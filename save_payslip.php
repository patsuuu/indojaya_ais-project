<?php
header('Content-Type: application/json; charset=utf-8');

ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once 'db_connect.php';

$uploadsDir = __DIR__ . '/uploads';

if (!is_dir($uploadsDir)) {
    @mkdir($uploadsDir, 0777, true);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$bioId = isset($_POST['bio_id']) ? trim($_POST['bio_id']) : '';
$payslipData = isset($_POST['payslip_data']) ? trim($_POST['payslip_data']) : null;

if (empty($bioId)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Bio ID is required']);
    exit;
}

$filename = null;
if (isset($_FILES['payslip']) && $_FILES['payslip']['error'] !== UPLOAD_ERR_NO_FILE) {
    $file = $_FILES['payslip'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Upload error code: ' . $file['error']]);
        exit;
    }

    // Allowed file types for payslip.
    $allowedMimeTypes = [
        'application/pdf',
        'image/png',
        'image/jpeg',
        'image/jpg',
        'image/webp'
    ];

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedMimeTypes, true)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid file type: ' . $mimeType]);
        exit;
    }

    if ($file['size'] > 10 * 1024 * 1024) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'File too large. Maximum size is 10MB.']);
        exit;
    }

    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $extension = strtolower($extension);
    if ($extension === '') {
        $extension = $mimeType === 'application/pdf' ? 'pdf' : 'bin';
    }

    $filename = 'payslip_' . preg_replace('/[^a-zA-Z0-9_-]/', '', $bioId) . '_' . date('YmdHis') . '_' . uniqid() . '.' . $extension;
    $filepath = $uploadsDir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to save uploaded file']);
        exit;
    }
}

// Ensure the records table has a payslip_file column.
$columnCheck = $conn->query("SHOW COLUMNS FROM records LIKE 'payslip_file'");
if ($columnCheck && $columnCheck->num_rows === 0) {
    $conn->query("ALTER TABLE records ADD COLUMN payslip_file VARCHAR(255) NULL");
}

// Ensure the records table has a payslip_data column.
$dataColumnCheck = $conn->query("SHOW COLUMNS FROM records LIKE 'payslip_data'");
if ($dataColumnCheck && $dataColumnCheck->num_rows === 0) {
    $conn->query("ALTER TABLE records ADD COLUMN payslip_data TEXT NULL");
}

// Save a reference in the records table when a matching Bio ID exists.
$recordId = null;
$stmt = $conn->prepare("SELECT id FROM records WHERE bio_id = ? ORDER BY date DESC, id DESC LIMIT 1");
if ($stmt) {
    $stmt->bind_param('s', $bioId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $recordId = $row['id'];
    }
    $stmt->close();
}

if ($recordId === null) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'No attendance record found for this Bio ID.']);
    $conn->close();
    exit;
}

$fields = [];
$types = '';
$params = [];

if ($filename !== null) {
    $fields[] = 'payslip_file = ?';
    $types .= 's';
    $params[] = $filename;
}

if ($payslipData !== null) {
    $fields[] = 'payslip_data = ?';
    $types .= 's';
    $params[] = $payslipData;
}

if (!empty($fields)) {
    $sql = 'UPDATE records SET ' . implode(', ', $fields) . ' WHERE id = ?';
    $types .= 'i';
    $params[] = $recordId;
    $updateStmt = $conn->prepare($sql);
    if ($updateStmt) {
        $updateStmt->bind_param($types, ...$params);
        $updateStmt->execute();
        $updateStmt->close();
    }
}

$conn->close();

echo json_encode([
    'success' => true,
    'message' => 'Payslip saved successfully.',
    'filename' => $filename,
    'url' => $filename ? 'uploads/' . $filename : null,
    'record_id' => $recordId
]);
?>