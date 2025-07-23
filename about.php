<?php
session_start();
require 'config.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Fetch user details
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// If user not found (shouldn't happen if session is valid)
if (!$user) {
    header("Location: logout.php");
    exit;
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
    <title>My Profile - Contact Directory</title>
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
                <h1 class="text-3xl font-bold">My Profile</h1>
                <p class="opacity-90">Account details and information</p>
            </div>

            <!-- Profile Content -->
            <div class="p-8">
                <!-- Profile Card -->
                <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden mb-8">
                    <div class="p-6">
                        <div class="flex flex-col md:flex-row items-start md:items-center gap-6">
                            <!-- Avatar Placeholder -->
                            <div class="w-24 h-24 bg-tealgreen rounded-full flex items-center justify-center text-white text-4xl">
                                <?= strtoupper(substr(safe($user['first_name']), 0, 1) . strtoupper(substr(safe($user['last_name']), 0, 1))) ?>
                            </div>

                            <!-- User Info -->
                            <div class="flex-1">
                                <h2 class="text-2xl font-bold text-gray-800">
                                    <?= safe($user['first_name']) ?> <?= safe($user['last_name']) ?>
                                </h2>
                                <p class="text-gray-600 mb-2">Member since <?= date('F Y', strtotime(safe($user['created_at']))) ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Details Section -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Account Details -->
                    <div class="bg-gray-50 p-6 rounded-xl">
                        <h3 class="text-xl font-semibold text-darkgreen mb-4 flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            Account Details
                        </h3>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Username</label>
                                <p class="mt-1 text-gray-800 font-medium"><?= safe($user['username']) ?></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Email</label>
                                <p class="mt-1 text-gray-800 font-medium"><?= safe($user['email']) ?></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Account Created</label>
                                <p class="mt-1 text-gray-800 font-medium">
                                    <?= date('F j, Y \a\t g:i A', strtotime(safe($user['created_at']))) ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Personal Details -->
                    <div class="bg-gray-50 p-6 rounded-xl">
                        <h3 class="text-xl font-semibold text-darkgreen mb-4 flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Personal Information
                        </h3>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-500">First Name</label>
                                <p class="mt-1 text-gray-800 font-medium"><?= safe($user['first_name']) ?></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Last Name</label>
                                <p class="mt-1 text-gray-800 font-medium"><?= safe($user['last_name']) ?></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500">User ID</label>
                                <p class="mt-1 text-gray-800 font-medium"><?= safe($user['user_id']) ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="mt-8 flex flex-col sm:flex-row gap-4">
                    <a href="edit_profile.php" class="btn-primary bg-tealgreen hover:bg-darkgreen text-white px-6 py-3 rounded-xl font-semibold text-center transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline mr-2" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                        </svg>
                        Edit Profile
                    </a>
                    <a href="change_password.php" class="btn-secondary bg-gray-200 hover:bg-gray-300 text-gray-800 px-6 py-3 rounded-xl font-semibold text-center transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline mr-2" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                        </svg>
                        Change Password
                    </a>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>
</html>