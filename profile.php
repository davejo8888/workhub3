<?php

// Added by repair script to define ROOT_PATH
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__FILE__));
}
// Start session and include necessary files
// Assuming your session management is in includes/auth.php
// and it initializes the session.
require_once 'includes/config.php'; // For any site-wide configurations
require_once 'includes/auth.php';   // For session management and isLoggedIn()
require_once 'includes/functions.php'; // For any utility functions if needed by the template

// Check if user is logged in, if not, redirect to login page
if (!isLoggedIn()) {
    redirect('login.php', ['type' => 'error', 'message' => 'Please log in to view your profile.']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile - MyWorkHub</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css">
    <style>
        /* Add any custom styles if needed */
        .form-input {
            transition: all 0.3s ease;
        }
        .form-input:focus {
            border-color: #4f46e5; /* Indigo-600 */
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.3);
        }
        .spinner {
            border: 4px solid rgba(0, 0, 0, .1);
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border-left-color: #4f46e5;
            animation: spin 1s ease infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body class="bg-gray-100">
    <header class="bg-white shadow-md">
        <div class="container mx-auto px-6 py-4 flex justify-between items-center">
            <a href="dashboard.php" class="text-xl font-bold text-indigo-600">MyWorkHub</a>
            <nav class="space-x-4">
                <a href="dashboard.php" class="text-gray-600 hover:text-indigo-600">Dashboard</a>
                <a href="profile.php" class="text-indigo-600 font-semibold">Profile</a>
                <a href="logout.php" class="text-gray-600 hover:text-indigo-600">Logout</a>
                 </nav>
        </div>
    </header>

    <main class="container mx-auto px-6 py-8">
        <div class="max-w-2xl mx-auto bg-white p-8 rounded-lg shadow-lg">
            <h2 class="text-2xl font-semibold text-gray-800 mb-6">My Profile</h2>

            <div id="loadingSpinner" class="flex justify-center items-center h-32">
                <div class="spinner"></div>
            </div>

            <div id="profileFormContainer" class="hidden">
                <div id="alertMessage" class="mb-4 p-4 rounded-md text-sm hidden"></div>

                <form id="profileForm" class="space-y-6">
                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700">Username</label>
                        <input type="text" id="username" name="username" readonly
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm bg-gray-100 cursor-not-allowed sm:text-sm"
                               placeholder="Username (cannot be changed)">
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
                        <input type="email" id="email" name="email" required
                               class="form-input mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none sm:text-sm"
                               placeholder="your.email@example.com">
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="first_name" class="block text-sm font-medium text-gray-700">First Name</label>
                            <input type="text" id="first_name" name="first_name"
                                   class="form-input mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none sm:text-sm"
                                   placeholder="Your first name">
                        </div>
                        <div>
                            <label for="last_name" class="block text-sm font-medium text-gray-700">Last Name</label>
                            <input type="text" id="last_name" name="last_name"
                                   class="form-input mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none sm:text-sm"
                                   placeholder="Your last name">
                        </div>
                    </div>

                    <div>
                        <label for="job_title" class="block text-sm font-medium text-gray-700">Job Title (Optional)</label>
                        <input type="text" id="job_title" name="job_title"
                               class="form-input mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none sm:text-sm"
                               placeholder="Your job title">
                    </div>

                    <div>
                        <label for="department" class="block text-sm font-medium text-gray-700">Department (Optional)</label>
                        <input type="text" id="department" name="department"
                               class="form-input mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none sm:text-sm"
                               placeholder="Your department">
                    </div>
                    
                    <hr class="my-8">
                    <h3 class="text-lg font-medium leading-6 text-gray-900">Change Password (Optional)</h3>

                    <div>
                        <label for="current_password" class="block text-sm font-medium text-gray-700">Current Password</label>
                        <input type="password" id="current_password" name="current_password"
                               class="form-input mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none sm:text-sm"
                               placeholder="Enter your current password">
                    </div>
                    <div>
                        <label for="new_password" class="block text-sm font-medium text-gray-700">New Password</label>
                        <input type="password" id="new_password" name="new_password"
                               class="form-input mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none sm:text-sm"
                               placeholder="Enter new password (min 8 characters)">
                    </div>
                    <div>
                        <label for="confirm_new_password" class="block text-sm font-medium text-gray-700">Confirm New Password</label>
                        <input type="password" id="confirm_new_password" name="confirm_new_password"
                               class="form-input mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none sm:text-sm"
                               placeholder="Confirm new password">
                    </div>

                    <div class="pt-2">
                        <button type="submit" id="saveProfileButton"
                                class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <footer class="bg-white border-t mt-12 py-6">
        <div class="container mx-auto px-6 text-center text-gray-500 text-sm">
            &copy; <?php echo date('Y'); ?> MyWorkHub. Created by Dr. Ahmed AL-sadi. All rights reserved.
        </div>
    </footer>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const profileForm = document.getElementById('profileForm');
    const alertMessage = document.getElementById('alertMessage');
    const saveProfileButton = document.getElementById('saveProfileButton');
    const loadingSpinner = document.getElementById('loadingSpinner');
    const profileFormContainer = document.getElementById('profileFormContainer');
    // const currentProfileImage = document.getElementById('currentProfileImage'); // For image display

    function showAlert(message, type = 'error') {
        alertMessage.textContent = message;
        alertMessage.classList.remove('hidden', 'bg-red-100', 'text-red-700', 'bg-green-100', 'text-green-700');
        if (type === 'error') {
            alertMessage.classList.add('bg-red-100', 'text-red-700');
        } else {
            alertMessage.classList.add('bg-green-100', 'text-green-700');
        }
        alertMessage.classList.remove('hidden');
    }

    // Fetch profile data on page load
    fetch('api/users.php?action=get_profile') // Adjust path if your API is elsewhere
        .then(response => response.json())
        .then(data => {
            loadingSpinner.classList.add('hidden');
            profileFormContainer.classList.remove('hidden');
            if (data.success && data.data) {
                const user = data.data;
                document.getElementById('username').value = user.username || '';
                document.getElementById('email').value = user.email || '';
                document.getElementById('first_name').value = user.first_name || '';
                document.getElementById('last_name').value = user.last_name || '';
                document.getElementById('job_title').value = user.job_title || '';
                document.getElementById('department').value = user.department || '';
                
                // If handling profile images:
                // if (user.profile_image) {
                //    currentProfileImage.src = user.profile_image; // Make sure this path is web-accessible
                //    currentProfileImage.classList.remove('hidden');
                // }
            } else {
                showAlert(data.message || 'Failed to load profile data.', 'error');
            }
        })
        .catch(error => {
            loadingSpinner.classList.add('hidden');
            profileFormContainer.classList.remove('hidden');
            console.error('Error fetching profile:', error);
            showAlert('An error occurred while fetching your profile. Please try again.', 'error');
        });

    profileForm.addEventListener('submit', function(event) {
        event.preventDefault();
        saveProfileButton.disabled = true;
        saveProfileButton.innerHTML = '<div class="spinner_button animate-spin rounded-full h-5 w-5 border-b-2 border-white mx-auto"></div> Processing...';
        alertMessage.classList.add('hidden');

        const formData = new FormData(profileForm);
        formData.append('action', 'update_profile'); // Add the action parameter

        // If you are handling file uploads for profile_image, ensure your backend (api/users.php) is set up for it.
        // const profileImageFile = document.getElementById('profile_image').files[0];
        // if (profileImageFile) {
        //     formData.append('profile_image', profileImageFile);
        // }

        fetch('api/users.php', { // Adjust path if API is elsewhere
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert(data.message || 'Profile updated successfully!', 'success');
                // Optionally clear password fields
                document.getElementById('current_password').value = '';
                document.getElementById('new_password').value = '';
                document.getElementById('confirm_new_password').value = '';
                // If email was changed and it's used for login display, you might want to update UI or re-fetch user data.
            } else {
                let errorMessage = data.message || 'Failed to update profile.';
                if (data.errors && data.errors.length > 0) {
                    errorMessage += ' Errors: ' + data.errors.join(', ');
                }
                showAlert(errorMessage, 'error');
            }
        })
        .catch(error => {
            console.error('Error updating profile:', error);
            showAlert('An error occurred. Please try again.', 'error');
        })
        .finally(() => {
            saveProfileButton.disabled = false;
            saveProfileButton.innerHTML = 'Save Changes';
        });
    });
});
</script>

</body>
</html>