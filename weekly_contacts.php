<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$type = $_GET['type'] ?? 'weekly';

$end_date = $_GET['end_date'] ?? date('Y-m-d');
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-6 days'));

$contacts = [];

try {
    $query = "SELECT contact_id, first_name, last_name, phone, email, created_at 
              FROM contacts 
              WHERE user_id = :user_id 
                AND DATE(created_at) BETWEEN :start_date AND :end_date 
              ORDER BY created_at DESC";

    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindParam(':start_date', $start_date);
    $stmt->bindParam(':end_date', $end_date);
    $stmt->execute();

    $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $error_message = "Error loading contacts. Please try again later.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Weekly Contacts - Contact Directory</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">

<?php include 'includes/header.php'; ?>

<main class="max-w-6xl mx-auto px-4 py-6">
    <?php if (isset($error_message)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?= htmlspecialchars($error_message) ?>
        </div>
    <?php endif; ?>

    <h2 class="text-2xl font-bold text-gray-800 mb-4">Weekly Contacts</h2>

    <!-- Filter Form -->
    <form method="get" class="bg-white p-4 rounded shadow mb-6 flex flex-col md:flex-row gap-4 md:items-end">
        <input type="hidden" name="type" value="<?= htmlspecialchars($type) ?>">

        <div>
            <label class="block text-sm text-gray-700 mb-1">Start Date</label>
            <input type="date" name="start_date" value="<?= htmlspecialchars($start_date) ?>" class="border p-2 rounded w-full">
        </div>

        <div>
            <label class="block text-sm text-gray-700 mb-1">End Date</label>
            <input type="date" name="end_date" value="<?= htmlspecialchars($end_date) ?>" class="border p-2 rounded w-full">
        </div>

        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition h-[42px]">
            Filter
        </button>

        <a href="dashboard.php" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700 transition h-[42px] flex items-center justify-center">
            ← Back
        </a>
    </form>

    <?php if (!empty($contacts)): ?>
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="p-4 bg-gray-50 border-b">
                <p class="text-sm text-gray-600">
                    Showing contacts from <strong><?= htmlspecialchars(date('M j, Y', strtotime($start_date))) ?></strong>
                    to <strong><?= htmlspecialchars(date('M j, Y', strtotime($end_date))) ?></strong>
                    (<?= count($contacts) ?> records)
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full table-auto">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Name</th>
                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Phone</th>
                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Email</th>
                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($contacts as $contact): ?>
                            <tr class="hover:bg-gray-50 cursor-pointer" onclick="window.location='detailed_contacts.php?id=<?= $contact['contact_id'] ?>'">
                                <td class="px-4 py-2">
                                    <?= htmlspecialchars($contact['first_name'] . ' ' . $contact['last_name']) ?>
                                </td>
                                <td class="px-4 py-2"><?= htmlspecialchars($contact['phone']) ?></td>
                                <td class="px-4 py-2"><?= htmlspecialchars($contact['email']) ?></td>
                                <td class="px-4 py-2"><?= htmlspecialchars(date('M j, Y g:i A', strtotime($contact['created_at']))) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php else: ?>
        <div class="bg-white shadow rounded-lg p-6 text-center">
            <p class="text-gray-500">No contacts found for the selected date range.</p>
        </div>
    <?php endif; ?>
</main>

</body>
</html>
