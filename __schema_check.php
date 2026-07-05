<?php
require 'db_connect.php';
$res = $conn->query('SHOW COLUMNS FROM records');
if (!$res) { echo "ERROR: " . $conn->error; exit; }
while ($row = $res->fetch_assoc()) {
    echo $row['Field'] . ' ' . $row['Type'] . "\n";
}
$conn->close();
