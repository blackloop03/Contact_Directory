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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate current password
    $stmt = $pdo->prepare("SELECT password FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!password_verify($current_password, $user['password'])) {
        $errors[] = 'Current password is incorrect';
    }

    // Validate new password
    if (empty($new_password)) {
        $errors[] = 'New password is required';
    } elseif (strlen($new_password) < 8) {
        $errors[] = 'Password must be at least 8 characters';
    }

    if ($new_password !== $confirm_password) {
        $errors[] = 'New passwords do not match';
    }

    if (empty($errors)) {
        // Update password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
        $stmt->execute([$hashed_password, $user_id]);

        $success = "Password updated successfully!";
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
    <title>Change Password - Contact Directory</title>
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
                <h1 class="text-3xl font-bold">Change Password</h1>
                <p class="opacity-90">Update your account security</p>
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
                    <!-- Current Password -->
                    <div class="bg-gray-50 p-6 rounded-xl">
                        <h3 class="text-xl font-semibold text-darkgreen mb-4 flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                            Password Change
                        </h3>
                        <div class="space-y-4">
                            <div>
                                <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">Current Password *</label>
                                <input type="password" id="current_password" name="current_password" required
                                       class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-tealgreen focus:outline-none transition">
                            </div>
                            <div>
                                <label for="new_password" class="block text-sm font-medium text-gray-700 mb-1">New Password *</label>
                                <input type="password" id="new_password" name="new_password" required
                                       class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-tealgreen focus:outline-none transition">
                                <p class="text-xs text-gray-500 mt-1">Must be at least 8 characters</p>
                            </div>
                            <div>
                                <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password *</label>
                                <input type="password" id="confirm_password" name="confirm_password" required
                                       class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-tealgreen focus:outline-none transition">
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-col sm:flex-row gap-4 pt-4">
                        <button type="submit" class="btn-primary bg-tealgreen hover:bg-darkgreen text-white px-6 py-3 rounded-xl font-semibold flex-1 sm:flex-initial transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline mr-2" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                            </svg>
                            Update Password
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

    <script>
        // Password visibility toggle
        document.addEventListener('DOMContentLoaded', function() {
            const passwordFields = document.querySelectorAll('input[type="password"]');
            passwordFields.forEach(field => {
                const parent = field.parentElement;
                const toggle = document.createElement('span');
                toggle.innerHTML = '👁️';
                toggle.className = 'absolute right-3 top-1/2 transform -translate-y-1/2 cursor-pointer';
                toggle.addEventListener('click', () => {
                    if (field.type === 'password') {
                        field.type = 'text';
                        toggle.innerHTML = '👁️‍🗨️';
                    } else {
                        field.type = 'password';
                        toggle.innerHTML = '👁️';
                    }
                });
                parent.classList.add('relative');
                parent.appendChild(toggle);
            });
        });
    </script>
</body>
</html>