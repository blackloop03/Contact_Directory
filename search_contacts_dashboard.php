<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$search_query = trim($_GET['q'] ?? '');

if ($search_query !== '') {
    $stmt = $pdo->prepare("
        SELECT * FROM contacts 
        WHERE user_id = ? AND 
        (first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR phone LIKE ?)
        ORDER BY created_at DESC
    ");
    $search_term = "%{$search_query}%";
    $stmt->execute([$user_id, $search_term, $search_term, $search_term, $search_term]);
    $contacts = $stmt->fetchAll();
} else {
    $stmt = $pdo->prepare("SELECT * FROM contacts WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
    $stmt->execute([$user_id]);
    $contacts = $stmt->fetchAll();
}

header('Content-Type: application/json');
echo json_encode($contacts);
?>