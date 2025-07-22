<?php
header('Content-Type: text/plain');

echo "Test file is working!\n";
echo "Current directory: " . getcwd() . "\n";
echo "File exists: " . (file_exists(__FILE__) ? 'Yes' : 'No') . "\n";

echo "\nServer variables:\n";
print_r($_SERVER);

echo "\nGet variables:\n";
print_r($_GET);

echo "\nPost variables:\n";
print_r($_POST);
