<?php
// Prevent any output before JSON response
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once 'db_connect.php';

// Set timezone to Philippines
date_default_timezone_set('Asia/Manila');

function isRegularHoliday($date) {
    $year = date('Y', strtotime($date));
    $regularHolidays = [
        $year . '-01-01', $year . '-04-09', $year . '-05-01', $year . '-05-27', $year . '-06-12',
        $year . '-08-31', $year . '-11-30', $year . '-12-25', $year . '-12-30'
    ];
    return in_array($date, $regularHolidays, true);
}

function isSpecialHoliday($date) {
    $year = date('Y', strtotime($date));
    $specialHolidays = [
        $year . '-02-25', $year . '-03-08', $year . '-11-01', $year . '-12-31'
    ];
    return in_array($date, $specialHolidays, true);
}

function getHolidayOffAmount($date) {
    $baseAmount = 611.00;
    if (isRegularHoliday($date)) {
        return $baseAmount;
    }
    if (isSpecialHoliday($date)) {
        $dayOfWeek = date('w', strtotime($date));
        if ($dayOfWeek === '0') {
            return round($baseAmount * 0.50, 2);
        }
        return round($baseAmount * 0.30, 2);
    }
    return $baseAmount;
}

// Clear any previous output
ob_clean();

$response = [
    'success' => false,
    'message' => '',
    'error' => '',
    'status' => '' // 'complete', 'incomplete_no_timein', 'incomplete_no_timeout', 'holiday_off'
];

// Define expected times
$EXPECTED_TIME_IN = '08:30:00'; // 8:30 AM
$EXPECTED_TIME_OUT = '18:00:00'; // 6:00 PM

// Set JSON header immediately
header('Content-Type: application/json; charset=utf-8');

try {
    // Validate required fields
    $required_fields = ['gmail', 'bio_id', 'date', 'last_name', 'first_name', 'department', 'account_stage', 'account', 'team_leader', 'action'];
    $missing_fields = [];

    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $missing_fields[] = $field;
        }
    }

    if (!empty($missing_fields)) {
        $response['error'] = 'Missing required fields: ' . implode(', ', $missing_fields);
        $response['fields'] = $missing_fields;
        echo json_encode($response);
        exit;
    }

    // Get form data
    $gmail = trim($_POST['gmail']);
    $bio_id = trim($_POST['bio_id']);
    $date = trim($_POST['date']);
    $last_name = trim($_POST['last_name']);
    $first_name = trim($_POST['first_name']);
    $department = trim($_POST['department']);
    $account_stage = trim($_POST['account_stage']);
    $account = trim($_POST['account']);
    $team_leader = trim($_POST['team_leader']);
    $action = trim($_POST['action']); // 'IN' or 'OUT'
    $photo_filename = isset($_POST['photo_filename']) && !empty($_POST['photo_filename']) ? trim($_POST['photo_filename']) : null;

    // Ensure the bio_id is registered in the employees table
    $account_check_sql = "SELECT id FROM employees WHERE bio_id = ? LIMIT 1";
    $account_check_stmt = $conn->prepare($account_check_sql);

    if (!$account_check_stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $account_check_stmt->bind_param('s', $bio_id);
    if (!$account_check_stmt->execute()) {
        throw new Exception("Execute failed: " . $account_check_stmt->error);
    }

    $account_check_result = $account_check_stmt->get_result();
    $account_check_stmt->close();

    if ($account_check_result->num_rows === 0) {
        $response['error'] = '❌ Bio ID is not registered. Only registered employees may submit attendance.';
        echo json_encode($response);
        exit;
    }

    // Validate action
    if ($action !== 'IN' && $action !== 'OUT' && $action !== 'HOLIDAY_OFF') {
        $response['error'] = 'Invalid action. Must be IN, OUT, or HOLIDAY_OFF.';
        echo json_encode($response);
        exit;
    }

    if ($action === 'HOLIDAY_OFF') {
        $isHolidayDate = isRegularHoliday($date) || isSpecialHoliday($date);
        if (!$isHolidayDate) {
            $response['error'] = 'Holiday off can only be recorded on a regular or special holiday.';
            echo json_encode($response);
            exit;
        }

        // Check if holiday_off has already been recorded for this date
        $holiday_check_sql = "SELECT id FROM records 
                              WHERE bio_id = ? 
                              AND date = ? 
                              AND status = 'holiday_off'
                              LIMIT 1";
        
        if ($holiday_check_stmt = $conn->prepare($holiday_check_sql)) {
            $holiday_check_stmt->bind_param('ss', $bio_id, $date);
            $holiday_check_stmt->execute();
            $holiday_check_result = $holiday_check_stmt->get_result();
            
            if ($holiday_check_result->num_rows > 0) {
                $response['error'] = 'You have already recorded Holiday Off for this date. Cannot use Holiday Off more than once per day.';
                echo json_encode($response);
                exit;
            }
            $holiday_check_stmt->close();
        }
    }

    // Get current time in Philippines timezone
    $current_time = date('Y-m-d H:i:s');
    $current_time_only = date('H:i:s');

    // Calculate late time
    $late_minutes = 0;

    try {
        if ($action === 'IN') {
            $time_in = $current_time;
            $time_out = NULL;
            $late_out_minutes = 0;
            
            // Calculate late in minutes for time in
            $expected_time = DateTime::createFromFormat('H:i:s', $EXPECTED_TIME_IN);
            $actual_time = DateTime::createFromFormat('H:i:s', $current_time_only);
            
            if ($actual_time && $expected_time && $actual_time > $expected_time) {
                $interval = $expected_time->diff($actual_time);
                $late_minutes = ($interval->h * 60) + $interval->i;
            }
            
        } elseif ($action === 'OUT') {
            $time_in = NULL;
            $time_out = $current_time;
            $late_minutes = 0;
            
            // Calculate late time for time out (how early or late they left)
            $expected_time = DateTime::createFromFormat('H:i:s', $EXPECTED_TIME_OUT);
            $actual_time = DateTime::createFromFormat('H:i:s', $current_time_only);
            
            // Negative means they left early, positive means they left late
            if ($actual_time && $expected_time) {
                if ($actual_time < $expected_time) {
                    $interval = $actual_time->diff($expected_time);
                    $late_minutes = -($interval->h * 60 + $interval->i); // Negative for early
                } else {
                    $interval = $expected_time->diff($actual_time);
                    $late_minutes = ($interval->h * 60) + $interval->i; // Positive for late
                }
            }
        } else {
            $time_in = NULL;
            $time_out = NULL;
            $late_minutes = 0;
            $late_out_minutes = 0;
        }
    } catch (Exception $e) {
        error_log("Time calculation error: " . $e->getMessage());
        $response['error'] = 'Error calculating time: ' . $e->getMessage();
        echo json_encode($response);
        exit;
    }

    // Check if record exists for this date
    $check_sql = "SELECT id, time_in, time_out, status FROM records 
                  WHERE bio_id = ? 
                  AND date = ? 
                  AND gmail = ?
                  LIMIT 1";
    
    if (!$check_stmt = $conn->prepare($check_sql)) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $check_stmt->bind_param('sss', $bio_id, $date, $gmail);
    
    if (!$check_stmt->execute()) {
        throw new Exception("Execute failed: " . $check_stmt->error);
    }

    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        // Update existing record
        $row = $check_result->fetch_assoc();
        $record_id = $row['id'];
        $existing_time_in = $row['time_in'];
        $existing_time_out = $row['time_out'];
        $existing_status = $row['status'];
        
        if ($action === 'HOLIDAY_OFF') {
            $response['error'] = 'A record already exists for this date. Cannot record a holiday off when attendance is already present.';
            echo json_encode($response);
            exit;
        }

        // Prevent time-in/time-out if holiday_off already exists
        if ($existing_status === 'holiday_off' && ($action === 'IN' || $action === 'OUT')) {
            $response['error'] = 'Holiday Off has already been recorded for this date. Cannot add attendance to a Holiday Off record.';
            echo json_encode($response);
            exit;
        }

        if ($existing_time_in && $action === 'IN') {
            $response['error'] = 'Time IN has already been recorded for this date. Only one Time IN is allowed per day.';
            echo json_encode($response);
            exit;
        }

        if ($existing_time_out && $action === 'OUT') {
            $response['error'] = 'Time OUT has already been recorded for this date. Only one Time OUT is allowed per day.';
            echo json_encode($response);
            exit;
        }

        if ($action === 'IN') {
            $update_sql = "UPDATE records 
                          SET time_in = ?, 
                              late_in_minutes = ?,
                              photo = ? 
                          WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param('sisi', $time_in, $late_minutes, $photo_filename, $record_id);
            $status = ($existing_time_out) ? 'complete' : 'incomplete_no_timeout';
        } else {
            $update_sql = "UPDATE records 
                          SET time_out = ?, 
                              late_out_minutes = ?,
                              direction = 'OUT',
                              photo = ? 
                          WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param('sisi', $time_out, $late_minutes, $photo_filename, $record_id);
            $status = ($existing_time_in) ? 'complete' : 'incomplete_no_timein';
        }
        
        if (!$update_stmt->execute()) {
            throw new Exception("Update failed: " . $update_stmt->error);
        }

        // Build response message
        $late_text = '';
        if ($action === 'IN' && $late_minutes > 0) {
            $late_text = " (Late: " . $late_minutes . " minutes)";
        } elseif ($action === 'OUT') {
            if ($late_minutes > 0) {
                $late_text = " (Left: " . $late_minutes . " minutes LATE)";
            } elseif ($late_minutes < 0) {
                $late_text = " (Left: " . abs($late_minutes) . " minutes EARLY)";
            } else {
                $late_text = " (On time)";
            }
        }
        
        // Status message
        $status_msg = '';
        if ($status === 'incomplete_no_timeout') {
            $status_msg = " ⚠️ Record incomplete - Awaiting Time OUT";
        } elseif ($status === 'incomplete_no_timein') {
            $status_msg = " ⚠️ Record incomplete - No Time IN recorded";
        } elseif ($status === 'complete') {
            $status_msg = " ✅ Record complete - Time IN & OUT recorded";
        }
        
        $response['success'] = true;
        $response['message'] = 'Time ' . $action . ' recorded successfully at ' . $current_time . ' (PHT)' . $late_text . $status_msg;
        $response['late_minutes'] = $late_minutes;
        $response['status'] = $status;
        
    } else {
        // Insert new record
        if ($action === 'IN') {
            $direction = 'IN';
            $status = 'incomplete_no_timeout';
        } elseif ($action === 'OUT') {
            $direction = 'OUT';
            $status = 'incomplete_no_timein';
        } else {
            $direction = 'HOLIDAY_OFF';
            $status = 'holiday_off';
        }
        
        $insert_sql = "INSERT INTO records 
                      (bio_id, gmail, date, last_name, first_name, department, account_stage, account, team_leader, time_in, time_out, late_in_minutes, late_out_minutes, direction, photo, status) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $insert_stmt = $conn->prepare($insert_sql);
        
        if (!$insert_stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        // Set late values based on action
        $late_in_val = ($action === 'IN') ? $late_minutes : 0;
        $late_out_val = ($action === 'OUT') ? $late_minutes : 0;
        if ($action === 'HOLIDAY_OFF') {
            $late_in_val = 0;
            $late_out_val = 0;
        }
        
        $insert_stmt->bind_param('ssssssssssiiisss', 
            $bio_id, $gmail, $date, $last_name, $first_name, 
            $department, $account_stage, $account, $team_leader, 
            $time_in, $time_out, $late_in_val, $late_out_val, $direction, $photo_filename, $status);
        
        if (!$insert_stmt->execute()) {
            throw new Exception("Insert failed: " . $insert_stmt->error);
        }

        if ($action === 'HOLIDAY_OFF') {
            $holidayAmount = getHolidayOffAmount($date);
            $update_balance_sql = "UPDATE employees SET payment_balance = payment_balance + ? WHERE bio_id = ?";
            $balance_stmt = $conn->prepare($update_balance_sql);
            if (!$balance_stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            $balance_stmt->bind_param('ds', $holidayAmount, $bio_id);
            if (!$balance_stmt->execute()) {
                throw new Exception("Balance update failed: " . $balance_stmt->error);
            }
            $balance_stmt->close();
        }

        // Build response message
        $late_text = '';
        if ($action === 'IN' && $late_minutes > 0) {
            $late_text = " (Late: " . $late_minutes . " minutes)";
        }
        
        // Status message
        if ($action === 'IN') {
            $status_msg = " ⚠️ Record created - Awaiting Time OUT";
        } elseif ($action === 'OUT') {
            $status_msg = " ⚠️ Record created - No Time IN recorded";
        } else {
            $status_msg = " ✅ Holiday off recorded. 1-day payment has been added to employee account.";
        }
        
        $response['success'] = true;
        $response['message'] = 'Attendance record created successfully at ' . $current_time . ' (PHT)' . $late_text . $status_msg;
        $response['late_minutes'] = $late_minutes;
        $response['status'] = $status;
    }
    
    $check_stmt->close();
    
} catch (Exception $e) {
    error_log("Save attendance error: " . $e->getMessage());
    $response['success'] = false;
    $response['error'] = 'Error: ' . $e->getMessage();
}

if (isset($conn)) {
    $conn->close();
}

echo json_encode($response);
ob_end_flush();
?>