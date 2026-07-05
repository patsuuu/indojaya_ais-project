<?php
require_once 'db_connect.php';

if (!$conn) {
    http_response_code(500);
    header('Content-Type: text/plain');
    die('Database connection failed');
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    header('Content-Type: text/plain');
    die('Invalid ID');
}

$id = intval($_GET['id']);

error_log("view_photo.php - Requesting photo for ID: " . $id);

try {
    $sql = "SELECT photo FROM records WHERE id = ?";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param('i', $id);
    
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    $stmt->close();
    
    if (!$row) {
        http_response_code(404);
        header('Content-Type: text/plain');
        die('Record not found');
    }
    
    if (empty($row['photo'])) {
        http_response_code(404);
        header('Content-Type: text/plain');
        die('Photo is empty');
    }
    
    $photo = $row['photo'];
    error_log("view_photo.php - Photo data found, length: " . strlen($photo));
    
    // If it's already a data URL, extract and send the image
    if (strpos($photo, 'data:image/png;base64,') === 0) {
        error_log("view_photo.php - Detected PNG data URL");
        $base64 = substr($photo, 22); // Remove 'data:image/png;base64,'
        header('Content-Type: image/png');
        header('Cache-Control: public, max-age=3600');
        echo base64_decode($base64);
        
    } elseif (strpos($photo, 'data:image/jpeg;base64,') === 0) {
        error_log("view_photo.php - Detected JPEG data URL");
        $base64 = substr($photo, 23); // Remove 'data:image/jpeg;base64,'
        header('Content-Type: image/jpeg');
        header('Cache-Control: public, max-age=3600');
        echo base64_decode($base64);
        
    } elseif (strpos($photo, 'data:image/webp;base64,') === 0) {
        error_log("view_photo.php - Detected WebP data URL");
        $base64 = substr($photo, 23); // Remove 'data:image/webp;base64,'
        header('Content-Type: image/webp');
        header('Cache-Control: public, max-age=3600');
        echo base64_decode($base64);
        
    } elseif (strpos($photo, 'data:') === 0) {
        error_log("view_photo.php - Detected generic data URL");
        // Generic data URL
        if (preg_match('/data:([^;]+);base64,(.+)/', $photo, $matches)) {
            header('Content-Type: ' . $matches[1]);
            header('Cache-Control: public, max-age=3600');
            echo base64_decode($matches[2]);
        } else {
            http_response_code(400);
            header('Content-Type: text/plain');
            die('Invalid photo format');
        }
        
    } else if (strlen($photo) > 100 && (strpos($photo, 'iVBO') === 0 || strpos($photo, '/9j/') === 0)) {
        // It's raw base64
        error_log("view_photo.php - Detected raw base64");
        header('Content-Type: image/png');
        header('Cache-Control: public, max-age=3600');
        echo base64_decode($photo);
        
    } else {
        http_response_code(400);
        header('Content-Type: text/plain');
        error_log("view_photo.php - Unknown photo format, first 100 chars: " . substr($photo, 0, 100));
        die('Unknown photo format');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: text/plain');
    error_log("view_photo.php error: " . $e->getMessage());
    die('Error: ' . $e->getMessage());
}

$conn->close();
?>