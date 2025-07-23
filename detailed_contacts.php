<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$contact_id = $_GET['id'] ?? null;

if (!$contact_id) {
    header("Location: my_contacts.php");
    exit;
}

// Fetch contact
$stmt = $pdo->prepare("SELECT * FROM contacts WHERE contact_id = ? AND user_id = ?");
$stmt->execute([$contact_id, $user_id]);
$contact = $stmt->fetch();

if (!$contact) {
    header("Location: my_contacts.php");
    exit;
}

// Generate vCard string
$vcard = "BEGIN:VCARD\n";
$vcard .= "VERSION:3.0\n";
$vcard .= "FN:{$contact['first_name']} {$contact['last_name']}\n";
if (!empty($contact['email'])) {
    $vcard .= "EMAIL:{$contact['email']}\n";
}
if (!empty($contact['phone'])) {
    $vcard .= "TEL:{$contact['phone']}\n";
}
if (!empty($contact['address'])) {
    $vcard .= "ADR:{$contact['address']}\n";
}
if (!empty($contact['company'])) {
    $vcard .= "ORG:{$contact['company']}\n";
}
$vcard .= "END:VCARD";

// Encode vCard for QR API
$vcard_encoded = urlencode($vcard);
$qr_url = "https://api.qrserver.com/v1/create-qr-code/?data={$vcard_encoded}&size=200x200";

include 'includes/header.php';
?>

<div class="max-w-4xl mx-auto p-6 bg-white shadow rounded">
    <div class="flex flex-col md:flex-row gap-6">
        <div>
            <?php if (!empty($contact['avatar_url'])): ?>
                <img src="<?= htmlspecialchars($contact['avatar_url']) ?>" alt="Avatar" class="w-32 h-32 rounded-full object-cover">
            <?php else: ?>
                <?php $initials = strtoupper(substr($contact['first_name'], 0, 1) . substr($contact['last_name'], 0, 1)); ?>
                <div class="w-32 h-32 bg-gray-300 text-green-700 rounded-full flex items-center justify-center text-4xl">
                    <?= htmlspecialchars($initials) ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="flex-1">
            <h1 class="text-3xl font-bold text-green-700 mb-2">
                <?= htmlspecialchars($contact['first_name'] . ' ' . $contact['last_name']) ?>
            </h1>
            <?php if (!empty($contact['email'])): ?>
                <p class="text-gray-600 mb-1"><strong>Email:</strong> <?= htmlspecialchars($contact['email']) ?></p>
            <?php endif; ?>
            <?php if (!empty($contact['phone'])): ?>
                <p class="text-gray-600 mb-1"><strong>Phone:</strong> <?= htmlspecialchars($contact['phone']) ?></p>
            <?php endif; ?>
            <?php if (!empty($contact['address'])): ?>
                <p class="text-gray-600 mb-1"><strong>Address:</strong> <?= htmlspecialchars($contact['address']) ?></p>
            <?php endif; ?>
            <?php if (!empty($contact['company'])): ?>
                <p class="text-gray-600 mb-1"><strong>Company:</strong> <?= htmlspecialchars($contact['company']) ?></p>
            <?php endif; ?>
            <?php if (!empty($contact['notes'])): ?>
                <p class="text-gray-600 mb-1"><strong>Notes:</strong> <?= htmlspecialchars($contact['notes']) ?></p>
            <?php endif; ?>
            <p class="text-gray-600 mb-1"><strong>Created:</strong> <?= htmlspecialchars($contact['created_at']) ?></p>
            <p class="text-gray-600 mb-1"><strong>Last Updated:</strong> <?= htmlspecialchars($contact['updated_at']) ?></p>
        </div>

        <div class="flex flex-col items-center">
            <img src="<?= $qr_url ?>" alt="QR Code" class="w-32 h-32">
            <p class="text-sm text-gray-500 mt-2 text-center">Scan to save contact</p>
        </div>
    </div>

    <div class="mt-6 flex gap-4">
        <a href="edit_contact.php?id=<?= htmlspecialchars($contact['contact_id']) ?>" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Edit</a>
        <button onclick="showDeleteConfirm()" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">Delete</button>
        <a href="my_contacts.php" class="bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400">Back</a>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="delete-confirm-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden z-50">
        <div class="bg-white p-6 rounded-xl max-w-md w-full">
            <h3 class="text-lg font-semibold text-gray-700 mb-4">Are you sure you want to delete this contact?</h3>
            <p class="text-gray-600 mb-6">This action cannot be undone.</p>
            <div class="flex justify-end gap-4">
                <button onclick="cancelDelete()" class="bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400">Cancel</button>
                <form action="delete_contact.php" method="post" style="display: inline;">
                    <input type="hidden" name="id" value="<?= htmlspecialchars($contact['contact_id']) ?>">
                    <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">Confirm</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function showDeleteConfirm() {
    document.getElementById('delete-confirm-modal').classList.remove('hidden');
}

function cancelDelete() {
    document.getElementById('delete-confirm-modal').classList.add('hidden');
}
</script>

<?php include 'includes/footer.php'; ?>