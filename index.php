<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Contact Directory</title>
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
    <div class="space-x-4">
      <a href="login.php" class="text-darkgreen hover:underline">Login</a>
      <a href="register.php" class="bg-tealgreen text-white py-1 px-3 rounded hover:opacity-90">Sign Up</a>
    </div>
  </div>
</header>

<main class="pt-6 px-4">
<div class="min-h-screen flex flex-col justify-center items-center">
    <div class="bg-white shadow-lg rounded-lg max-w-3xl w-full p-8">
        <h1 class="text-4xl font-bold mb-4 text-darkgreen text-center">Welcome to Contact Directory</h1>
        <p class="text-lg text-center text-darkgreen mb-6">
            Keep your contacts organized, accessible, and secure — all in one place.  
            Register today and take control of your connections!
        </p>
        <div class="flex justify-center space-x-4">
            <a href="login.php" class="bg-tealgreen text-white font-semibold py-2 px-6 rounded shadow hover:opacity-90">Login</a>
            <a href="register.php" class="bg-beige text-darkgreen font-semibold py-2 px-6 rounded shadow hover:opacity-90">Sign Up</a>
        </div>
    </div>

    <div class="mt-12 grid grid-cols-1 md:grid-cols-3 gap-6 max-w-5xl">
        <div class="bg-white shadow rounded-lg p-6 text-center">
            <h2 class="text-xl font-semibold text-darkgreen mb-2">Secure</h2>
            <p class="text-darkgreen">Your contact data is safe and secure, accessible only to you.</p>
        </div>
        <div class="bg-white shadow rounded-lg p-6 text-center">
            <h2 class="text-xl font-semibold text-darkgreen mb-2">Organized</h2>
            <p class="text-darkgreen">Keep all your personal and professional contacts neatly organized.</p>
        </div>
        <div class="bg-white shadow rounded-lg p-6 text-center">
            <h2 class="text-xl font-semibold text-darkgreen mb-2">Easy Access</h2>
            <p class="text-darkgreen">Log in from anywhere to access your contact directory.</p>
        </div>
    </div>
</div>
</main>

<!-- Footer -->
<footer class="text-center text-sm text-gray-500 mt-10 py-4">
  &copy; <?= date('Y') ?> Contact Directory. All rights reserved.
</footer>

</body>
</html>
