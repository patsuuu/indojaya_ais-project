<?php
session_start();
require_once 'role_helpers.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'db_connect.php';

    $bio_id = trim($_POST['bio_id'] ?? '');
    $gmail = trim($_POST['gmail'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $first_name = trim($_POST['first_name'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $account_stage = trim($_POST['account_stage'] ?? '');
    $account = trim($_POST['account'] ?? '');
    $team_leader = trim($_POST['team_leader'] ?? '');

    if (empty($bio_id) || empty($gmail) || empty($last_name) || empty($first_name) || empty($department) || empty($account_stage) || empty($account) || empty($team_leader)) {
        $error = '❌ All fields are required.';
    } elseif (!filter_var($gmail, FILTER_VALIDATE_EMAIL)) {
        $error = '❌ Please enter a valid Gmail address.';
    } else {
        $check_sql = "SELECT id FROM employees WHERE bio_id = ? LIMIT 1";
        $stmt = $conn->prepare($check_sql);
        $stmt->bind_param('s', $bio_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error = '❌ This Bio ID is already registered.';
        } else {
            $insert_sql = "INSERT INTO employees (bio_id, gmail, last_name, first_name, department, account_stage, account, team_leader) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param('ssssssss', $bio_id, $gmail, $last_name, $first_name, $department, $account_stage, $account, $team_leader);

            if ($insert_stmt->execute()) {
                $success = '✅ Employee Bio ID registered successfully.';
                $bio_id = $gmail = $last_name = $first_name = $department = $account_stage = $account = $team_leader = '';
            } else {
                $error = '❌ Error saving employee info: ' . $insert_stmt->error;
            }

            $insert_stmt->close();
        }

        $stmt->close();
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register Employee Bio ID</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h3 class="card-title mb-4">Register New Employee Bio ID</h3>

                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>

                        <?php if (!empty($success)): ?>
                            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="mb-3">
                                <label class="form-label">Bio ID</label>
                                <input type="text" class="form-control" name="bio_id" value="<?php echo htmlspecialchars($bio_id ?? ''); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Gmail</label>
                                <input type="email" class="form-control" name="gmail" value="<?php echo htmlspecialchars($gmail ?? ''); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Last Name</label>
                                <input type="text" class="form-control" name="last_name" value="<?php echo htmlspecialchars($last_name ?? ''); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">First Name</label>
                                <input type="text" class="form-control" name="first_name" value="<?php echo htmlspecialchars($first_name ?? ''); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Department</label>
                                <select class="form-control" name="department" required>
                                    <option value="">Select department</option>
                                    <option value="Collection">Collection</option>
                                    <option value="Telemarketing">Telemarketing</option>
                                    <option value="Reviewer">Reviewer</option>
                                    <option value="Compliance">Compliance</option>
                                    <option value="Management">Management</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Account Stage</label>
                                <select class="form-control" name="account_stage" required>
                                    <option value="">Select stage</option>
                                    <option value="S0">S0</option>
                                    <option value="S1">S1</option>
                                    <option value="S2">S2</option>
                                    <option value="S3">S3</option>
                                    <option value="S4">S4</option>
                                    <option value="Telemarketing">Telemarketing</option>
                                    <option value="Hr">Hr</option>
                                    <option value="Admin">Admin</option>
                                    <option value="Accounting">Accounting</option>
                                    <option value="It">It</option>
                                    <option value="Trainee">Trainee</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Account</label>
                                <input type="text" class="form-control" name="account" value="<?php echo htmlspecialchars($account ?? ''); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Team Leader</label>
                                <input type="text" class="form-control" name="team_leader" value="<?php echo htmlspecialchars($team_leader ?? ''); ?>" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Register Employee</button>
                            <?php if (hasRole('Hr') || hasRole('HR')): ?>
                                <a href="records.php" class="btn btn-secondary ms-2">Back to Records</a>
                            <?php else: ?>
                                <a href="home.php" class="btn btn-secondary ms-2">Go to Attendance</a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
