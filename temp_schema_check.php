<?php
require 'c:/xampp/htdocs/TimeIn-TimeOut/db_connect.php';
$tables = array('employees', 'records');
foreach ($tables as $table) {
  $res = $conn->query('SHOW COLUMNS FROM ' . $table);
  echo "TABLE: $table\n";
  while ($row = $res->fetch_assoc()) {
    echo $row['Field'] . ' ' . $row['Type'] . ' ' . $row['Null'] . ' ' . ($row['Default'] === null ? 'NULL' : $row['Default']) . "\n";
  }
  echo "\n";
}
$conn->close();
?>
