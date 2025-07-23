<?php
session_start();
require 'config.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$errors = [];
$success = "";

// Fetch current user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header("Location: logout.php");
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $username = trim($_POST['username']);

    // Validation
    if (empty($first_name)) {
        $errors[] = 'First name is required';
    }

    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    }

    // Check if username is taken (excluding current user)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? AND user_id != ?");
    $stmt->execute([$username, $user_id]);
    if ($stmt->fetchColumn() > 0) {
        $errors[] = 'Username already taken';
    }

    // Check if email is taken (excluding current user)
    if (!empty($email)) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND user_id != ?");
        $stmt->execute([$email, $user_id]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'Email already registered';
        }
    }

    if (empty($errors)) {
        // Update database
        $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, username = ? WHERE user_id = ?");
        $stmt->execute([$first_name, $last_name, $email, $username, $user_id]);

        $_SESSION['username'] = $username;
        $success = "Profile updated successfully!";
        
        // Refresh user data
        $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

function safe($val) {
    return htmlspecialchars($val ?? '', ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - Contact Directory</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'cream': '#FEF7ED',
                        'darkgreen': '#166534',
                        'tealgreen': '#0D9488',
                        'lightgreen': '#DCFCE7',
                        'mediumgreen': '#22C55E'
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gradient-to-br from-cream via-orange-50 to-emerald-50 min-h-screen">
    <?php include 'includes/header.php'; ?>

    <main class="max-w-4xl mx-auto px-4 py-8">
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
            <!-- Page Header -->
            <div class="bg-gradient-to-r from-tealgreen to-darkgreen p-6 text-white">
                <h1 class="text-3xl font-bold">Edit Profile</h1>
                <p class="opacity-90">Update your account information</p>
            </div>

            <!-- Form Content -->
            <div class="p-8">
                <?php if (!empty($errors)): ?>
                    <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6 rounded-r-lg">
                        <div class="flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-400 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <h3 class="font-semibold text-red-700">Please fix the following issues:</h3>
                        </div>
                        <ul class="mt-2 list-disc list-inside text-red-600">
                            <?php foreach ($errors as $error): ?>
                                <li><?= safe($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php elseif (!empty($success)): ?>
                    <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-6 rounded-r-lg">
                        <div class="flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-400 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <p class="font-semibold text-green-700"><?= safe($success) ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Personal Information -->
                        <div class="bg-gray-50 p-6 rounded-xl">
                            <h3 class="text-xl font-semibold text-darkgreen mb-4 flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                Personal Information
                            </h3>
                            <div class="space-y-4">
                                <div>
                                    <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">First Name *</label>
                                    <input type="text" id="first_name" name="first_name" required
                                           value="<?= safe($user['first_name']) ?>"
                                           class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-tealgreen focus:outline-none transition">
                                </div>
                                <div>
                                    <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                                    <input type="text" id="last_name" name="last_name"
                                           value="<?= safe($user['last_name']) ?>"
                                           class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-tealgreen focus:outline-none transition">
                                </div>
                            </div>
                        </div>

                        <!-- Account Information -->
                        <div class="bg-gray-50 p-6 rounded-xl">
                            <h3 class="text-xl font-semibold text-darkgreen mb-4 flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                Account Information
                            </h3>
                            <div class="space-y-4">
                                <div>
                                    <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Username *</label>
                                    <input type="text" id="username" name="username" required
                                           value="<?= safe($user['username']) ?>"
                                           class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-tealgreen focus:outline-none transition">
                                </div>
                                <div>
                                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                    <input type="email" id="email" name="email"
                                           value="<?= safe($user['email']) ?>"
                                           class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-tealgreen focus:outline-none transition">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-col sm:flex-row gap-4 pt-4">
                        <button type="submit" class="btn-primary bg-tealgreen hover:bg-darkgreen text-white px-6 py-3 rounded-xl font-semibold flex-1 sm:flex-initial transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline mr-2" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M7.707 10.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V6h5a2 2 0 012 2v7a2 2 0 01-2 2H4a2 2 0 01-2-2V8a2 2 0 012-2h5v5.586l-1.293-1.293zM9 4a1 1 0 012 0v2H9V4z" />
                            </svg>
                            Save Changes
                        </button>
                        <a href="about.php" class="btn-secondary bg-gray-200 hover:bg-gray-300 text-gray-800 px-6 py-3 rounded-xl font-semibold text-center flex-1 sm:flex-initial transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline mr-2" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                            </svg>
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>
</html>