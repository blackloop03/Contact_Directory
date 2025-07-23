<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

function safe($val) {
    return htmlspecialchars($val ?? '', ENT_QUOTES, 'UTF-8');
}

function getContacts($pdo, $user_id, $search) {
    $stmt = $pdo->prepare("
        SELECT c.*, CASE WHEN f.contact_id IS NOT NULL THEN 1 ELSE 0 END as is_favorite 
        FROM contacts c 
        LEFT JOIN fav_contacts f ON c.contact_id = f.contact_id 
        WHERE c.user_id = ? AND c.phone LIKE ?
        ORDER BY c.first_name ASC
    ");
    $stmt->execute([$user_id, "$search%"]);
    return $stmt->fetchAll();
}

$search_term = $_GET['q'] ?? '';
$contacts = getContacts($pdo, $user_id, $search_term);

header('Content-Type: application/json');
echo json_encode($contacts);
?>