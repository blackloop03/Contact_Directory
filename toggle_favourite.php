<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$contact_id = $_POST['contact_id'] ?? null;
$user_id = $_SESSION['user_id'];

if (!$contact_id) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid contact']);
    exit;
}

try {
    // Check if already favorited
    $stmt = $pdo->prepare("SELECT * FROM fav_contacts WHERE contact_id = ? AND user_id = ?");
    $stmt->execute([$contact_id, $user_id]);
    $is_favorite = $stmt->fetch();

    if ($is_favorite) {
        // Remove from favorites
        $stmt = $pdo->prepare("DELETE FROM fav_contacts WHERE contact_id = ? AND user_id = ?");
        $stmt->execute([$contact_id, $user_id]);
        echo json_encode(['success' => true, 'status' => 'removed']);
    } else {
        // Add to favorites
        $stmt = $pdo->prepare("INSERT INTO fav_contacts (contact_id, user_id) VALUES (?, ?)");
        $stmt->execute([$contact_id, $user_id]);
        echo json_encode(['success' => true, 'status' => 'added']);
    }
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database error']);
}