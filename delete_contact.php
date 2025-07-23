<?php
session_start();
require 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Get the contact id from POST
$contact_id = $_POST['id'] ?? 0;

// Validate
if (!$contact_id || !is_numeric($contact_id)) {
    header("Location: my_contacts.php");
    exit;
}

// Delete from main contacts
$stmt1 = $pdo->prepare("DELETE FROM contacts WHERE contact_id = ? AND user_id = ?");
$stmt1->execute([$contact_id, $user_id]);

// Delete from favorites table too (if exists)
$stmt2 = $pdo->prepare("DELETE FROM fav_contacts WHERE contact_id = ? AND user_id = ?");
$stmt2->execute([$contact_id, $user_id]);

// Set success message
$_SESSION['success_message'] = "Contact deleted successfully!";

// Redirect back
header("Location: my_contacts.php?deleted=1");
exit;
?>