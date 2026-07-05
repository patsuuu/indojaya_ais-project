<?php
header('Content-Type: application/json');

// Set timezone to Philippines (PHT)
date_default_timezone_set('Asia/Manila');

// Return current server time in Philippines timezone
$serverTime = date('Y-m-d H:i:s');
$timestamp = time();

echo json_encode([
    'server_time' => $serverTime,
    'timestamp' => $timestamp * 1000,  // milliseconds
    'timezone' => 'Asia/Manila',
    'offset' => '+08:00'
]);
?>