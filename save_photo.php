<?php
header('Content-Type: application/json');

ini_set('display_errors', 0);
error_reporting(E_ALL);

$uploadsDir = __DIR__ . '/uploads';

// Create uploads directory if needed
if (!is_dir($uploadsDir)) {
    @mkdir($uploadsDir, 0777, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['photo'])) {
    $file = $_FILES['photo'];
    
    error_log("Photo upload - File: {$file['name']}, Size: {$file['size']}, Error: {$file['error']}");
    
    // Check upload error
    if ($file['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['error' => 'Upload error code: ' . $file['error']]);
        error_log('Upload error: ' . $file['error']);
        exit;
    }
    
    // Verify file exists
    if (!file_exists($file['tmp_name'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Temp file not found']);
        exit;
    }
    
    // Check MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    error_log("MIME type: " . $mimeType);
    
    if (!in_array($mimeType, ['image/png', 'image/jpeg', 'image/jpg', 'image/webp'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid file type: ' . $mimeType]);
        exit;
    }
    
    // Check file size
    if ($file['size'] > 5 * 1024 * 1024) {
        http_response_code(400);
        echo json_encode(['error' => 'File too large']);
        exit;
    }
    
    // Generate filename
    $filename = 'photo_' . date('YmdHis') . '_' . uniqid() . '.png';
    $filepath = $uploadsDir . '/' . $filename;
    
    error_log("Saving photo to: " . $filepath);
    error_log("Directory writable: " . (is_writable($uploadsDir) ? 'Yes' : 'No'));
    
    // Move file
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        if (file_exists($filepath)) {
            $fileSize = filesize($filepath);
            error_log("✅ Photo saved: $filename (Size: $fileSize bytes)");
            
            echo json_encode([
                'success' => true,
                'filename' => $filename,
                'url' => 'uploads/' . $filename,
                'size' => $fileSize
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'File not found after save']);
        }
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to move file']);
        error_log('move_uploaded_file failed');
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'No file uploaded']);
}
?>