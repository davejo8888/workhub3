<?php
require_once 'auth/session.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('dashboard.php');
}

// Get available roles and departments for dropdown options
$db = Database::getInstance();
$roles = $db->fetchAll("SELECT id, name FROM roles WHERE name != 'admin' ORDER BY name");
$departments = $db->fetchAll("SELECT DISTINCT department FROM Users WHERE department IS NOT NULL ORDER BY department");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create an Account - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-lg-6 col-md-8">
                <div class="card shadow-lg">
                    <div class="card-header bg-primary text-white">
                        <h2 class="text-center mb-0">Create an Account</h2>
                        <p class="text-center mb-0">Join <?php echo SITE_NAME; ?> to manage your tasks efficiently</p>
                    </div>
                    <div class="card-body">
                        <form id="registrationForm" method="post">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            
                            <div class="mb-3">
                                <label for="fullName" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="fullName" name="full_name" required>
                                <div class="invalid-feedback">Please enter your full name</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                                <div class="invalid-feedback">Please choose a username</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                                <div class="invalid-feedback">Please enter a valid email address</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" 
                                       minlength="8" required>
                                <div class="invalid-feedback">Password must be at least 8 characters</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirmPassword" class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" id="confirmPassword" 
                                       name="confirm_password" required>
                                <div class="invalid-feedback">Passwords do not match</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="department" class="form-label">Department</label>
                                <select class="form-select" id="department" name="department" required>
                                    <option value="">Select Department</option>
                                    <?php if (!empty($departments)): ?>
                                        <?php foreach ($departments as $dept): ?>
                                            <option value="<?php echo htmlspecialchars($dept['department']); ?>">
                                                <?php echo htmlspecialchars($dept['department']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                    <option value="other">Other</option>
                                </select>
                                <div id="otherDepartmentDiv" class="mt-2 d-none">
                                    <input type="text" class="form-control" id="otherDepartment" 
                                           name="other_department" placeholder="Enter department name">
                                </div>
                                <div class="invalid-feedback">Please select your department</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="role" class="form-label">Role</label>
                                <select class="form-select" id="role" name="role" required>
                                    <option value="">Select Role</option>
                                    <?php if (!empty($roles)): ?>
                                        <?php foreach ($roles as $role): ?>
                                            <option value="<?php echo htmlspecialchars($role['name']); ?>">
                                                <?php echo htmlspecialchars($role['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                                <div class="invalid-feedback">Please select your role</div>
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="termsCheck" name="terms" required>
                                <label class="form-check-label" for="termsCheck">
                                    I agree to the <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">Terms of Service</a> 
                                    and <a href="#" data-bs-toggle="modal" data-bs-target="#privacyModal">Privacy Policy</a>
                                </label>
                                <div class="invalid-feedback">You must agree before submitting</div>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">Create Account</button>
                            </div>
                        </form>
                        
                        <div class="text-center mt-3">
                            Already have an account? <a href="login.php" class="text-decoration-none">Sign in</a>
                        </div>
                    </div>
                </div>
                </div>
        </div>
    </div>

    <!-- Terms of Service Modal -->
    <div class="modal fade" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="termsModalLabel">Terms of Service</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h4>Terms and Conditions for <?php echo SITE_NAME; ?></h4>
                    <p>Last Updated: June 1, 2025</p>
                    
                    <h5>1. Acceptance of Terms</h5>
                    <p>By accessing and using the <?php echo SITE_NAME; ?> platform, you agree to be bound by these Terms and Conditions.</p>
                    
                    <h5>2. User Registration</h5>
                    <p>You are responsible for maintaining the confidentiality of your account information and password. You are fully responsible for all activities that occur under your account.</p>
                    
                    <h5>3. Proper Use</h5>
                    <p>You agree to use the service only for lawful purposes and in accordance with these Terms.</p>
                    
                    <h5>4. Data Privacy</h5>
                    <p>Your use of the platform is also governed by our Privacy Policy.</p>
                    
                    <h5>5. Intellectual Property</h5>
                    <p>All content included on the platform is the property of <?php echo SITE_NAME; ?> or its content suppliers and is protected by international copyright laws.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Privacy Policy Modal -->
    <div class="modal fade" id="privacyModal" tabindex="-1" aria-labelledby="privacyModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="privacyModalLabel">Privacy Policy</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h4>Privacy Policy for <?php echo SITE_NAME; ?></h4>
                    <p>Last Updated: June 1, 2025</p>
                    
                    <h5>1. Information We Collect</h5>
                    <p>We collect information you provide directly to us when you register for an account, create or modify your profile, and use the features of our platform.</p>
                    
                    <h5>2. How We Use Your Information</h5>
                    <p>We use the information we collect to operate, maintain, and provide the features and functionality of our service.</p>
                    
                    <h5>3. Data Security</h5>
                    <p>We implement appropriate security measures to protect your personal information.</p>
                    
                    <h5>4. Cookies</h5>
                    <p>We may use cookies and similar technologies to collect information about your use of our platform.</p>
                    
                    <h5>5. Changes to This Policy</h5>
                    <p>We may update this Privacy Policy from time to time. We will notify you of any changes by posting the new Privacy Policy on this page.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('registrationForm');
            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('confirmPassword');
            const department = document.getElementById('department');
            const otherDepartmentDiv = document.getElementById('otherDepartmentDiv');
            const otherDepartment = document.getElementById('otherDepartment');
            
            // Show/hide other department field
            department.addEventListener('change', function() {
                if (this.value === 'other') {
                    otherDepartmentDiv.classList.remove('d-none');
                    otherDepartment.setAttribute('required', 'required');
                } else {
                    otherDepartmentDiv.classList.add('d-none');
                    otherDepartment.removeAttribute('required');
                }
            });
            
            // Form validation and submission
            form.addEventListener('submit', function(event) {
                event.preventDefault();
                let isValid = true;
                
                // Reset previous validations
                form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
                
                // Validate password match
                if (password.value !== confirmPassword.value) {
                    confirmPassword.classList.add('is-invalid');
                    isValid = false;
                }
                
                // Check department selection
                if (department.value === 'other' && otherDepartment.value.trim() === '') {
                    otherDepartment.classList.add('is-invalid');
                    isValid = false;
                }
                
                // Basic validation for all required fields
                form.querySelectorAll('[required]').forEach(function(input) {
                    if (input.value.trim() === '') {
                        input.classList.add('is-invalid');
                        isValid = false;
                    }
                });
                
                // If form is valid, submit via AJAX
                if (isValid) {
                    const formData = new FormData(form);
                    
                    // Replace department value with custom one if "other" was selected
                    if (department.value === 'other') {
                        formData.set('department', otherDepartment.value);
                    }
                    
                    // Show loading indicator
                    const submitBtn = form.querySelector('button[type="submit"]');
                    const originalBtnText = submitBtn.innerHTML;
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';
                    
                    // Send registration request
                    fetch('api/register_process.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        // Reset button state
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalBtnText;
                        
                        if (data.success) {
                            // Registration successful
                            Swal.fire({
                                icon: 'success',
                                title: 'Registration Successful!',
                                text: 'Your account has been created. You will now be redirected to the login page.',
                                confirmButtonText: 'Continue'
                            }).then(() => {
                                window.location.href = 'login.php?registered=1';
                            });
                        } else {
                            // Show errors
                            if (data.errors) {
                                Object.keys(data.errors).forEach(key => {
                                    const input = form.querySelector(`[name="${key}"]`);
                                    if (input) {
                                        input.classList.add('is-invalid');
                                        const feedback = input.nextElementSibling;
                                        if (feedback && feedback.classList.contains('invalid-feedback')) {
                                            feedback.textContent = data.errors[key];
                                        }
                                    }
                                });
                            }
                            
                            Swal.fire({
                                icon: 'error',
                                title: 'Registration Failed',
                                text: data.message || 'Please check the form for errors and try again.',
                                confirmButtonText: 'OK'
                            });
                        }
                    })
                    .catch(error => {
                        // Reset button state
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalBtnText;
                        
                        console.error('Error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Registration Error',
                            text: 'An unexpected error occurred. Please try again later.',
                            confirmButtonText: 'OK'
                        });
                    });
                }
            });
        });
    </script>
</body>
</html>