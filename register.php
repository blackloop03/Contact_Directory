<?php
require 'config.php';

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $username = trim($_POST['username']);
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validation
    if (empty($first_name) || empty($last_name) || empty($username) || empty($email) || empty($phone) || empty($password) || empty($confirm_password)) {
        $errors[] = 'All fields are required.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email address.';
    }

    if (!preg_match('/^\d{10}$/', $phone)) {
        $errors[] = 'Phone number must be exactly 10 digits.';
    }

    if (ctype_digit($first_name) || ctype_digit($last_name)) {
        $errors[] = 'Name cannot contain only numbers.';
    }

    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username OR email = :email");
        $stmt->execute(['username' => $username, 'email' => $email]);
        if ($stmt->fetch()) {
            $errors[] = 'Username or email already exists.';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, username, password, email) VALUES (:first_name, :last_name, :username, :password, :email)");
            $stmt->execute([
                'first_name' => $first_name,
                'last_name'  => $last_name,
                'username'   => $username,
                'password'   => $hashed_password,
                'email'      => $email
            ]);

            $success = true;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sign Up - Contact Directory</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>
  .bg-darkgreen   { background-color: #003D2E; }
  .bg-tealgreen   { background-color: #4D9C8D; }
  .bg-beige       { background-color: #DDD1A7; }
  .bg-cream       { background-color: #FFFAEB; }

  .text-darkgreen { color: #003D2E; }
  .text-tealgreen { color: #4D9C8D; }
  .text-beige     { color: #DDD1A7; }
  .text-cream     { color: #FFFAEB; }
</style>
</head>
<body class="bg-cream">

<!-- Custom Header -->
<header class="bg-white shadow">
  <div class="max-w-6xl mx-auto px-4 py-4 flex justify-between items-center">
    <h1 class="text-2xl font-bold text-darkgreen">Contact Directory</h1>
    <a href="login.php" class="text-darkgreen hover:underline">Login</a>
  </div>
</header>

<div class="max-w-6xl mx-auto px-4 py-2">
  <a href="index.php" class="text-tealgreen hover:underline">&larr; Go Back</a>
</div>

<div class="min-h-screen bg-cream flex flex-col justify-center items-center py-6 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8 bg-white shadow-lg rounded-lg p-8">
        <h2 class="text-center text-3xl font-extrabold text-darkgreen">Create your account</h2>

        <?php if (!empty($errors)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                <?php foreach ($errors as $error): ?>
                    <p><?= htmlspecialchars($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php elseif ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                Registration successful! <a href="login.php" class="text-tealgreen underline">Login here</a>.
            </div>
        <?php endif; ?>

        <form class="mt-6 space-y-4" method="post" action="">
            <div>
                <label class="block text-darkgreen font-medium">First Name</label>
                <input name="first_name" type="text" required 
                    class="appearance-none rounded w-full px-3 py-2 border border-gray-300 focus:outline-none focus:ring-tealgreen">
            </div>
            <div>
                <label class="block text-darkgreen font-medium">Last Name</label>
                <input name="last_name" type="text" required 
                    class="appearance-none rounded w-full px-3 py-2 border border-gray-300 focus:outline-none focus:ring-tealgreen">
            </div>
            <div>
                <label class="block text-darkgreen font-medium">Username</label>
                <input name="username" type="text" required 
                    class="appearance-none rounded w-full px-3 py-2 border border-gray-300 focus:outline-none focus:ring-tealgreen">
            </div>
            <div>
                <label class="block text-darkgreen font-medium">Email</label>
                <input name="email" type="email" required 
                    class="appearance-none rounded w-full px-3 py-2 border border-gray-300 focus:outline-none focus:ring-tealgreen">
            </div>
            <div>
                <label class="block text-darkgreen font-medium">Phone Number</label>
                <input name="phone" type="text" required maxlength="10"
                    class="appearance-none rounded w-full px-3 py-2 border border-gray-300 focus:outline-none focus:ring-tealgreen">
            </div>
            <div>
                <label class="block text-darkgreen font-medium">Password</label>
                <input name="password" type="password" required 
                    class="appearance-none rounded w-full px-3 py-2 border border-gray-300 focus:outline-none focus:ring-tealgreen">
            </div>
            <div>
                <label class="block text-darkgreen font-medium">Confirm Password</label>
                <input name="confirm_password" type="password" required 
                    class="appearance-none rounded w-full px-3 py-2 border border-gray-300 focus:outline-none focus:ring-tealgreen">
            </div>

            <div>
                <button type="submit" 
                    class="group relative w-full flex justify-center py-2 px-4 text-sm font-medium rounded bg-tealgreen text-white hover:opacity-90 focus:outline-none">
                    Sign Up
                </button>
            </div>
        </form>

        <p class="mt-4 text-center text-sm text-darkgreen">
            Already have an account? 
            <a href="login.php" class="font-medium text-tealgreen hover:underline">Log in</a>
        </p>
    </div>
</div>

</body>
</html>
