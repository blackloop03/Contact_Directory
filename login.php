<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require 'config.php';
echo '<pre>'; print_r($_SESSION); echo '</pre>';



$errors = [];

// If already logged in, redirect
if (isset($_SESSION['user_id'])) {
    header("Refresh:0; url=dashboard.php");
    exit;

}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $errors[] = 'Both fields are required.';
    } else {
        // Query user by email
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Login successful
            $_SESSION['user_id'] = $user['user_id'];

            $_SESSION['last_login'] = date("Y-m-d H:i:s");
            header("Refresh:0; url=dashboard.php");
            exit;

        } else {
            $errors[] = 'Invalid email or password.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - Contact Directory</title>
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
    <a href="register.php" class="text-darkgreen hover:underline">Sign Up</a>
  </div>
</header>

<div class="min-h-screen bg-cream flex flex-col justify-center items-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8 bg-white shadow-lg rounded-lg p-8">
        <h2 class="text-center text-3xl font-extrabold text-darkgreen">Log in to your account</h2>

        <?php if (!empty($errors)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php foreach ($errors as $error): ?>
                    <p><?= htmlspecialchars($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form class="space-y-6" method="post" action="">
            <div>
                <label class="block text-darkgreen font-medium">Email</label>
                <input name="email" type="email" required 
                    class="appearance-none rounded w-full px-3 py-2 border border-gray-300 focus:outline-none focus:ring-tealgreen">
            </div>
            <div class="relative">
                <label class="block text-darkgreen font-medium">Password</label>
                <input name="password" type="password" id="password" required 
                    class="appearance-none rounded w-full px-3 py-2 border border-gray-300 focus:outline-none focus:ring-tealgreen">
                <button type="button" onclick="togglePassword()" 
                    class="absolute right-3 top-8 text-sm text-tealgreen hover:underline">Show</button>
            </div>

            <div>
                <button type="submit" 
                    class="group relative w-full flex justify-center py-2 px-4 text-sm font-medium rounded bg-tealgreen text-white hover:opacity-90 focus:outline-none">
                    Login
                </button>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
function togglePassword() {
    const pwd = document.getElementById("password");
    const btn = event.target;
    if (pwd.type === "password") {
        pwd.type = "text";
        btn.textContent = "Hide";
    } else {
        pwd.type = "password";
        btn.textContent = "Show";
    }
}
</script>

</body>
</html>
