<?php
// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Redirect to records if logged in
header('Location: records.html');
exit;
?>