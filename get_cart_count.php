<?php
session_start();

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = array();
}

$count = array_sum($_SESSION['cart']);

echo json_encode(['count' => $count]);
?>
