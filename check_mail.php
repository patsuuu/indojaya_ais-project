<?php
if (function_exists('mail')) {
    echo "✅ mail() function exists<br>";
} else {
    echo "❌ mail() function is disabled<br>";
}

// Try sending
$result = mail('test@test.com', 'Test', 'Test message');
echo $result ? "✅ mail() returned true" : "❌ mail() returned false";
?>