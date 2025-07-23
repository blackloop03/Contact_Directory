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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = $_POST['first_name'] ?? '';
    $last_name  = $_POST['last_name'] ?? '';
    $email      = $_POST['email'] ?? '';
    $phone      = $_POST['phone'] ?? '';
    $address    = $_POST['address'] ?? '';
    $company    = $_POST['company'] ?? '';
    $notes      = $_POST['notes'] ?? '';

    $avatar_url = $contact['avatar_url'];

    // Handle avatar upload
    if (!empty($_FILES['avatar']['name'])) {
        $target_dir = "uploads/";
        $filename = uniqid() . "_" . basename($_FILES["avatar"]["name"]);
        $target_file = $target_dir . $filename;

        if (move_uploaded_file($_FILES["avatar"]["tmp_name"], $target_file)) {
            $avatar_url = $target_file;
        }
    }

    $stmt = $pdo->prepare("
        UPDATE contacts
        SET first_name = ?, last_name = ?, email = ?, phone = ?, address = ?, company = ?, notes = ?, avatar_url = ?, updated_at = NOW()
        WHERE contact_id = ? AND user_id = ?
    ");
    $stmt->execute([
        $first_name, $last_name, $email, $phone, $address, $company, $notes, $avatar_url,
        $contact_id, $user_id
    ]);

    header("Location: detailed_contacts.php?id={$contact_id}");
    exit;
}

include 'includes/header.php';
?>

<div class="max-w-4xl mx-auto p-6 bg-white shadow rounded">
    <h1 class="text-2xl font-bold text-green-700 mb-4">Edit Contact</h1>

    <form method="post" enctype="multipart/form-data" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block">First Name</label>
                <input type="text" name="first_name" value="<?= htmlspecialchars($contact['first_name']) ?>" class="w-full border p-2 rounded">
            </div>
            <div>
                <label class="block">Last Name</label>
                <input type="text" name="last_name" value="<?= htmlspecialchars($contact['last_name']) ?>" class="w-full border p-2 rounded">
            </div>
            <div>
                <label class="block">Email</label>
                <input type="email" name="email" value="<?= htmlspecialchars($contact['email']) ?>" class="w-full border p-2 rounded">
            </div>
            <div>
                <label class="block">Phone</label>
                <input type="text" name="phone" value="<?= htmlspecialchars($contact['phone']) ?>" class="w-full border p-2 rounded">
            </div>
            <div>
                <label class="block">Address</label>
                <input type="text" name="address" value="<?= htmlspecialchars($contact['address']) ?>" class="w-full border p-2 rounded">
            </div>
            <div>
                <label class="block">Company</label>
                <input type="text" name="company" value="<?= htmlspecialchars($contact['company']) ?>" class="w-full border p-2 rounded">
            </div>
        </div>

        <div>
            <label class="block">Notes</label>
            <textarea name="notes" class="w-full border p-2 rounded"><?= htmlspecialchars($contact['notes']) ?></textarea>
        </div>

        <div>
            <label class="block">Avatar</label>
            <?php if (!empty($contact['avatar_url'])): ?>
                <img src="<?= htmlspecialchars($contact['avatar_url']) ?>" alt="Avatar" class="w-20 h-20 rounded-full mb-2">
            <?php endif; ?>
            <input type="file" name="avatar" class="block">
        </div>

        <div class="flex gap-4">
            <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">Save Changes</button>
            <a href="my_contacts.php" class="bg-gray-300 px-4 py-2 rounded">Cancel</a>
        </div>
    </form>
</div>

<?php include 'includes/footer.php'; ?>
