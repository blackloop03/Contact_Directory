<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

if (!empty($_POST['selected_contacts'])) {
    $ids = $_POST['selected_contacts'];

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("DELETE FROM contacts WHERE user_id = ? AND contact_id IN ($placeholders)");
    $stmt->execute(array_merge([$user_id], $ids));
}

header("Location: my_contacts.php");
exit;
?>
