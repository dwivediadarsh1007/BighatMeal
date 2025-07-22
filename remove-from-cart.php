<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if (isset($_POST['product_id'])) {
    // For standard products
    try {
        $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$_SESSION['user_id'], $_POST['product_id']]);
        header('Location: cart.php');
        exit();
    } catch(PDOException $e) {
        $error = "Error removing from cart";
    }
} elseif (isset($_POST['cart_id'])) {
    // For custom meals
    try {
        $stmt = $conn->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
        $stmt->execute([$_POST['cart_id'], $_SESSION['user_id']]);
        header('Location: cart.php');
        exit();
    } catch(PDOException $e) {
        $error = "Error removing custom meal from cart";
    }
}
header('Location: cart.php');
exit();
?>
