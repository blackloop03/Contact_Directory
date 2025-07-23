<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Search query
$search_query = trim($_GET['query'] ?? '');

// Fetch total contacts
$stmt = $pdo->prepare("SELECT COUNT(*) FROM contacts WHERE user_id = ?");
$stmt->execute([$user_id]);
$total_contacts = $stmt->fetchColumn();

// Fetch recently added contacts or search results
if ($search_query !== '') {
    $stmt = $pdo->prepare("
        SELECT * FROM contacts 
        WHERE user_id = ? AND 
        (first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR phone LIKE ?)
        ORDER BY created_at DESC
    ");
    $search_term = "%{$search_query}%";
    $stmt->execute([$user_id, $search_term, $search_term, $search_term, $search_term]);
    $recent_contacts = $stmt->fetchAll();
} else {
    $stmt = $pdo->prepare("SELECT * FROM contacts WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
    $stmt->execute([$user_id]);
    $recent_contacts = $stmt->fetchAll();
}

// Added this week
$stmt = $pdo->prepare("SELECT COUNT(*) FROM contacts WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$stmt->execute([$user_id]);
$added_this_week = $stmt->fetchColumn();

// Edited this week
$stmt = $pdo->prepare("SELECT COUNT(*) FROM contacts WHERE user_id = ? AND updated_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$stmt->execute([$user_id]);
$edited_this_week = $stmt->fetchColumn();

$last_login = $_SESSION['last_login'] ?? date("Y-m-d H:i:s");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard - Contact Directory</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>
  .bg-darkgreen   { background-color: #003D2E; }
  .bg-tealgreen   { background-color: #4D9C8D; }
  .bg-beige       { background-color: #DDD1A7; }
  .bg-cream       { background-color: #FFFAEB; }
</style>
</head>
<body class="bg-cream">

<?php include 'includes/header.php'; ?>

<main class="max-w-6xl mx-auto px-4 py-6 space-y-6">

    <h2 class="text-3xl font-bold text-darkgreen mb-6">Dashboard</h2>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white shadow rounded-lg p-4 text-center">
            <p class="text-lg font-medium text-darkgreen">Total Contacts</p>
            <p class="text-2xl font-bold text-tealgreen"><?= htmlspecialchars($total_contacts) ?></p>
        </div>

        <div class="bg-white shadow rounded-lg p-4 text-center">
            <p class="text-lg font-medium text-darkgreen">Last Login</p>
            <p class="text-sm text-tealgreen"><?= htmlspecialchars($last_login) ?></p>
        </div>

        <a href="weekly_contacts.php?type=added" class="bg-white shadow rounded-lg p-4 text-center hover:shadow-lg transition">
            <p class="text-lg font-medium text-darkgreen">Added This Week</p>
            <p class="text-2xl font-bold text-tealgreen"><?= htmlspecialchars($added_this_week) ?></p>
            <p class="text-xs text-gray-500 mt-1">Click to view & filter</p>
        </a>

        <a href="weekly_contacts.php?type=edited" class="bg-white shadow rounded-lg p-4 text-center hover:shadow-lg transition">
            <p class="text-lg font-medium text-darkgreen">Edited This Week</p>
            <p class="text-2xl font-bold text-tealgreen"><?= htmlspecialchars($edited_this_week) ?></p>
            <p class="text-xs text-gray-500 mt-1">Click to view & filter</p>
        </a>
    </div>

    <!-- Search -->
    <form method="get" class="bg-white shadow rounded-lg p-4 flex flex-col md:flex-row gap-2">
        <input type="text" name="query" value="<?= htmlspecialchars($search_query) ?>" placeholder="Search by name, email, or phone…" class="flex-1 px-3 py-2 border rounded focus:outline-none">
        <button type="submit" class="bg-tealgreen text-white px-4 rounded hover:opacity-90">Search</button>
    </form>

    <!-- Recently Added / Search Results -->
    <div class="bg-white shadow rounded-lg p-4">
        <h3 class="text-xl font-bold text-darkgreen mb-4">
            <?= $search_query ? 'Search Results' : 'Recently Added Contacts' ?>
        </h3>

        <?php if (!empty($recent_contacts)): ?>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 text-left">
                            <th class="px-4 py-2">Avatar</th>
                            <th class="px-4 py-2">Name</th>
                            <th class="px-4 py-2">Email</th>
                            <th class="px-4 py-2">Phone</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_contacts as $contact): ?>
                            <tr class="hover:bg-gray-50 cursor-pointer" onclick="window.location='detailed_contacts.php?id=<?= $contact['contact_id'] ?>'">
                                <td class="px-4 py-2">
                                    <?php if (!empty($contact['avatar_url'])): ?>
                                        <img src="<?= htmlspecialchars($contact['avatar_url']) ?>" alt="Avatar" class="w-8 h-8 rounded-full">
                                    <?php else: ?>
                                        <div class="w-8 h-8 rounded-full bg-gray-300 flex items-center justify-center">
                                            <?= strtoupper(substr($contact['first_name'], 0, 1) . substr($contact['last_name'], 0, 1)) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-2"><?= htmlspecialchars($contact['first_name'] . ' ' . $contact['last_name']) ?></td>
                                <td class="px-4 py-2"><?= htmlspecialchars($contact['email']) ?></td>
                                <td class="px-4 py-2"><?= htmlspecialchars($contact['phone']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-gray-500">No contacts found.</p>
        <?php endif; ?>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <a href="add_contact.php" class="bg-tealgreen text-white p-4 rounded-lg shadow text-center hover:opacity-90">
            ➕ Add New Contact
        </a>
        <a href="edit_profile.php" class="bg-beige text-darkgreen p-4 rounded-lg shadow text-center hover:opacity-90">
            📝 Update My Info
        </a>
        <a href="change_password.php" class="bg-darkgreen text-white p-4 rounded-lg shadow text-center hover:opacity-90">
            🔐 Change Password
        </a>
    </div>

</main>

<footer class="text-center text-sm text-gray-500 mt-10 py-4">
  © <?= date('Y') ?> Contact Directory. All rights reserved.
</footer>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.querySelector('input[name="query"]');
    const tableBody = document.querySelector('tbody');

    searchInput.addEventListener('input', async (e) => {
        const searchTerm = e.target.value.trim();

        try {
            const response = await fetch(`search_contacts_dashboard.php?q=${encodeURIComponent(searchTerm)}`);
            const contacts = await response.json();

            // Clear existing rows
            tableBody.innerHTML = '';

            // Populate table with new data
            if (contacts.length > 0) {
                contacts.forEach(contact => {
                    const row = document.createElement('tr');
                    row.className = 'hover:bg-gray-50 cursor-pointer';
                    row.onclick = () => window.location = `detailed_contacts.php?id=${contact.contact_id}`;
                    row.innerHTML = `
                        <td class="px-4 py-2">
                            ${contact.avatar_url ? `<img src="${contact.avatar_url}" alt="Avatar" class="w-8 h-8 rounded-full">` : 
                            `<div class="w-8 h-8 rounded-full bg-gray-300 flex items-center justify-center">
                                ${contact.first_name.charAt(0).toUpperCase() + contact.last_name.charAt(0).toUpperCase()}
                            </div>`}
                        </td>
                        <td class="px-4 py-2">${contact.first_name} ${contact.last_name}</td>
                        <td class="px-4 py-2">${contact.email}</td>
                        <td class="px-4 py-2">${contact.phone}</td>
                    `;
                    tableBody.appendChild(row);
                });
            } else {
                tableBody.innerHTML = '<tr><td colspan="4" class="px-4 py-2 text-gray-500">No contacts found.</td></tr>';
            }
        } catch (error) {
            console.error('Error fetching contacts:', error);
            tableBody.innerHTML = '<tr><td colspan="4" class="px-4 py-2 text-gray-500">Error loading contacts.</td></tr>';
        }
    });

    // Optional: Handle form submission to preserve GET parameter
    document.querySelector('form').addEventListener('submit', (e) => {
        e.preventDefault(); // Prevent full page reload
    });
});
</script>

</body>
</html>