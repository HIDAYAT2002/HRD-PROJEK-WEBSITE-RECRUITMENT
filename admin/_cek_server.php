<?php
echo "OS: " . PHP_OS . "<br>";
echo "PHP: " . PHP_VERSION . "<br>";
echo "Server: " . ($_SERVER['SERVER_SOFTWARE'] ?? '-') . "<br>";
echo "Disable functions: " . (ini_get('disable_functions') ?: '-') . "<br>";
?>
