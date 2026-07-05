<?php
require_once 'auth_check.php';
require_once 'db_connect.php';

// Set timezone to Philippines
date_default_timezone_set('Asia/Manila');

// Import classes at the top level
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

// Check if PhpSpreadsheet is installed
$composerAutoload = __DIR__ . '/vendor/autoload.php';
if (file_exists($composerAutoload)) {
    require_once $composerAutoload;
}

if (class_exists('PhpOffice\\PhpSpreadsheet\\Spreadsheet')) {
    generateFormattedExcel($conn);
} else {
    generateSimpleCSV($conn);
}

function formatDuration($seconds) {
    $seconds = max(0, intval(round($seconds)));
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $secs = $seconds % 60;
    return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
}

function calculateHolidayOffPay($date) {
    $baseAmount = 611.00;
    $regularHolidays = [
        '2026-01-01', '2026-04-09', '2026-05-01', '2026-05-27', '2026-06-12',
        '2026-08-31', '2026-11-30', '2026-12-25', '2026-12-30'
    ];
    $specialHolidays = [
        '2026-02-25', '2026-03-08', '2026-11-01', '2026-12-31'
    ];

    if (in_array($date, $regularHolidays, true)) {
        return [0, 0, $baseAmount, 1.0, 'Regular Holiday'];
    }

    if (in_array($date, $specialHolidays, true)) {
        $dayOfWeek = date('w', strtotime($date));
        if ($dayOfWeek === '0') {
            return [0, 0, round($baseAmount * 0.50, 2), 1.5, 'Special Holiday Rest Day'];
        }
        return [0, 0, round($baseAmount * 0.30, 2), 1.3, 'Special Holiday'];
    }

    return [0, 0, 0, 1.0, 'Regular'];
}

function hasRequestedOvertime($conn, $bioId, $date) {
    if (empty($bioId) || empty($date)) {
        return false;
    }

    $stmt = $conn->prepare("SELECT ot_requested, ot_hours, ot_pay FROM records WHERE bio_id = ? AND date = ? LIMIT 1");
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('ss', $bioId, $date);
    $stmt->execute();
    $stmt->store_result();
    $hasRequest = false;
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($otRequested, $otHours, $otPay);
        $stmt->fetch();
        $hasRequest = (bool) $otRequested || floatval($otHours) > 0 || floatval($otPay) > 0;
    }
    $stmt->close();

    return $hasRequest;
}

function calculateHoursAndPay($bioId, $date, $timeIn, $timeOut, $status, $conn) {
    if ($status === 'holiday_off') {
        return calculateHolidayOffPay($date);
    }

    if (empty($timeIn) || empty($timeOut)) {
        return [null, null, null, null, null];
    }

    $inTs = strtotime($timeIn);
    $outTs = strtotime($timeOut);
    if ($outTs < $inTs) {
        $outTs += 24 * 60 * 60;
    }

    $date = date('Y-m-d', $inTs);
    $workStart = strtotime($date . ' 09:00:00');
    $lunchStart = strtotime($date . ' 12:00:00');
    $lunchEnd = strtotime($date . ' 13:00:00');
    $workEnd = strtotime($date . ' 18:00:00');
    $halfDayThreshold = strtotime($date . ' 18:30:00');

    $info = getPayMultiplierForDate($bioId, $date, $conn);
    $multiplier = $info['multiplier'];
    $label = $info['label'];

    if (in_array($date, [
        '2026-01-01', '2026-04-09', '2026-05-01', '2026-05-27', '2026-06-12',
        '2026-08-31', '2026-11-30', '2026-12-25', '2026-12-30'
    ], true) && !empty($timeIn) && !empty($timeOut)) {
        $workStart = strtotime($date . ' 09:00:00');
        $workEnd = strtotime($date . ' 18:00:00');
        $hasBefore = strtotime($timeIn) < $workStart;
        $hasAfter = $outTs > $workEnd;
        if (!($hasBefore && $hasAfter)) {
            $multiplier = 1.0;
            $label = 'Regular';
        }
    }

    $hasRequestedOvertime = hasRequestedOvertime($conn, $bioId, $date);

    if ($hasRequestedOvertime && $outTs > $halfDayThreshold) {
        $paySeconds = 8 * 3600;
        $pay = round(8 * 76.375 * $multiplier, 2);
        return [$paySeconds, 8, $pay, $multiplier, $label];
    }

    if (!$hasRequestedOvertime && $outTs > $halfDayThreshold) {
        $paySeconds = 4 * 3600;
        $pay = round((611 / 2) * $multiplier, 2);
        return [$paySeconds, 4, $pay, $multiplier, $label . ' - Half Day'];
    }

    $paySeconds = 0;
    $payStart = max($inTs, $workStart);

    if ($payStart < $lunchStart) {
        $paySeconds += max(0, min($outTs, $lunchStart) - $payStart);
    }

    if ($outTs > $lunchEnd) {
        $afternoonStart = max($payStart, $lunchEnd);
        if ($afternoonStart < $workEnd) {
            $paySeconds += max(0, min($outTs, $workEnd) - $afternoonStart);
        }
    }

    $payHours = $paySeconds / 3600;
    $pay = round($payHours * 76.375 * $multiplier, 2);
    return [$paySeconds, $payHours, $pay, $multiplier, $label];
}

function hasAttendanceOnDate($conn, $bioId, $date) {
    if (empty($bioId) || empty($date)) {
        return false;
    }

    $stmt = $conn->prepare("SELECT 1 FROM records WHERE bio_id = ? AND date = ? AND time_in IS NOT NULL AND time_in <> '' LIMIT 1");
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('ss', $bioId, $date);
    $stmt->execute();
    $stmt->store_result();
    $found = $stmt->num_rows > 0;
    $stmt->close();

    return $found;
}

function getPayMultiplierForDate($bioId, $date, $conn) {
    $regularHolidays = [
        '2026-01-01', '2026-04-09', '2026-05-01', '2026-05-27', '2026-06-12',
        '2026-08-31', '2026-11-30', '2026-12-25', '2026-12-30'
    ];
    $specialHolidays = [
        '2026-02-25', '2026-03-08', '2026-11-01', '2026-12-31'
    ];

    if (in_array($date, $regularHolidays, true)) {
        if (!empty($bioId)) {
            $previousDate = date('Y-m-d', strtotime($date . ' -1 day'));
            $nextDate = date('Y-m-d', strtotime($date . ' +1 day'));
            $stmt = $conn->prepare("SELECT COUNT(*) FROM records WHERE bio_id = ? AND date IN (?, ?)");
            if ($stmt) {
                $stmt->bind_param('sss', $bioId, $previousDate, $nextDate);
                $stmt->execute();
                $stmt->bind_result($count);
                $stmt->fetch();
                $stmt->close();
                if ($count === 2) {
                    return ['multiplier' => 2.0, 'label' => 'Double Pay (Regular Holiday)'];
                }
            }
        }
        return ['multiplier' => 1.0, 'label' => 'Regular Holiday'];
    }

    if (in_array($date, $specialHolidays, true)) {
        return ['multiplier' => 1.3, 'label' => 'Special Holiday'];
    }

    return ['multiplier' => 1.0, 'label' => 'Regular'];
}

function getPayLabelForMultiplier($multiplier) {
    if ($multiplier === 2.0) {
        return 'Double Pay';
    }
    if ($multiplier === 1.5) {
        return '150% Pay';
    }
    if ($multiplier === 1.3) {
        return '130% Pay';
    }
    return 'Regular';
}

function generateFormattedExcel($conn) {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Set title
    $sheet->setTitle('Attendance Records');
    
       // Headers aligned with records page
    $headers = ['ID', 'Bio ID', 'Email', 'Last Name', 'First Name', 'Department', 
                'Account Stage', 'Account', 'Team Leader', 'OT Hours', 'Time IN', 'Late IN (min)', 'Time OUT', 'Late OUT (min)', 'Total Hours', 'Pay (PHP)', 'Date', 'Photo'];
    // Style header row
    $headerStyle = [
        'font' => [
            'bold' => true,
            'color' => ['rgb' => 'FFFFFF'],
            'size' => 12
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => '333333']
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER
        ]
    ];
    
    // Write headers
    foreach ($headers as $col => $header) {
        $cell = $sheet->getCellByColumnAndRow($col + 1, 1);
        $cell->setValue($header);
        $cell->getStyle()->applyFromArray($headerStyle);
    }
    
    $bioId = isset($_GET['bio_id']) ? trim($_GET['bio_id']) : '';
    $dateFrom = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
    $dateTo = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';
    $status = isset($_GET['status']) ? trim($_GET['status']) : '';

    $whereClauses = [];
    $params = [];
    $types = '';

    if ($bioId !== '') {
        $whereClauses[] = 'bio_id LIKE ?';
        $types .= 's';
        $params[] = '%' . $bioId . '%';
    }
    if ($dateFrom !== '') {
        $whereClauses[] = 'date >= ?';
        $types .= 's';
        $params[] = $dateFrom;
    }
    if ($dateTo !== '') {
        $whereClauses[] = 'date <= ?';
        $types .= 's';
        $params[] = $dateTo;
    }
    if ($status !== '') {
        if ($status === 'no_time_in') {
            $whereClauses[] = '(time_in IS NULL OR time_in = "")';
        } elseif ($status === 'no_time_out') {
            $whereClauses[] = '(time_out IS NULL OR time_out = "")';
        } else {
            $whereClauses[] = 'status = ?';
            $types .= 's';
            $params[] = $status;
        }
    }

    $sql = "SELECT id, bio_id, gmail, date, last_name, first_name, time_in, time_out, late_in_minutes, late_out_minutes, status, 
            department, account_stage, account, team_leader, direction, photo, payslip_file, ot_hours, ot_rate, ot_pay, ot_requested, ot_reason
            FROM records";
    if (!empty($whereClauses)) {
        $sql .= ' WHERE ' . implode(' AND ', $whereClauses);
    }
    $sql .= " ORDER BY date DESC, time_in DESC LIMIT 500";

    if (!empty($params)) {
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            die('Query failed: ' . $conn->error);
        }
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $conn->query($sql);
    }

    if (!$result) {
        die('Query failed: ' . $conn->error);
    }
    
    // Write data
    $row = 2;
    $rowCount = 0;
    while ($data = $result->fetch_assoc()) {
        $rowCount++;
        $timeIn = $data['time_in'] ? substr($data['time_in'], 11, 5) : '-';
        $timeOut = $data['time_out'] ? substr($data['time_out'], 11, 5) : '-';
        list($rowSeconds, $rowHours, $rowPay, $rowMultiplier, $rowPayLabel) = calculateHoursAndPay($data['bio_id'], $data['date'], $data['time_in'], $data['time_out'], $data['status'], $conn);
        $excelHours = $rowSeconds !== null ? formatDuration($rowSeconds) : '-';
        $basePay = $rowPay !== null ? $rowPay : 0;
        $excelPay = number_format($basePay + floatval($data['ot_pay']), 2, '.', '');
        $excelOtHours = number_format(floatval($data['ot_hours']), 2, '.', '');
        $photoUrl = !empty($data['photo']) ? 'uploads/' . $data['photo'] : '';

        $values = [
            $data['id'],
            $data['bio_id'],
            $data['gmail'],
            $data['last_name'],
            $data['first_name'],
            $data['department'],
            $data['account_stage'],
            $data['account'],
            $data['team_leader'],
            $excelOtHours,
            $timeIn,
            $data['late_in_minutes'] > 0 ? $data['late_in_minutes'] . ' min' : 'On time',
            $timeOut,
            $data['late_out_minutes'] > 0 ? $data['late_out_minutes'] . ' min LATE' : ($data['late_out_minutes'] < 0 ? abs($data['late_out_minutes']) . ' min EARLY' : '-'),
            $excelHours,
            $excelPay,
            $data['date'],
            $photoUrl
        ];
        
        // Alternate row colors
        $bgColor = ($row % 2 == 0) ? 'F9F9F9' : 'FFFFFF';
        $rowStyle = [
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $bgColor]
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CCCCCC']
                ]
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'vertical' => Alignment::VERTICAL_CENTER
            ]
        ];
        
        foreach ($values as $col => $value) {
            $cell = $sheet->getCellByColumnAndRow($col + 1, $row);
            $cell->setValue($value);
            $cell->getStyle()->applyFromArray($rowStyle);
        }
        
        $row++;
    }
    
    // Auto-size columns
    foreach (range(1, count($headers)) as $col) {
        $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
    }
    
    // Add summary row
    $row++;
    $summaryStyle = [
        'font' => [
            'bold' => true,
            'size' => 11
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => 'E8E8E8']
        ]
    ];
    
    $summaryCell = $sheet->getCellByColumnAndRow(1, $row);
    $summaryCell->setValue('Total Records: ' . $rowCount);
    $summaryCell->getStyle()->applyFromArray($summaryStyle);
    
    // Generate Excel file
    $filename = 'attendance_' . date('Y-m-d_H-i-s') . '.xlsx';
    
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    header('Pragma: public');
    header('Expires: 0');
    
    try {
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
    } catch (Exception $e) {
        die('Error generating Excel: ' . $e->getMessage());
    }
}

function generateSimpleCSV($conn) {
    $bioId = isset($_GET['bio_id']) ? trim($_GET['bio_id']) : '';
    $dateFrom = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
    $dateTo = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';
    $status = isset($_GET['status']) ? trim($_GET['status']) : '';

    $whereClauses = [];
    $params = [];
    $types = '';

    if ($bioId !== '') {
        $whereClauses[] = 'bio_id LIKE ?';
        $types .= 's';
        $params[] = '%' . $bioId . '%';
    }
    if ($dateFrom !== '') {
        $whereClauses[] = 'date >= ?';
        $types .= 's';
        $params[] = $dateFrom;
    }
    if ($dateTo !== '') {
        $whereClauses[] = 'date <= ?';
        $types .= 's';
        $params[] = $dateTo;
    }
    if ($status !== '') {
        if ($status === 'no_time_in') {
            $whereClauses[] = '(time_in IS NULL OR time_in = "")';
        } elseif ($status === 'no_time_out') {
            $whereClauses[] = '(time_out IS NULL OR time_out = "")';
        } else {
            $whereClauses[] = 'status = ?';
            $types .= 's';
            $params[] = $status;
        }
    }

    $sql = "SELECT id, bio_id, gmail, date, last_name, first_name, time_in, time_out, late_in_minutes, late_out_minutes, status,
            department, account_stage, account, team_leader, direction, photo, payslip_file,
            ot_hours, ot_rate, ot_pay, ot_requested, ot_reason
            FROM records";
    if (!empty($whereClauses)) {
        $sql .= ' WHERE ' . implode(' AND ', $whereClauses);
    }
    $sql .= " ORDER BY date DESC, time_in DESC LIMIT 500";

    if (!empty($params)) {
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            die('Query failed: ' . $conn->error);
        }
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $conn->query($sql);
    }
    
    if (!$result) {
        die('Query failed: ' . $conn->error);
    }
    
    $filename = 'attendance_' . date('Y-m-d_H-i-s') . '.csv';
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    header('Pragma: public');
    header('Expires: 0');
    
    // UTF-8 BOM
    echo "\xEF\xBB\xBF";
    
    // Headers aligned with records page
    $headers = ['ID', 'Bio ID', 'Email', 'Last Name', 'First Name', 'Department', 
                'Account Stage', 'Account', 'Team Leader', 'OT Hours', 'Time IN', 'Late IN (min)', 'Time OUT', 'Late OUT (min)', 'Total Hours', 'Pay (PHP)', 'Date', 'Photo'];

    $output = fopen('php://output', 'w');
    if ($output === false) {
        die('Unable to open output stream');
    }

    fputcsv($output, $headers);

    // Data
    while ($row = $result->fetch_assoc()) {
        $timeIn = $row['time_in'] ? substr($row['time_in'], 11, 5) : '-';
        $timeOut = $row['time_out'] ? substr($row['time_out'], 11, 5) : '-';
        list($rowSeconds, $rowHours, $rowPay, $rowMultiplier, $rowPayLabel) = calculateHoursAndPay($row['bio_id'], $row['date'], $row['time_in'], $row['time_out'], $row['status'], $conn);
        $hoursValue = $rowSeconds !== null ? formatDuration($rowSeconds) : '';
        $basePay = $rowPay !== null ? $rowPay : 0;
        $payValue = number_format($basePay + floatval($row['ot_pay']), 2, '.', '');
        $otHoursValue = number_format(floatval($row['ot_hours']), 2, '.', '');
        $photoUrl = !empty($row['photo']) ? 'uploads/' . $row['photo'] : '';

        $data = [
            $row['id'],
            $row['bio_id'],
            $row['gmail'],
            $row['last_name'],
            $row['first_name'],
            $row['department'],
            $row['account_stage'],
            $row['account'],
            $row['team_leader'],
            $otHoursValue,
            $timeIn,
            $row['late_in_minutes'] > 0 ? $row['late_in_minutes'] . ' min' : 'On time',
            $timeOut,
            $row['late_out_minutes'] > 0 ? $row['late_out_minutes'] . ' min LATE' : ($row['late_out_minutes'] < 0 ? abs($row['late_out_minutes']) . ' min EARLY' : '-'),
            $hoursValue,
            $payValue,
            $row['date'],
            $photoUrl
        ];
        
        fputcsv($output, $data);
    }
    
    fclose($output);
}

$conn->close();
?>