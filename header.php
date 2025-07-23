<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Contact Directory</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">

<header class="bg-white shadow">
  <div class="max-w-6xl mx-auto px-4 py-4 flex justify-between items-center">
    <h1 class="text-2xl font-bold text-green-700">Contact Directory</h1>

    <nav class="flex items-center space-x-6">
      <a href="dashboard.php" class="text-green-700 hover:underline">Dashboard</a>
      <a href="my_contacts.php" class="text-green-700 hover:underline">My Contacts</a>
      <a href="add_contact.php" class="text-green-700 hover:underline">Add Contact</a>

      <div class="relative">
        <button id="settingsButton" type="button" class="text-green-700 hover:underline flex items-center gap-1">
          Settings
          <svg class="w-4 h-4 inline-block" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.293l3.71-4.06a.75.75 0 111.1 1.02l-4.25 4.66a.75.75 0 01-1.1 0l-4.25-4.66a.75.75 0 01.02-1.06z" clip-rule="evenodd"/>
          </svg>
        </button>

        <div id="settingsMenu" class="hidden absolute right-0 mt-2 w-48 bg-white border border-gray-200 rounded-md shadow-lg z-50">
          <a href="about.php" class="block px-4 py-2 text-green-700 hover:bg-gray-100">About Me</a>
          <a href="edit_profile.php" class="block px-4 py-2 text-green-700 hover:bg-gray-100">Update Info</a>
          <a href="change_password.php" class="block px-4 py-2 text-green-700 hover:bg-gray-100">Change Password</a>
          <a href="logout.php" class="block px-4 py-2 text-green-700 hover:bg-gray-100">Logout</a>
        </div>
      </div>
    </nav>
  </div>
</header>

<script>
document.getElementById("settingsButton").addEventListener("click", () => {
  document.getElementById("settingsMenu").classList.toggle("hidden");
});
</script>
