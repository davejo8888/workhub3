<?php
// This file: workhub/includes/header.php

// Ensure session is started and necessary files are included if they haven't been already
// This is often handled by a central 'bootstrap' or 'init' file, or at the top of each main page.
// For simplicity here, we'll assume core things like session and isLoggedIn are available.
// If not, you'd require_once 'auth.php'; here or ensure it's done before including this header.

// You might want to pass the page title as a variable from the main page
// Example: $pageTitle = "Dashboard"; (set this before including header.php)
if (!isset($pageTitle)) {
    $pageTitle = "MyWorkHub"; // Default title
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - MyWorkHub</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css">
    <style>
        /* You can include very minimal, critical global styles here or link a CSS file */
        body {
            font-family: 'Poppins', sans-serif; /* Example font from login.php */
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen"> <?php // Consider making body class dynamic if needed ?>

<header class="bg-white shadow-md">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
        <a href="<?php echo isLoggedIn() ? 'dashboard.php' : 'index.php'; ?>" class="text-xl font-bold text-indigo-600">
            MyWorkHub
        </a>
        <nav class="space-x-4">
            <?php if (isLoggedIn()): ?>
                <a href="dashboard.php" class="text-gray-600 hover:text-indigo-600 <?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'text-indigo-600 font-semibold' : ''; ?>">
                    Dashboard
                </a>
                <a href="profile.php" class="text-gray-600 hover:text-indigo-600 <?php echo (basename($_SERVER['PHP_SELF']) == 'profile.php') ? 'text-indigo-600 font-semibold' : ''; ?>">
                    Profile
                </a>
                <?php
                // Assuming you have a users.php for listing users if admin
                if (hasRole('admin')) { // Assuming hasRole() function exists in your auth.php or functions.php
                    // You'd need to create a page for user management, e.g., manage_users.php
                    // echo '<a href="manage_users.php" class="text-gray-600 hover:text-indigo-600">Manage Users</a>';
                }
                ?>
                <a href="logout.php" class="text-gray-600 hover:text-indigo-600">
                    <i class="fas fa-sign-out-alt mr-1"></i> Logout
                </a>
            <?php else: ?>
                <a href="login.php" class="text-gray-600 hover:text-indigo-600 <?php echo (basename($_SERVER['PHP_SELF']) == 'login.php') ? 'text-indigo-600 font-semibold' : ''; ?>">
                    Login
                </a>
                <a href="register.php" class="text-gray-600 hover:text-indigo-600 <?php echo (basename($_SERVER['PHP_SELF']) == 'register.php') ? 'text-indigo-600 font-semibold' : ''; ?>">
                    Register
                </a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<main class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">