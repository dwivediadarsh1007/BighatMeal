<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);

// Return JSON response
echo json_encode([
    'loggedIn' => $isLoggedIn,
    'userId' => $isLoggedIn ? $_SESSION['user_id'] : null,
    'userName' => $isLoggedIn ? ($_SESSION['name'] ?? 'User') : null
]);

// End the script
exit();
