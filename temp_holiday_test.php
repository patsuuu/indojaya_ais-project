<?php
// Temp test script for save_attendance holiday off
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = [
    'gmail' => 'Simonapayorpatag12@gmail.com',
    'bio_id' => '222',
    'date' => '2026-06-12',
    'last_name' => 'PATAG',
    'first_name' => 'SIMON',
    'department' => 'Collection',
    'account_stage' => 'S0',
    'account' => 'Test Account',
    'team_leader' => 'LEADER',
    'action' => 'HOLIDAY_OFF'
];

ob_start();
include 'c:/xampp/htdocs/TimeIn-TimeOut/save_attendance.php';
$output = ob_get_clean();
file_put_contents('c:/xampp/htdocs/TimeIn-TimeOut/temp_holiday_test_output.json', $output);
echo $output;
