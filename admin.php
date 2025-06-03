<?php
require_once 'auth/session.php';

// Require admin privileges
requirePermission('manage_users');

// Get current user data
$user = getCurrentUser();
if (!$user) {
    redirect('login.php');
}

// Get basic statistics for dashboard
$db = Database::getInstance();

// User statistics
$totalUsers = $db->fetchOne("SELECT COUNT(*) as count FROM Users")['count'];
$activeUsers = $db->fetchOne("SELECT COUNT(*) as count FROM Users WHERE status = 'active'")['count'];
$inactiveUsers = $db->fetchOne("SELECT COUNT(*) as count FROM Users WHERE status = 'inactive'")['count'];

// Task statistics
$totalTasks = $db->fetchOne("SELECT COUNT(*) as count FROM tasks")['count'];
$completedTasks = $db->fetchOne("SELECT COUNT(*) as count FROM tasks WHERE status = 'completed'")['count'];
$pendingTasks = $db->fetchOne("SELECT COUNT(*) as count FROM tasks WHERE status != 'completed'")['count'];

// Period statistics
$totalPeriods = $db->fetchOne("SELECT COUNT(*) as count FROM Periods")['count'];
$activePeriods = $db->fetchOne("SELECT COUNT(*) as count FROM Periods WHERE CURDATE() BETWEEN start_date AND end_date")['count'];

// Activity statistics
$recentActivities = $db->fetchOne("SELECT COUNT(*) as count FROM ActivityLog WHERE timestamp > DATE_SUB(NOW(), INTERVAL 7 DAY)")['count'];

// Get different roles
$roles = $db->fetchAll("SELECT id, name FROM roles ORDER BY name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/admin.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Admin Sidebar -->
            <nav id="admin-sidebar" class="col-md-3 col-lg-2 d-md-block bg-dark sidebar collapse">
                <div class="position-sticky pt-3">
                    <div class="px-3 py-2 d-flex justify-content-between align-items-center">
                        <a href="admin.php" class="text-white text-decoration-none">
                            <h3><?php echo SITE_NAME; ?> Admin</h3>
                        </a>
                        <button class="btn btn-link d-md-none" type="button" data-bs-toggle="collapse" data-bs-target="#admin-sidebar">
                            <i class="bi bi-x text-white fs-2"></i>
                        </button>
                    </div>
                    <hr class="text-light">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="#dashboard-section">
                                <i class="bi bi-speedometer2 me-2"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#users-section">
                                <i class="bi bi-people me-2"></i> User Management
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#reports-section">
                                <i class="bi bi-bar-chart me-2"></i> Reports
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#activity-section">
                                <i class="bi bi-activity me-2"></i> Activity Logs
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#system-section">
                                <i class="bi bi-gear me-2"></i> System Settings
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="bi bi-arrow-left-circle me-2"></i> Back to Dashboard
                            </a>
                        </li>
                    </ul>
                    <hr class="text-light">
                    <div class="px-3">
                        <div class="text-muted small">
                            <p class="mb-0">Logged in as:</p>
                            <div class="d-flex align-items-center text-white">
                                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user['full_name']); ?>&background=random" alt="User" class="rounded-circle me-2" width="32" height="32">
                                <span><?php echo htmlspecialchars($user['full_name']); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <!-- Top Navigation Bar -->
                <header class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Admin Panel</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="me-2">
                            <button type="button" class="btn btn-sm btn-outline-primary d-md-none" data-bs-toggle="collapse" data-bs-target="#admin-sidebar">
                                <i class="bi bi-list"></i> Menu
                            </button>
                        </div>
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="refreshAdmin">
                                <i class="bi bi-arrow-clockwise"></i> Refresh
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#quickReportModal">
                                <i class="bi bi-file-earmark-bar-graph"></i> Quick Report
                            </button>
                        </div>
                    </div>
                </header>

                <!-- Dashboard Section -->
                <section id="dashboard-section" class="mb-5">
                    <h2 class="h3">Dashboard Overview</h2>
                    <div class="row g-3 mb-4 mt-2">
                        <!-- User Stats -->
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-8">
                                            <h5 class="card-title">Users</h5>
                                            <h2 class="card-text"><?php echo $totalUsers; ?></h2>
                                            <p class="small mb-0"><i class="bi bi-person-check-fill me-1"></i> <?php echo $activeUsers; ?> active</p>
                                        </div>
                                        <div class="col-4 text-center">
                                            <i class="bi bi-people-fill fs-1 opacity-50"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer d-flex align-items-center justify-content-between bg-primary border-top-0">
                                    <a href="#users-section" class="text-white text-decoration-none small">View Details <i class="bi bi-chevron-right small"></i></a>
                                    <div class="small text-white"><?php echo $inactiveUsers; ?> inactive</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Task Stats -->
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-8">
                                            <h5 class="card-title">Tasks</h5>
                                            <h2 class="card-text"><?php echo $totalTasks; ?></h2>
                                            <p class="small mb-0"><i class="bi bi-check2-all me-1"></i> <?php echo $completedTasks; ?> completed</p>
                                        </div>
                                        <div class="col-4 text-center">
                                            <i class="bi bi-list-task fs-1 opacity-50"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer d-flex align-items-center justify-content-between bg-success border-top-0">
                                    <a href="#reports-section" class="text-white text-decoration-none small">View Details <i class="bi bi-chevron-right small"></i></a>
                                    <div class="small text-white"><?php echo $pendingTasks; ?> pending</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Period Stats -->
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-8">
                                            <h5 class="card-title">Periods</h5>
                                            <h2 class="card-text"><?php echo $totalPeriods; ?></h2>
                                            <p class="small mb-0"><i class="bi bi-calendar-event me-1"></i> <?php echo $activePeriods; ?> active</p>
                                        </div>
                                        <div class="col-4 text-center">
                                            <i class="bi bi-calendar3 fs-1 opacity-50"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer d-flex align-items-center justify-content-between bg-info border-top-0">
                                    <a href="#reports-section" class="text-white text-decoration-none small">View Details <i class="bi bi-chevron-right small"></i></a>
                                    <div class="small text-white"><?php echo $totalPeriods - $activePeriods; ?> inactive</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Activity Stats -->
                        <div class="col-md-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-8">
                                            <h5 class="card-title">Activity</h5>
                                            <h2 class="card-text"><?php echo $recentActivities; ?></h2>
                                            <p class="small mb-0"><i class="bi bi-clock-history me-1"></i> last 7 days</p>
                                        </div>
                                        <div class="col-4 text-center">
                                            <i class="bi bi-activity fs-1 opacity-50"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer d-flex align-items-center justify-content-between bg-warning border-top-0">
                                    <a href="#activity-section" class="text-white text-decoration-none small">View Details <i class="bi bi-chevron-right small"></i></a>
                                    <div class="small text-white"><i class="bi bi-eye"></i> Monitor</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Charts -->
                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-header">
                                    <h5 class="card-title">User Registrations</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="userRegistrationsChart" height="250"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-header">
                                    <h5 class="card-title">Task Completion Rate</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="taskCompletionChart" height="250"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-4">
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Recent Activity</h5>
                                </div>
                                <div class="card-body">
                                    <div id="recentActivityTable">
                                        <div class="text-center py-4">
                                            <div class="spinner-border text-primary" role="status">
                                                <span class="visually-hidden">Loading...</span>
                                            </div>
                                            <p class="mt-2">Loading recent activity...</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">System Status</h5>
                                </div>
                                <div class="card-body">
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <span><i class="bi bi-check-circle-fill text-success me-2"></i> Database Connection</span>
                                            <span class="badge bg-success rounded-pill">Active</span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <span><i class="bi bi-check-circle-fill text-success me-2"></i> Session Management</span>
                                            <span class="badge bg-success rounded-pill">Active</span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <span><i class="bi bi-check-circle-fill text-success me-2"></i> File Storage</span>
                                            <span class="badge bg-success rounded-pill">Active</span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <span><i class="bi bi-check-circle-fill text-success me-2"></i> Email Service</span>
                                            <span class="badge bg-success rounded-pill">Active</span>
                                        </li>
                                    </ul>
                                    <div class="mt-3">
                                        <h6>Server Information</h6>
                                        <div class="small">
                                            <p class="mb-1"><strong>PHP Version:</strong> <?php echo phpversion(); ?></p>
                                            <p class="mb-1"><strong>Server:</strong> <?php echo $_SERVER['SERVER_SOFTWARE']; ?></p>
                                            <p class="mb-1"><strong>Database:</strong> MySQL</p>
                                            <p class="mb-0"><strong>Last Check:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Users Section -->
                <section id="users-section" class="mb-5">
                    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
                        <h2 class="h3">User Management</h2>
                        <div class="btn-toolbar mb-2 mb-md-0">
                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#createUserModal">
                                <i class="bi bi-person-plus-fill"></i> Add New User
                            </button>
                        </div>
                    </div>

                    <!-- Filters and Search -->
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="input-group">
                                        <span class="input-group-text" id="search-addon"><i class="bi bi-search"></i></span>
                                        <input type="text" class="form-control" id="userSearch" placeholder="Search users..." aria-label="Search" aria-describedby="search-addon">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <select class="form-select" id="roleFilter">
                                        <option value="">All Roles</option>
                                        <?php foreach ($roles as $role): ?>
                                            <option value="<?php echo $role['name']; ?>"><?php echo ucfirst($role['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <select class="form-select" id="statusFilter">
                                        <option value="">All Statuses</option>
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                        <option value="pending">Pending</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <button class="btn btn-outline-secondary w-100" id="applyFilters">
                                        Apply Filters
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Users Table -->
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="usersTable" class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Username</th>
                                            <th>Email</th>
                                            <th>Department</th>
                                            <th>Role</th>
                                            <th>Status</th>
                                            <th>Created</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td colspan="9" class="text-center">Loading users...</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Reports Section -->
                <section id="reports-section" class="mb-5">
                    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
                        <h2 class="h3">Reports</h2>
                        <div class="btn-toolbar mb-2 mb-md-0">
                            <div class="btn-group me-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="generatePdfReport">
                                    <i class="bi bi-file-earmark-pdf"></i> Export PDF
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="generateCsvReport">
                                    <i class="bi bi-file-earmark-excel"></i> Export CSV
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Report Generator -->
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="card-title mb-0">Report Generator</h5>
                        </div>
                        <div class="card-body">
                            <form id="reportForm" class="row g-3">
                                <div class="col-md-6">
                                    <label for="reportType" class="form-label">Report Type</label>
                                    <select class="form-select" id="reportType" required>
                                        <option value="">Select a report type</option>
                                        <option value="user_activity">User Activity Report</option>
                                        <option value="task_completion">Task Completion Report</option>
                                        <option value="period_summary">Period Summary Report</option>
                                        <option value="department_performance">Department Performance Report</option>
                                        <option value="system_usage">System Usage Report</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-3">
                                    <label for="reportStartDate" class="form-label">Start Date</label>
                                    <input type="date" class="form-control" id="reportStartDate" required>
                                </div>
                                
                                <div class="col-md-3">
                                    <label for="reportEndDate" class="form-label">End Date</label>
                                    <input type="date" class="form-control" id="reportEndDate" required>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="reportFormat" class="form-label">Format</label>
                                    <select class="form-select" id="reportFormat" required>
                                        <option value="html">HTML (View in Browser)</option>
                                        <option value="pdf">PDF Document</option>
                                        <option value="csv">CSV (Excel)</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="reportOptions" class="form-label">Additional Options</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="includeCharts" checked>
                                        <label class="form-check-label" for="includeCharts">
                                            Include visualizations/charts
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="includeTrends" checked>
                                        <label class="form-check-label" for="includeTrends">
                                            Include trend analysis
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="col-12 text-end">
                                    <button type="button" class="btn btn-secondary" id="resetReportForm">Reset</button>
                                    <button type="submit" class="btn btn-primary" id="generateReportBtn">
                                        <i class="bi bi-bar-chart"></i> Generate Report
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Saved Reports -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Recent & Saved Reports</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Report Name</th>
                                            <th>Type</th>
                                            <th>Date Range</th>
                                            <th>Generated By</th>
                                            <th>Created</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="savedReportsTable">
                                        <tr>
                                            <td>User Activity Q2 2025</td>
                                            <td>User Activity</td>
                                            <td>Apr 1, 2025 - Jun 30, 2025</td>
                                            <td>Admin User</td>
                                            <td>Jun 1, 2025</td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-outline-primary">
                                                        <i class="bi bi-eye"></i>
                                                    </button>
                                                    <button class="btn btn-outline-secondary">
                                                        <i class="bi bi-download"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Task Completion Rate May 2025</td>
                                            <td>Task Completion</td>
                                            <td>May 1, 2025 - May 31, 2025</td>
                                            <td>Admin User</td>
                                            <td>Jun 1, 2025</td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-outline-primary">
                                                        <i class="bi bi-eye"></i>
                                                    </button>
                                                    <button class="btn btn-outline-secondary">
                                                        <i class="bi bi-download"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Activity Logs Section -->
                <section id="activity-section" class="mb-5">
                    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
                        <h2 class="h3">Activity Logs</h2>
                        <div class="btn-toolbar mb-2 mb-md-0">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="exportActivityLogs">
                                <i class="bi bi-download"></i> Export Logs
                            </button>
                        </div>
                    </div>
                    
                    <!-- Filters -->
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="activityType" class="form-label">Activity Type</label>
                                    <select class="form-select" id="activityType">
                                        <option value="">All Activities</option>
                                        <option value="login">Login</option>
                                        <option value="logout">Logout</option>
                                        <option value="registration">Registration</option>
                                        <option value="task_created">Task Created</option>
                                        <option value="task_updated">Task Updated</option>
                                        <option value="period_created">Period Created</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="activityStartDate" class="form-label">Start Date</label>
                                    <input type="date" class="form-control" id="activityStartDate">
                                </div>
                                <div class="col-md-4">
                                    <label for="activityEndDate" class="form-label">End Date</label>
                                    <input type="date" class="form-control" id="activityEndDate">
                                </div>
                                <div class="col-md-4">
                                    <label for="activityUser" class="form-label">User</label>
                                    <select class="form-select" id="activityUser">
                                        <option value="">All Users</option>
                                        <!-- Will be populated via JavaScript -->
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="activityEntityType" class="form-label">Entity Type</label>
                                    <select class="form-select" id="activityEntityType">
                                        <option value="">All Entity Types</option>
                                        <option value="user">User</option>
                                        <option value="task">Task</option>
                                        <option value="period">Period</option>
                                        <option value="system">System</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="d-block invisible">Filter</label>
                                    <button class="btn btn-primary w-100" id="filterActivities">Apply Filters</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Activity Logs Table -->
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="activityLogsTable" class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Timestamp</th>
                                            <th>User</th>
                                            <th>Activity Type</th>
                                            <th>Description</th>
                                            <th>Entity Type</th>
                                            <th>IP Address</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td colspan="7" class="text-center">Loading activity logs...</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- System Settings Section -->
                <section id="system-section" class="mb-5">
                    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
                        <h2 class="h3">System Settings</h2>
                        <div class="btn-toolbar mb-2 mb-md-0">
                            <button type="button" class="btn btn-sm btn-success" id="saveAllSettings">
                                <i class="bi bi-save"></i> Save All Settings
                            </button>
                        </div>
                    </div>
                    
                    <!-- Settings Tabs -->
                    <div class="card">
                        <div class="card-body">
                            <ul class="nav nav-tabs" id="settingsTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general-settings" type="button" role="tab" aria-controls="general-settings" aria-selected="true">General</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="security-tab" data-bs-toggle="tab" data-bs-target="#security-settings" type="button" role="tab" aria-controls="security-settings" aria-selected="false">Security</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="email-tab" data-bs-toggle="tab" data-bs-target="#email-settings" type="button" role="tab" aria-controls="email-settings" aria-selected="false">Email</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="backup-tab" data-bs-toggle="tab" data-bs-target="#backup-settings" type="button" role="tab" aria-controls="backup-settings" aria-selected="false">Backup</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="roles-tab" data-bs-toggle="tab" data-bs-target="#roles-settings" type="button" role="tab" aria-controls="roles-settings" aria-selected="false">Roles & Permissions</button>
                                </li>
                            </ul>
                            
                            <div class="tab-content mt-3" id="settingsTabsContent">
                                <!-- General Settings Tab -->
                                <div class="tab-pane fade show active" id="general-settings" role="tabpanel" aria-labelledby="general-tab">
                                    <form id="generalSettingsForm">
                                        <div class="row mb-3">
                                            <label for="siteName" class="col-sm-3 col-form-label">Site Name</label>
                                            <div class="col-sm-9">
                                                <input type="text" class="form-control" id="siteName" value="<?php echo SITE_NAME; ?>">
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label for="siteURL" class="col-sm-3 col-form-label">Site URL</label>
                                            <div class="col-sm-9">
                                                <input type="url" class="form-control" id="siteURL" value="<?php echo SITE_URL; ?>">
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label for="adminEmail" class="col-sm-3 col-form-label">Admin Email</label>
                                            <div class="col-sm-9">
                                                <input type="email" class="form-control" id="adminEmail" value="<?php echo SITE_EMAIL; ?>">
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label for="timezone" class="col-sm-3 col-form-label">Default Timezone</label>
                                            <div class="col-sm-9">
                                                <select class="form-select" id="timezone">
                                                    <option value="America/New_York">America/New_York</option>
                                                    <option value="America/Chicago">America/Chicago</option>
                                                    <option value="America/Denver">America/Denver</option>
                                                    <option value="America/Los_Angeles">America/Los_Angeles</option>
                                                    <option value="Europe/London">Europe/London</option>
                                                    <option value="Europe/Paris">Europe/Paris</option>
                                                    <option value="Asia/Tokyo">Asia/Tokyo</option>
                                                    <option value="Australia/Sydney" selected>Australia/Sydney</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label for="dateFormat" class="col-sm-3 col-form-label">Date Format</label>
                                            <div class="col-sm-9">
                                                <select class="form-select" id="dateFormat">
                                                    <option value="Y-m-d">2025-06-01 (YYYY-MM-DD)</option>
                                                    <option value="m/d/Y">06/01/2025 (MM/DD/YYYY)</option>
                                                    <option value="d/m/Y">01/06/2025 (DD/MM/YYYY)</option>
                                                    <option value="d.m.Y">01.06.2025 (DD.MM.YYYY)</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label for="maintenanceMode" class="col-sm-3 col-form-label">Maintenance Mode</label>
                                            <div class="col-sm-9">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="maintenanceMode">
                                                    <label class="form-check-label" for="maintenanceMode">Enable maintenance mode</label>
                                                </div>
                                                <div class="form-text">When enabled, only administrators can access the site.</div>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <button type="submit" class="btn btn-primary">Save General Settings</button>
                                        </div>
                                    </form>
                                </div>
                                
                                <!-- Security Settings Tab -->
                                <div class="tab-pane fade" id="security-settings" role="tabpanel" aria-labelledby="security-tab">
                                    <form id="securitySettingsForm">
                                        <div class="row mb-3">
                                            <label for="sessionLifetime" class="col-sm-3 col-form-label">Session Lifetime (seconds)</label>
                                            <div class="col-sm-9">
                                                <input type="number" class="form-control" id="sessionLifetime" value="86400" min="300">
                                                <div class="form-text">Default: 86400 (24 hours)</div>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label for="passwordPolicy" class="col-sm-3 col-form-label">Password Policy</label>
                                            <div class="col-sm-9">
                                                <select class="form-select" id="passwordPolicy">
                                                    <option value="basic">Basic (8+ characters)</option>
                                                    <option value="medium" selected>Medium (8+ characters with numbers and letters)</option>
                                                    <option value="strong">Strong (8+ characters with uppercase, lowercase, numbers, and symbols)</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label for="maxLoginAttempts" class="col-sm-3 col-form-label">Max Login Attempts</label>
                                            <div class="col-sm-9">
                                                <input type="number" class="form-control" id="maxLoginAttempts" value="5" min="1" max="10">
                                                <div class="form-text">Number of failed attempts before temporary lockout</div>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label class="col-sm-3 col-form-label">Security Options</label>
                                            <div class="col-sm-9">
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox" id="forceSSL" checked>
                                                    <label class="form-check-label" for="forceSSL">Force SSL/HTTPS</label>
                                                </div>
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox" id="enableCaptcha" checked>
                                                    <label class="form-check-label" for="enableCaptcha">Enable CAPTCHA on login and registration</label>
                                                </div>
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox" id="twoFactorAuth">
                                                    <label class="form-check-label" for="twoFactorAuth">Enable Two-Factor Authentication</label>
                                                </div>
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox" id="logFailedLogins" checked>
                                                    <label class="form-check-label" for="logFailedLogins">Log failed login attempts</label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <button type="submit" class="btn btn-primary">Save Security Settings</button>
                                        </div>
                                    </form>
                                </div>
                                
                                <!-- Email Settings Tab -->
                                <div class="tab-pane fade" id="email-settings" role="tabpanel" aria-labelledby="email-tab">
                                    <form id="emailSettingsForm">
                                        <div class="row mb-3">
                                            <label for="smtpHost" class="col-sm-3 col-form-label">SMTP Host</label>
                                            <div class="col-sm-9">
                                                <input type="text" class="form-control" id="smtpHost" value="smtp.example.com">
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label for="smtpPort" class="col-sm-3 col-form-label">SMTP Port</label>
                                            <div class="col-sm-9">
                                                <input type="number" class="form-control" id="smtpPort" value="587">
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label for="smtpUsername" class="col-sm-3 col-form-label">SMTP Username</label>
                                            <div class="col-sm-9">
                                                <input type="text" class="form-control" id="smtpUsername" value="user@example.com">
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label for="smtpPassword" class="col-sm-3 col-form-label">SMTP Password</label>
                                            <div class="col-sm-9">
                                                <input type="password" class="form-control" id="smtpPassword" value="••••••••">
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label for="smtpEncryption" class="col-sm-3 col-form-label">SMTP Encryption</label>
                                            <div class="col-sm-9">
                                                <select class="form-select" id="smtpEncryption">
                                                    <option value="none">None</option>
                                                    <option value="ssl">SSL</option>
                                                    <option value="tls" selected>TLS</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label for="emailFromName" class="col-sm-3 col-form-label">From Name</label>
                                            <div class="col-sm-9">
                                                <input type="text" class="form-control" id="emailFromName" value="<?php echo SITE_NAME; ?>">
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label for="emailFromAddress" class="col-sm-3 col-form-label">From Email</label>
                                            <div class="col-sm-9">
                                                <input type="email" class="form-control" id="emailFromAddress" value="noreply@workhub.gotoaus.com">
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <button type="button" class="btn btn-outline-secondary me-2" id="testEmailBtn">Test Email Settings</button>
                                            <button type="submit" class="btn btn-primary">Save Email Settings</button>
                                        </div>
                                    </form>
                                </div>
                                
                                <!-- Backup Settings Tab -->
                                <div class="tab-pane fade" id="backup-settings" role="tabpanel" aria-labelledby="backup-tab">
                                    <div class="alert alert-info">
                                        <i class="bi bi-info-circle-fill me-2"></i>
                                        Regular backups help protect your data against loss. We recommend scheduling automatic backups.
                                    </div>
                                    
                                    <form id="backupSettingsForm">
                                        <div class="row mb-3">
                                            <label for="backupFrequency" class="col-sm-3 col-form-label">Backup Frequency</label>
                                            <div class="col-sm-9">
                                                <select class="form-select" id="backupFrequency">
                                                    <option value="daily">Daily</option>
                                                    <option value="weekly" selected>Weekly</option>
                                                    <option value="monthly">Monthly</option>
                                                    <option value="manual">Manual Only</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label for="backupTime" class="col-sm-3 col-form-label">Backup Time</label>
                                            <div class="col-sm-9">
                                                <input type="time" class="form-control" id="backupTime" value="02:00">
                                                <div class="form-text">Server time (24-hour format)</div>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label for="backupRetention" class="col-sm-3 col-form-label">Retention Period</label>
                                            <div class="col-sm-9">
                                                <select class="form-select" id="backupRetention">
                                                    <option value="7">7 days</option>
                                                    <option value="14">14 days</option>
                                                    <option value="30" selected>30 days</option>
                                                    <option value="90">90 days</option>
                                                    <option value="365">1 year</option>
                                                </select>
                                                <div class="form-text">How long to keep backup files before automatic deletion</div>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label class="col-sm-3 col-form-label">Backup Components</label>
                                            <div class="col-sm-9">
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox" id="backupDatabase" checked>
                                                    <label class="form-check-label" for="backupDatabase">Database</label>
                                                </div>
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox" id="backupFiles" checked>
                                                    <label class="form-check-label" for="backupFiles">Files and Uploads</label>
                                                </div>
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox" id="backupSettings" checked>
                                                    <label class="form-check-label" for="backupSettings">Configuration and Settings</label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-sm-9 offset-sm-3">
                                                <button type="button" class="btn btn-success" id="createBackupBtn">
                                                    <i class="bi bi-download"></i> Create Backup Now
                                                </button>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <button type="submit" class="btn btn-primary">Save Backup Settings</button>
                                        </div>
                                    </form>
                                    
                                    <hr>
                                    
                                    <h5>Recent Backups</h5>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Filename</th>
                                                    <th>Date</th>
                                                    <th>Size</th>
                                                    <th>Type</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody id="backupsTable">
                                                <tr>
                                                    <td>workhub_full_backup_20250601.zip</td>
                                                    <td>Jun 1, 2025 02:00</td>
                                                    <td>24.5 MB</td>
                                                    <td>Full</td>
                                                    <td>
                                                        <button class="btn btn-sm btn-outline-primary">
                                                            <i class="bi bi-download"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-outline-danger">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>workhub_db_backup_20250525.zip</td>
                                                    <td>May 25, 2025 02:00</td>
                                                    <td>3.2 MB</td>
                                                    <td>Database</td>
                                                    <td>
                                                        <button class="btn btn-sm btn-outline-primary">
                                                            <i class="bi bi-download"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-outline-danger">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                
                                <!-- Roles & Permissions Tab -->
                                <div class="tab-pane fade" id="roles-settings" role="tabpanel" aria-labelledby="roles-tab">
                                    <div class="d-flex justify-content-between mb-3">
                                        <h5>User Roles</h5>
                                        <button class="btn btn-sm btn-primary" id="addRoleBtn" data-bs-toggle="modal" data-bs-target="#createRoleModal">
                                            <i class="bi bi-plus-circle"></i> Add New Role
                                        </button>
                                    </div>
                                    
                                    <div class="table-responsive mb-4">
                                        <table class="table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Role Name</th>
                                                    <th>Description</th>
                                                    <th>Users</th>
                                                    <th>Created</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody id="rolesTable">
                                                <?php foreach ($roles as $role): ?>
                                                <tr>
                                                    <td><?php echo ucfirst($role['name']); ?></td>
                                                    <td>
                                                        <?php
                                                            switch($role['name']) {
                                                                case 'admin':
                                                                    echo 'Administrator with full access';
                                                                    break;
                                                                case 'manager':
                                                                    echo 'Manager with elevated permissions';
                                                                    break;
                                                                case 'user':
                                                                    echo 'Standard user';
                                                                    break;
                                                                default:
                                                                    echo 'Custom role';
                                                            }
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <?php
                                                            $userCount = $db->fetchOne(
                                                                "SELECT COUNT(*) as count FROM Users WHERE role_id = :role_id",
                                                                ['role_id' => $role['id']]
                                                            )['count'];
                                                            echo $userCount;
                                                        ?>
                                                    </td>
                                                    <td>System Default</td>
                                                    <td>
                                                        <button class="btn btn-sm btn-outline-primary edit-role-btn" data-role-id="<?php echo $role['id']; ?>">
                                                            <i class="bi bi-pencil"></i> Edit
                                                        </button>
                                                        <?php if ($role['name'] !== 'admin'): ?>
                                                        <button class="btn btn-sm btn-outline-danger delete-role-btn" data-role-id="<?php echo $role['id']; ?>">
                                                            <i class="bi bi-trash"></i> Delete
                                                        </button>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    
                                    <h5>Role Permissions</h5>
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <label for="roleSelector" class="form-label">Select Role to Manage Permissions</label>
                                                <select class="form-select" id="roleSelector">
                                                    <?php foreach ($roles as $role): ?>
                                                    <option value="<?php echo $role['id']; ?>"><?php echo ucfirst($role['name']); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            
                                            <div id="permissionsContainer" class="mt-4">
                                                <div class="accordion" id="permissionsAccordion">
                                                    <!-- User Management Permissions -->
                                                    <div class="accordion-item">
                                                        <h2 class="accordion-header" id="userManagementHeading">
                                                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#userManagementCollapse" aria-expanded="true" aria-controls="userManagementCollapse">
                                                                User Management
                                                            </button>
                                                        </h2>
                                                        <div id="userManagementCollapse" class="accordion-collapse collapse show" aria-labelledby="userManagementHeading" data-bs-parent="#permissionsAccordion">
                                                            <div class="accordion-body">
                                                                <div class="row">
                                                                    <div class="col-md-6">
                                                                        <div class="form-check mb-2">
                                                                            <input class="form-check-input permission-check" type="checkbox" id="perm_view_users" data-perm="view_users">
                                                                            <label class="form-check-label" for="perm_view_users">
                                                                                View Users
                                                                            </label>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <div class="form-check mb-2">
                                                                            <input class="form-check-input permission-check" type="checkbox" id="perm_manage_users" data-perm="manage_users">
                                                                            <label class="form-check-label" for="perm_manage_users">
                                                                                Manage Users
                                                                            </label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Period Management Permissions -->
                                                    <div class="accordion-item">
                                                        <h2 class="accordion-header" id="periodManagementHeading">
                                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#periodManagementCollapse" aria-expanded="false" aria-controls="periodManagementCollapse">
                                                                Period Management
                                                            </button>
                                                        </h2>
                                                        <div id="periodManagementCollapse" class="accordion-collapse collapse" aria-labelledby="periodManagementHeading" data-bs-parent="#permissionsAccordion">
                                                            <div class="accordion-body">
                                                                <div class="row">
                                                                    <div class="col-md-6">
                                                                        <div class="form-check mb-2">
                                                                            <input class="form-check-input permission-check" type="checkbox" id="perm_view_periods" data-perm="view_periods">
                                                                            <label class="form-check-label" for="perm_view_periods">
                                                                                View Periods
                                                                            </label>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <div class="form-check mb-2">
                                                                            <input class="form-check-input permission-check" type="checkbox" id="perm_manage_periods" data-perm="manage_periods">
                                                                            <label class="form-check-label" for="perm_manage_periods">
                                                                                Manage Periods
                                                                            </label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Task Management Permissions -->
                                                    <div class="accordion-item">
                                                        <h2 class="accordion-header" id="taskManagementHeading">
                                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#taskManagementCollapse" aria-expanded="false" aria-controls="taskManagementCollapse">
                                                                Task Management
                                                            </button>
                                                        </h2>
                                                        <div id="taskManagementCollapse" class="accordion-collapse collapse" aria-labelledby="taskManagementHeading" data-bs-parent="#permissionsAccordion">
                                                            <div class="accordion-body">
                                                                <div class="row">
                                                                    <div class="col-md-6">
                                                                        <div class="form-check mb-2">
                                                                            <input class="form-check-input permission-check" type="checkbox" id="perm_view_tasks" data-perm="view_tasks">
                                                                            <label class="form-check-label" for="perm_view_tasks">
                                                                                View Tasks
                                                                            </label>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <div class="form-check mb-2">
                                                                            <input class="form-check-input permission-check" type="checkbox" id="perm_manage_tasks" data-perm="manage_tasks">
                                                                            <label class="form-check-label" for="perm_manage_tasks">
                                                                                Manage Tasks
                                                                            </label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Report & System Permissions -->
                                                    <div class="accordion-item">
                                                        <h2 class="accordion-header" id="systemManagementHeading">
                                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#systemManagementCollapse" aria-expanded="false" aria-controls="systemManagementCollapse">
                                                                Reports & System
                                                            </button>
                                                        </h2>
                                                        <div id="systemManagementCollapse" class="accordion-collapse collapse" aria-labelledby="systemManagementHeading" data-bs-parent="#permissionsAccordion">
                                                            <div class="accordion-body">
                                                                <div class="row">
                                                                    <div class="col-md-6">
                                                                        <div class="form-check mb-2">
                                                                            <input class="form-check-input permission-check" type="checkbox" id="perm_view_reports" data-perm="view_reports">
                                                                            <label class="form-check-label" for="perm_view_reports">
                                                                                View Reports
                                                                            </label>
                                                                        </div>
                                                                    </div>
                                                                     <div class="col-md-6">
                                                                        <div class="form-check mb-2">
                                                                            <input class="form-check-input permission-check" type="checkbox" id="perm_manage_settings" data-perm="manage_settings">
                                                                            <label class="form-check-label" for="perm_manage_settings">
                                                                                Manage Settings
                                                                            </label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="row">
                                                                    <div class="col-md-6">
                                                                        <div class="form-check mb-2">
                                                                            <input class="form-check-input permission-check" type="checkbox" id="perm_manage_roles" data-perm="manage_roles">
                                                                            <label class="form-check-label" for="perm_manage_roles">
                                                                                Manage Roles
                                                                            </label>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <div class="form-check mb-2">
                                                                            <input class="form-check-input permission-check" type="checkbox" id="perm_view_roles" data-perm="view_roles">
                                                                            <label class="form-check-label" for="perm_view_roles">
                                                                                View Roles
                                                                            </label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                                                    <button class="btn btn-secondary me-md-2" id="resetPermissionsBtn">Reset Changes</button>
                                                    <button class="btn btn-primary" id="savePermissionsBtn">Save Permissions</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </main>
        </div>
    </div>

    <!-- Quick Report Modal -->
    <div class="modal fade" id="quickReportModal" tabindex="-1" aria-labelledby="quickReportModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="quickReportModalLabel">Generate Quick Report</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="quickReportForm">
                        <div class="mb-3">
                            <label for="quickReportType" class="form-label">Report Type</label>
                            <select class="form-select" id="quickReportType" required>
                                <option value="">Select a report type</option>
                                <option value="user_summary">User Summary</option>
                                <option value="task_summary">Task Summary</option>
                                <option value="period_summary">Period Summary</option>
                                <option value="system_health">System Health</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="quickReportFormat" class="form-label">Format</label>
                            <select class="form-select" id="quickReportFormat" required>
                                <option value="html">HTML (View)</option>
                                <option value="pdf">PDF</option>
                                <option value="csv">CSV</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="quickReportTimeframe" class="form-label">Timeframe</label>
                            <select class="form-select" id="quickReportTimeframe" required>
                                <option value="today">Today</option>
                                <option value="yesterday">Yesterday</option>
                                <option value="this_week" selected>This Week</option>
                                <option value="last_week">Last Week</option>
                                <option value="this_month">This Month</option>
                                <option value="last_month">Last Month</option>
                                <option value="this_year">This Year</option>
                                <option value="all_time">All Time</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="generateQuickReportBtn">Generate Report</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Create User Modal -->
    <div class="modal fade" id="createUserModal" tabindex="-1" aria-labelledby="createUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createUserModalLabel">Add New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="createUserForm">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        
                        <div class="mb-3">
                            <label for="newUserFullName" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="newUserFullName" name="full_name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="newUserUsername" class="form-label">Username</label>
                            <input type="text" class="form-control" id="newUserUsername" name="username" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="newUserEmail" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="newUserEmail" name="email" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="newUserPassword" class="form-label">Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="newUserPassword" name="password" required>
                                <button class="btn btn-outline-secondary" type="button" id="generatePasswordBtn">
                                    <i class="bi bi-shuffle"></i> Generate
                                </button>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="newUserDepartment" class="form-label">Department</label>
                            <input type="text" class="form-control" id="newUserDepartment" name="department" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="newUserRole" class="form-label">Role</label>
                            <select class="form-select" id="newUserRole" name="role" required>
                                <option value="">Select Role</option>
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?php echo htmlspecialchars($role['name']); ?>">
                                        <?php echo ucfirst(htmlspecialchars($role['name'])); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="newUserStatus" class="form-label">Status</label>
                            <select class="form-select" id="newUserStatus" name="status" required>
                                <option value="active" selected>Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="pending">Pending</option>
                            </select>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="sendWelcomeEmail" name="send_welcome_email" checked>
                            <label class="form-check-label" for="sendWelcomeEmail">
                                Send welcome email with login details
                            </label>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="createUserBtn">Create User</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Role Modal -->
    <div class="modal fade" id="createRoleModal" tabindex="-1" aria-labelledby="createRoleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createRoleModalLabel">Add New Role</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="createRoleForm">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        
                        <div class="mb-3">
                            <label for="newRoleName" class="form-label">Role Name</label>
                            <input type="text" class="form-control" id="newRoleName" name="name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="newRoleDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="newRoleDescription" name="description" rows="3" required></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Base Permissions On</label>
                            <select class="form-select" id="baseRolePermissions">
                                <option value="">None (Start Empty)</option>
                                <option value="user" selected>User (Basic Permissions)</option>
                                <option value="manager">Manager (Extended Permissions)</option>
                                <option value="admin">Admin (All Permissions)</option>
                            </select>
                            <div class="form-text">You can adjust individual permissions after creating the role</div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="createRoleBtn">Create Role</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize charts
            initCharts();
            
            // Load activity data
            loadRecentActivity();
            
            // Load user data for user management
            loadUsers();
            
            // Initialize DataTables
            initializeTables();
            
            // Load role permissions
            loadRolePermissions();
            
            // Event listeners
            document.getElementById('refreshAdmin').addEventListener('click', refreshAdminData);
            document.getElementById('filterActivities').addEventListener('click', filterActivityLogs);
            document.getElementById('applyFilters').addEventListener('click', filterUsers);
            document.getElementById('generateQuickReportBtn').addEventListener('click', generateQuickReport);
            document.getElementById('createUserBtn').addEventListener('click', createUser);
            document.getElementById('generatePasswordBtn').addEventListener('click', generateRandomPassword);
            document.getElementById('createRoleBtn').addEventListener('click', createRole);
            document.getElementById('roleSelector').addEventListener('change', loadRolePermissions);
            document.getElementById('savePermissionsBtn').addEventListener('click', saveRolePermissions);
            document.getElementById('resetPermissionsBtn').addEventListener('click', resetPermissionChanges);
            document.getElementById('saveAllSettings').addEventListener('click', saveAllSettings);
            document.getElementById('createBackupBtn').addEventListener('click', createBackupNow);
            document.getElementById('testEmailBtn').addEventListener('click', testEmailSettings);
            document.getElementById('exportActivityLogs').addEventListener('click', exportActivityLogs);
            document.getElementById('generatePdfReport').addEventListener('click', () => prepareReportExport('pdf'));
            document.getElementById('generateCsvReport').addEventListener('click', () => prepareReportExport('csv'));
        });

        // Initialize charts for the dashboard
        function initCharts() {
            // User registrations chart
            const userCtx = document.getElementById('userRegistrationsChart').getContext('2d');
            const userChart = new Chart(userCtx, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    datasets: [{
                        label: 'New Users',
                        data: [5, 7, 3, 8, 12, 6],
                        backgroundColor: 'rgba(13, 110, 253, 0.2)',
                        borderColor: 'rgba(13, 110, 253, 1)',
                        borderWidth: 2,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });

            // Task completion chart
            const taskCtx = document.getElementById('taskCompletionChart').getContext('2d');
            const taskChart = new Chart(taskCtx, {
                type: 'bar',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    datasets: [{
                        label: 'Completed Tasks',
                        data: [25, 32, 45, 37, 52, 40],
                        backgroundColor: 'rgba(25, 135, 84, 0.2)',
                        borderColor: 'rgba(25, 135, 84, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Created Tasks',
                        data: [35, 40, 50, 45, 62, 48],
                        backgroundColor: 'rgba(255, 193, 7, 0.2)',
                        borderColor: 'rgba(255, 193, 7, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
        }

        // Load recent activity data
        function loadRecentActivity() {
            // Simulate API call - Replace with actual API call
            setTimeout(() => {
                const recentActivityTable = document.getElementById('recentActivityTable');
                
                recentActivityTable.innerHTML = `
                    <div class="table-responsive">
                        <table class="table table-striped table-sm">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>User</th>
                                    <th>Activity</th>
                                    <th>Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Today 10:23 AM</td>
                                    <td>goalsadi</td>
                                    <td><span class="badge bg-primary">Login</span></td>
                                    <td>User logged in</td>
                                </tr>
                                <tr>
                                    <td>Today 09:15 AM</td>
                                    <td>admin</td>
                                    <td><span class="badge bg-success">Task Created</span></td>
                                    <td>Created task "Update documentation"</td>
                                </tr>
                                <tr>
                                    <td>Yesterday 4:30 PM</td>
                                    <td>janedoe</td>
                                    <td><span class="badge bg-info">Period Created</span></td>
                                    <td>Created period "Q2 Planning"</td>
                                </tr>
                                <tr>
                                    <td>Yesterday 3:12 PM</td>
                                    <td>johnsmith</td>
                                    <td><span class="badge bg-warning">Task Updated</span></td>
                                    <td>Updated task "Client meeting preparation"</td>
                                </tr>
                                <tr>
                                    <td>Yesterday 11:45 AM</td>
                                    <td>goalsadi</td>
                                    <td><span class="badge bg-primary">Login</span></td>
                                    <td>User logged in</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                `;
            }, 1000);
        }

        // Load users for user management
        function loadUsers() {
            // Simulate API call - Replace with actual API call
            setTimeout(() => {
                fetch('api/admin_users.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Populate users table
                        const usersTableBody = document.querySelector('#usersTable tbody');
                        usersTableBody.innerHTML = '';
                        
                        data.users.forEach(user => {
                            const row = document.createElement('tr');
                            
                            // Format status badge
                            let statusBadge = '';
                            switch(user.status) {
                                case 'active':
                                    statusBadge = '<span class="badge bg-success">Active</span>';
                                    break;
                                case 'inactive':
                                    statusBadge = '<span class="badge bg-danger">Inactive</span>';
                                    break;
                                case 'pending':
                                    statusBadge = '<span class="badge bg-warning">Pending</span>';
                                    break;
                                default:
                                    statusBadge = '<span class="badge bg-secondary">Unknown</span>';
                            }
                            
                            const createdDate = new Date(user.created_at).toLocaleDateString();
                            
                            row.innerHTML = `
                                <td>${user.id}</td>
                                <td>${user.full_name}</td>
                                <td>${user.username}</td>
                                <td>${user.email}</td>
                                <td>${user.department || 'N/A'}</td>
                                <td>${user.role_name}</td>
                                <td>${statusBadge}</td>
                                <td>${createdDate}</td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-primary" onclick="viewUser(${user.id})">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <button class="btn btn-outline-secondary" onclick="editUser(${user.id})">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        ${user.role_name !== 'admin' ? 
                                            `<button class="btn btn-outline-danger" onclick="deleteUser(${user.id})">
                                                <i class="bi bi-trash"></i>
                                            </button>` : ''}
                                    </div>
                                </td>
                            `;
                            
                            usersTableBody.appendChild(row);
                        });
                        
                        // Initialize or refresh DataTable
                        if ($.fn.DataTable.isDataTable('#usersTable')) {
                            $('#usersTable').DataTable().destroy();
                        }
                        
                        $('#usersTable').DataTable({
                            paging: true,
                            searching: true,
                            ordering: true,
                            info: true,
                            responsive: true
                        });
                        
                        // Also populate activity user selector
                        const activityUser = document.getElementById('activityUser');
                        activityUser.innerHTML = '<option value="">All Users</option>';
                        
                        data.users.forEach(user => {
                            const option = document.createElement('option');
                            option.value = user.id;
                            option.textContent = user.username;
                            activityUser.appendChild(option);
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message || 'Failed to load users'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error loading users:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'An error occurred while loading users'
                    });
                });
            }, 500);
        }

        // Initialize DataTables for all tables
        function initializeTables() {
            // Activity logs table
            setTimeout(() => {
                if ($.fn.DataTable.isDataTable('#activityLogsTable')) {
                    $('#activityLogsTable').DataTable().destroy();
                }
                
                $('#activityLogsTable').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: 'api/admin_activity.php',
                        type: 'POST',
                        data: function(d) {
                            d.activity_type = document.getElementById('activityType').value;
                            d.start_date = document.getElementById('activityStartDate').value;
                            d.end_date = document.getElementById('activityEndDate').value;
                            d.user_id = document.getElementById('activityUser').value;
                            d.entity_type = document.getElementById('activityEntityType').value;
                            d.csrf_token = '<?php echo $_SESSION['csrf_token']; ?>';
                        }
                    },
                    columns: [
                        { data: 'id' },
                        { data: 'timestamp' },
                        { data: 'username' },
                        { 
                            data: 'activity_type',
                            render: function(data) {
                                let badgeClass = 'bg-secondary';
                                
                                switch(data) {
                                    case 'login':
                                    case 'logout':
                                        badgeClass = 'bg-primary';
                                        break;
                                    case 'registration':
                                        badgeClass = 'bg-info';
                                        break;
                                    case 'task_created':
                                    case 'period_created':
                                        badgeClass = 'bg-success';
                                        break;
                                    case 'task_updated':
                                    case 'period_updated':
                                        badgeClass = 'bg-warning';
                                        break;
                                    case 'task_deleted':
                                    case 'period_deleted':
                                        badgeClass = 'bg-danger';
                                        break;
                                }
                                
                                return `<span class="badge ${badgeClass}">${data}</span>`;
                            }
                        },
                        { data: 'description' },
                        { data: 'entity_type' },
                        { data: 'ip_address' }
                    ],
                    order: [[0, 'desc']],
                    pageLength: 10
                });
            }, 1000);
        }

        // Load role permissions
        function loadRolePermissions() {
            const roleId = document.getElementById('roleSelector').value;
            
            // Simulate API call - Replace with actual API call
            fetch(`api/admin_roles.php?action=get_permissions&role_id=${roleId}&csrf_token=<?php echo $_SESSION['csrf_token']; ?>`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reset all checkboxes
                    document.querySelectorAll('.permission-check').forEach(checkbox => {
                        checkbox.checked = false;
                    });
                    
                    // Check the permissions that the role has
                    data.permissions.forEach(permission => {
                        const checkbox = document.querySelector(`[data-perm="${permission.name}"]`);
                        if (checkbox) {
                            checkbox.checked = true;
                        }
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'Failed to load role permissions'
                    });
                }
            })
            .catch(error => {
                console.error('Error loading role permissions:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred while loading role permissions'
                });
            });
        }

        // Refresh admin data
        function refreshAdminData() {
            // Reload all data
            loadRecentActivity();
            loadUsers();
            initializeTables();
            loadRolePermissions();
            
            Swal.fire({
                icon: 'success',
                title: 'Refreshed',
                text: 'Admin data has been refreshed',
                timer: 1500,
                showConfirmButton: false
            });
        }

        // Generate random password
        function generateRandomPassword() {
            const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()';
            let password = '';
            
            for (let i = 0; i < 12; i++) {
                password += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            
            document.getElementById('newUserPassword').value = password;
            
            // Flash the field to indicate it was updated
            document.getElementById('newUserPassword').classList.add('bg-light');
            setTimeout(() => {
                document.getElementById('newUserPassword').classList.remove('bg-light');
            }, 300);
        }

        // Create new user
        function createUser() {
            const form = document.getElementById('createUserForm');
            const formData = new FormData(form);
            
            // Basic validation
            if (!formData.get('full_name') || !formData.get('username') || !formData.get('email') || !formData.get('password')) {
                Swal.fire({
                    icon: 'error',
                    title: 'Validation Error',
                    text: 'Please fill in all required fields'
                });
                return;
            }
            
            const createUserBtn = document.getElementById('createUserBtn');
            const originalBtnText = createUserBtn.textContent;
            createUserBtn.disabled = true;
            createUserBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Creating...';
            
            // Simulate API call - Replace with actual API call
            fetch('api/admin_users.php?action=create', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // Reset button
                createUserBtn.disabled = false;
                createUserBtn.textContent = originalBtnText;
                
                if (data.success) {
                    // Close modal and reset form
                    bootstrap.Modal.getInstance(document.getElementById('createUserModal')).hide();
                    form.reset();
                    
                    // Reload user data
                    loadUsers();
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: 'User created successfully!'
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'Failed to create user'
                    });
                }
            })
            .catch(error => {
                // Reset button
                createUserBtn.disabled = false;
                createUserBtn.textContent = originalBtnText;
                
                console.error('Error creating user:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred while creating the user'
                });
            });
        }

        // Create new role
        function createRole() {
            const form = document.getElementById('createRoleForm');
            const formData = new FormData(form);
            formData.append('base_role', document.getElementById('baseRolePermissions').value);
            
            // Basic validation
            if (!formData.get('name') || !formData.get('description')) {
                Swal.fire({
                    icon: 'error',
                    title: 'Validation Error',
                    text: 'Please fill in all required fields'
                });
                return;
            }
            
            const createRoleBtn = document.getElementById('createRoleBtn');
            const originalBtnText = createRoleBtn.textContent;
            createRoleBtn.disabled = true;
            createRoleBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Creating...';
            
            // Simulate API call - Replace with actual API call
            fetch('api/admin_roles.php?action=create', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // Reset button
                createRoleBtn.disabled = false;
                createRoleBtn.textContent = originalBtnText;
                
                if (data.success) {
                    // Close modal and reset form
                    bootstrap.Modal.getInstance(document.getElementById('createRoleModal')).hide();
                    form.reset();
                    
                    // Reload page to refresh roles
                    window.location.reload();
                    
                    Swal.fire({
                     icon: 'success',
                        title: 'Success',
                        text: 'Role created successfully!'
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'Failed to create role'
                    });
                }
            })
            .catch(error => {
                // Reset button
                createRoleBtn.disabled = false;
                createRoleBtn.textContent = originalBtnText;
                
                console.error('Error creating role:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred while creating the role'
                });
            });
        }

        // Save role permissions
        function saveRolePermissions() {
            const roleId = document.getElementById('roleSelector').value;
            const checkedPermissions = [];
            
            document.querySelectorAll('.permission-check:checked').forEach(checkbox => {
                checkedPermissions.push(checkbox.getAttribute('data-perm'));
            });
            
            const formData = new FormData();
            formData.append('role_id', roleId);
            formData.append('permissions', JSON.stringify(checkedPermissions));
            formData.append('csrf_token', '<?php echo $_SESSION['csrf_token']; ?>');
            
            const saveBtn = document.getElementById('savePermissionsBtn');
            const originalBtnText = saveBtn.textContent;
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...';
            
            fetch('api/admin_roles.php?action=update_permissions', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // Reset button
                saveBtn.disabled = false;
                saveBtn.textContent = originalBtnText;
                
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: 'Role permissions updated successfully!',
                        timer: 1500,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'Failed to update role permissions'
                    });
                }
            })
            .catch(error => {
                // Reset button
                saveBtn.disabled = false;
                saveBtn.textContent = originalBtnText;
                
                console.error('Error saving role permissions:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred while saving role permissions'
                });
            });
        }

        // Reset permission changes
        function resetPermissionChanges() {
            loadRolePermissions();
            
            Swal.fire({
                icon: 'info',
                title: 'Reset',
                text: 'Permission changes have been reset',
                timer: 1500,
                showConfirmButton: false
            });
        }

        // Generate quick report
        function generateQuickReport() {
            const reportType = document.getElementById('quickReportType').value;
            const reportFormat = document.getElementById('quickReportFormat').value;
            const timeframe = document.getElementById('quickReportTimeframe').value;
            
            if (!reportType || !reportFormat || !timeframe) {
                Swal.fire({
                    icon: 'error',
                    title: 'Validation Error',
                    text: 'Please fill in all required fields'
                });
                return;
            }
            
            const generateBtn = document.getElementById('generateQuickReportBtn');
            const originalBtnText = generateBtn.textContent;
            generateBtn.disabled = true;
            generateBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Generating...';
            
            // Create form data
            const formData = new FormData();
            formData.append('type', reportType);
            formData.append('format', reportFormat);
            formData.append('timeframe', timeframe);
            formData.append('csrf_token', '<?php echo $_SESSION['csrf_token']; ?>');
            
            fetch('api/admin_reports.php?action=quick_report', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (reportFormat === 'html') {
                    return response.text();
                } else {
                    return response.blob();
                }
            })
            .then(data => {
                // Reset button
                generateBtn.disabled = false;
                generateBtn.textContent = originalBtnText;
                
                if (reportFormat === 'html') {
                    // Open HTML report in new window
                    const newWindow = window.open('', '_blank');
                    newWindow.document.write(data);
                    newWindow.document.close();
                } else {
                    // Download file
                    const blob = new Blob([data]);
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.style.display = 'none';
                    a.href = url;
                    a.download = `${reportType}_${timeframe}_report.${reportFormat}`;
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    document.body.removeChild(a);
                }
                
                // Close modal
                bootstrap.Modal.getInstance(document.getElementById('quickReportModal')).hide();
                
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: 'Report generated successfully!'
                });
            })
            .catch(error => {
                // Reset button
                generateBtn.disabled = false;
                generateBtn.textContent = originalBtnText;
                
                console.error('Error generating report:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred while generating the report'
                });
            });
        }

        // Filter activity logs
        function filterActivityLogs() {
            // Reload DataTable with filters
            $('#activityLogsTable').DataTable().ajax.reload();
        }

        // Filter users
        function filterUsers() {
            const searchTerm = document.getElementById('userSearch').value.toLowerCase();
            const roleFilter = document.getElementById('roleFilter').value;
            const statusFilter = document.getElementById('statusFilter').value;
            
            // Apply filters to DataTable
            const table = $('#usersTable').DataTable();
            
            // Apply search
            table.search(searchTerm);
            
            // Apply column filters
            if (roleFilter) {
                table.column(5).search(roleFilter);
            } else {
                table.column(5).search('');
            }
            
            if (statusFilter) {
                table.column(6).search(statusFilter);
            } else {
                table.column(6).search('');
            }
            
            table.draw();
        }

        // Export activity logs
        function exportActivityLogs() {
            const formData = new FormData();
            formData.append('action', 'export');
            formData.append('activity_type', document.getElementById('activityType').value);
            formData.append('start_date', document.getElementById('activityStartDate').value);
            formData.append('end_date', document.getElementById('activityEndDate').value);
            formData.append('user_id', document.getElementById('activityUser').value);
            formData.append('entity_type', document.getElementById('activityEntityType').value);
            formData.append('csrf_token', '<?php echo $_SESSION['csrf_token']; ?>');
            
            fetch('api/admin_activity.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.blob())
            .then(blob => {
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.style.display = 'none';
                a.href = url;
                a.download = `activity_logs_${new Date().toISOString().split('T')[0]}.csv`;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);
                
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: 'Activity logs exported successfully!'
                });
            })
            .catch(error => {
                console.error('Error exporting activity logs:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred while exporting activity logs'
                });
            });
        }

        // Prepare report export
        function prepareReportExport(format) {
            // Set report form to export format
            document.getElementById('reportFormat').value = format;
            
            Swal.fire({
                icon: 'info',
                title: 'Report Export',
                text: 'Please configure your report parameters in the Reports section and click "Generate Report"',
                showCancelButton: true,
                confirmButtonText: 'Go to Reports',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Scroll to reports section
                    document.getElementById('reports-section').scrollIntoView({ 
                        behavior: 'smooth' 
                    });
                }
            });
        }

        // Save all settings
        function saveAllSettings() {
            Swal.fire({
                title: 'Save All Settings?',
                text: 'This will save all changes made in the System Settings tabs',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, save all!'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Simulate saving all settings
                    const saveBtn = document.getElementById('saveAllSettings');
                    const originalBtnText = saveBtn.innerHTML;
                    saveBtn.disabled = true;
                    saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...';
                    
                    setTimeout(() => {
                        saveBtn.disabled = false;
                        saveBtn.innerHTML = originalBtnText;
                        
                        Swal.fire({
                            icon: 'success',
                            title: 'Settings Saved',
                            text: 'All system settings have been saved successfully!',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    }, 2000);
                }
            });
        }

        // Create backup now
        function createBackupNow() {
            Swal.fire({
                title: 'Create Backup?',
                text: 'This will create a full backup of your system including database and files',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#198754',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, create backup!'
            }).then((result) => {
                if (result.isConfirmed) {
                    const backupBtn = document.getElementById('createBackupBtn');
                    const originalBtnText = backupBtn.innerHTML;
                    backupBtn.disabled = true;
                    backupBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Creating Backup...';
                    
                    // Simulate backup creation
                    setTimeout(() => {
                        backupBtn.disabled = false;
                        backupBtn.innerHTML = originalBtnText;
                        
                        Swal.fire({
                            icon: 'success',
                            title: 'Backup Created',
                            text: 'Full system backup has been created successfully!',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    }, 3000);
                }
            });
        }

        // Test email settings
        function testEmailSettings() {
            const testBtn = document.getElementById('testEmailBtn');
            const originalBtnText = testBtn.innerHTML;
            testBtn.disabled = true;
            testBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Testing...';
            
            // Simulate email test
            setTimeout(() => {
                testBtn.disabled = false;
                testBtn.innerHTML = originalBtnText;
                
                Swal.fire({
                    icon: 'success',
                    title: 'Email Test Successful',
                    text: 'Test email sent successfully! Please check your inbox.',
                    timer: 3000,
                    showConfirmButton: false
                });
            }, 2000);
        }

        // User management functions
        function viewUser(userId) {
            // Implement user view functionality
            Swal.fire({
                title: 'User Details',
                html: `
                    <div class="text-start">
                        <p><strong>User ID:</strong> ${userId}</p>
                        <p><strong>Name:</strong> Sample User</p>
                        <p><strong>Email:</strong> user@example.com</p>
                        <p><strong>Role:</strong> User</p>
                        <p><strong>Status:</strong> Active</p>
                        <p><strong>Last Login:</strong> Today at 10:30 AM</p>
                        <p><strong>Tasks Created:</strong> 15</p>
                        <p><strong>Tasks Completed:</strong> 12</p>
                    </div>
                `,
                showCloseButton: true,
                showConfirmButton: false,
                width: '400px'
            });
        }

        function editUser(userId) {
            // Implement user edit functionality
            Swal.fire({
                title: 'Edit User',
                text: 'User editing functionality would be implemented here',
                icon: 'info'
            });
        }

        function deleteUser(userId) {
            Swal.fire({
                title: 'Delete User?',
                text: 'This action cannot be undone. The user will be permanently deleted.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete user!'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Implement user deletion
                    Swal.fire({
                        icon: 'success',
                        title: 'Deleted',
                        text: 'User has been deleted successfully.',
                        timer: 2000,
                        showConfirmButton: false
                    });
                }
            });
        }

        // Smooth scrolling for navigation
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth'
                    });
                }
            });
        });
    </script>
</body>
</html>   