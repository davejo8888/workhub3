<?php
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__); // Defines ROOT_PATH as the directory of dashboard.php
}
require_once 'auth/session.php'; // Now session.php (and config.php) can be loaded correctly

// Require login to access this page
requireLogin();

// Get current user data
$user = getCurrentUser();
if (!$user) {
    redirect('login.php');
}

// Get user permissions
$db = Database::getInstance();
$permissions = getUserPermissions($user['role_id']);
$permissionNames = array_column($permissions, 'name');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-dark sidebar collapse">
                <div class="position-sticky pt-3">
                    <div class="px-3 py-2 d-flex justify-content-between align-items-center">
                        <a href="dashboard.php" class="text-white text-decoration-none">
                            <h3><?php echo SITE_NAME; ?></h3>
                        </a>
                        <button class="btn btn-link d-md-none" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar">
                            <i class="bi bi-x text-white fs-2"></i>
                        </button>
                    </div>
                    <hr class="text-light">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="dashboard.php">
                                <i class="bi bi-speedometer2 me-2"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#periods-section" data-bs-toggle="modal" data-bs-target="#periodsModal">
                                <i class="bi bi-calendar3 me-2"></i> Periods
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#tasks-section" data-bs-toggle="modal" data-bs-target="#tasksModal">
                                <i class="bi bi-check2-square me-2"></i> Tasks
                            </a>
                        </li>
                        <?php if (in_array('view_reports', $permissionNames)): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="#reports-section">
                                <i class="bi bi-file-earmark-bar-graph me-2"></i> Reports
                            </a>
                        </li>
                        <?php endif; ?>
                        <?php if (in_array('manage_settings', $permissionNames)): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="#settings-section">
                                <i class="bi bi-gear me-2"></i> Settings
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                    
                    <hr class="text-light">
                    <div class="px-3">
                        <div class="dropdown">
                            <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user['full_name']); ?>&background=random" alt="User" class="rounded-circle me-2" width="32" height="32">
                                <span><?php echo htmlspecialchars($user['full_name']); ?></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-dark text-small shadow" aria-labelledby="userDropdown">
                                <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php">Sign out</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="me-2">
                            <button type="button" class="btn btn-sm btn-outline-primary d-md-none" data-bs-toggle="collapse" data-bs-target="#sidebar">
                                <i class="bi bi-list"></i> Menu
                            </button>
                        </div>
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="refreshDashboard">
                                <i class="bi bi-arrow-clockwise"></i> Refresh
                            </button>
                        </div>
                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#newTaskModal">
                            <i class="bi bi-plus-lg"></i> New Task
                        </button>
                    </div>
                </div>

                <!-- Dashboard Overview Cards -->
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="card text-white bg-primary">
                            <div class="card-body">
                                <h5 class="card-title">Active Periods</h5>
                                <h2 class="card-text" id="activePeriods">-</h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-success">
                            <div class="card-body">
                                <h5 class="card-title">Open Tasks</h5>
                                <h2 class="card-text" id="openTasks">-</h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-info">
                            <div class="card-body">
                                <h5 class="card-title">Completed Tasks</h5>
                                <h2 class="card-text" id="completedTasks">-</h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-warning">
                            <div class="card-body">
                                <h5 class="card-title">Upcoming Deadlines</h5>
                                <h2 class="card-text" id="upcomingDeadlines">-</h2>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Periods and Task Progress -->
                <div class="row g-4 mb-4">
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Recent Periods</h5>
                                <a href="#periods-section" class="btn btn-sm btn-outline-secondary" 
                                   data-bs-toggle="modal" data-bs-target="#periodsModal">View All</a>
                            </div>
                            <div class="card-body" id="recentPeriods">
                                <div class="text-center py-3">Loading periods...</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Task Progress</h5>
                                <a href="#tasks-section" class="btn btn-sm btn-outline-secondary" 
                                   data-bs-toggle="modal" data-bs-target="#tasksModal">View All</a>
                            </div>
                            <div class="card-body" id="taskProgress">
                                <div class="text-center py-3">Loading task progress...</div>
                            </div>
                            <div class="card-footer bg-transparent">
                                <div class="progress" style="height: 10px;">
                                    <div id="progressBar" class="progress-bar bg-success" role="progressbar" 
                                         style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Upcoming Deadlines -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Upcoming Deadlines</h5>
                            </div>
                            <div class="card-body" id="deadlinesList">
                                <div class="text-center py-3">Loading deadlines...</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sections -->
                <section id="periods-section" class="mb-4">
                    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                        <h2>Periods</h2>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newPeriodModal">
                            <i class="bi bi-plus-lg"></i> New Period
                        </button>
                    </div>
                    <div id="periodsTableContainer">
                        <div class="text-center py-5">Loading periods...</div>
                    </div>
                </section>

                <section id="tasks-section" class="mb-4">
                    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                        <h2>Tasks</h2>
                        <div>
                            <div class="input-group">
                                <select class="form-select" id="periodFilter">
                                    <option value="">All Periods</option>
                                </select>
                                <select class="form-select" id="statusFilter">
                                    <option value="">All Statuses</option>
                                    <option value="not_started">Not Started</option>
                                    <option value="in_progress">In Progress</option>
                                    <option value="completed">Completed</option>
                                    <option value="on_hold">On Hold</option>
                                </select>
                                <button class="btn btn-outline-secondary" type="button" id="filterTasks">
                                    <i class="bi bi-funnel"></i> Filter
                                </button>
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newTaskModal">
                                    <i class="bi bi-plus-lg"></i> New Task
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Task</th>
                                    <th>Period</th>
                                    <th>Status</th>
                                    <th>Priority</th>
                                    <th>Deadline</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="tasksTableBody">
                                <tr>
                                    <td colspan="6" class="text-center">Loading tasks...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <?php if (in_array('view_reports', $permissionNames)): ?>
                <section id="reports-section" class="mb-4">
                    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                        <h2>Reports</h2>
                        <div class="btn-toolbar mb-2 mb-md-0">
                            <div class="btn-group me-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-download"></i> Export
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle-fill me-2"></i>
                        Reports functionality will be added in a future update.
                    </div>
                </section>
                <?php endif; ?>

                <?php if (in_array('manage_settings', $permissionNames)): ?>
                <section id="settings-section" class="mb-4">
                    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                        <h2>Settings</h2>
                    </div>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle-fill me-2"></i>
                        Settings functionality will be added in a future update.
                    </div>
                </section>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <!-- New Task Modal -->
    <div class="modal fade" id="newTaskModal" tabindex="-1" aria-labelledby="newTaskModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="newTaskModalLabel">Create New Task</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="newTaskForm">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        
                        <div class="mb-3">
                            <label for="taskTitle" class="form-label">Task Title</label>
                            <input type="text" class="form-control" id="taskTitle" name="title" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="taskDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="taskDescription" name="description" rows="3"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="taskPeriod" class="form-label">Period</label>
                            <select class="form-select" id="taskPeriod" name="period_id" required>
                                <option value="">Select Period</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="taskPriority" class="form-label">Priority</label>
                            <select class="form-select" id="taskPriority" name="priority" required>
                                <option value="low">Low</option>
                                <option value="medium" selected>Medium</option>
                                <option value="high">High</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="taskDeadline" class="form-label">Deadline</label>
                            <input type="date" class="form-control" id="taskDeadline" name="deadline">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveTaskBtn">Create Task</button>
                </div>
            </div>
        </div>
    </div>

    <!-- New Period Modal -->
    <div class="modal fade" id="newPeriodModal" tabindex="-1" aria-labelledby="newPeriodModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="newPeriodModalLabel">Create New Period</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="newPeriodForm">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        
                        <div class="mb-3">
                            <label for="periodName" class="form-label">Period Name</label>
                            <input type="text" class="form-control" id="periodName" name="name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="periodDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="periodDescription" name="description" rows="3"></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="periodStartDate" class="form-label">Start Date</label>
                                <input type="date" class="form-control" id="periodStartDate" name="start_date" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="periodEndDate" class="form-label">End Date</label>
                                <input type="date" class="form-control" id="periodEndDate" name="end_date" required>
                            </div>
                        </div>
                    </form>
                </div>
            <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="savePeriodBtn">Create Period</button>
                </div>
            </div>
        </div>
    </div>

    <!-- View Periods Modal -->
    <div class="modal fade" id="periodsModal" tabindex="-1" aria-labelledby="periodsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="periodsModalLabel">All Periods</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Tasks</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="periodsModalTableBody">
                                <tr>
                                    <td colspan="6" class="text-center">Loading periods...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- View Tasks Modal -->
    <div class="modal fade" id="tasksModal" tabindex="-1" aria-labelledby="tasksModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="tasksModalLabel">All Tasks</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-8">
                            <div class="input-group">
                                <select class="form-select" id="modalPeriodFilter">
                                    <option value="">All Periods</option>
                                </select>
                                <select class="form-select" id="modalStatusFilter">
                                    <option value="">All Statuses</option>
                                    <option value="not_started">Not Started</option>
                                    <option value="in_progress">In Progress</option>
                                    <option value="completed">Completed</option>
                                    <option value="on_hold">On Hold</option>
                                </select>
                                <button class="btn btn-outline-secondary" type="button" id="modalFilterTasks">
                                    <i class="bi bi-funnel"></i> Filter
                                </button>
                            </div>
                        </div>
                        <div class="col-md-4 text-end">
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newTaskModal">
                                <i class="bi bi-plus-lg"></i> New Task
                            </button>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Task</th>
                                    <th>Period</th>
                                    <th>Status</th>
                                    <th>Priority</th>
                                    <th>Deadline</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="tasksModalTableBody">
                                <tr>
                                    <td colspan="6" class="text-center">Loading tasks...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Task Modal -->
    <div class="modal fade" id="editTaskModal" tabindex="-1" aria-labelledby="editTaskModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editTaskModalLabel">Edit Task</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editTaskForm">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" id="editTaskId" name="task_id">
                        
                        <div class="mb-3">
                            <label for="editTaskTitle" class="form-label">Task Title</label>
                            <input type="text" class="form-control" id="editTaskTitle" name="title" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="editTaskDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="editTaskDescription" name="description" rows="3"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="editTaskPeriod" class="form-label">Period</label>
                            <select class="form-select" id="editTaskPeriod" name="period_id" required>
                                <option value="">Select Period</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="editTaskStatus" class="form-label">Status</label>
                            <select class="form-select" id="editTaskStatus" name="status" required>
                                <option value="not_started">Not Started</option>
                                <option value="in_progress">In Progress</option>
                                <option value="completed">Completed</option>
                                <option value="on_hold">On Hold</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="editTaskPriority" class="form-label">Priority</label>
                            <select class="form-select" id="editTaskPriority" name="priority" required>
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="editTaskDeadline" class="form-label">Deadline</label>
                            <input type="date" class="form-control" id="editTaskDeadline" name="deadline">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="updateTaskBtn">Update Task</button>
                </div>
            </div>
        </div>
    </div>

    <!-- View Task Details Modal -->
    <div class="modal fade" id="viewTaskModal" tabindex="-1" aria-labelledby="viewTaskModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewTaskModalLabel">Task Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <h4 id="viewTaskTitle"></h4>
                        <span id="viewTaskPeriod" class="badge bg-primary"></span>
                        <span id="viewTaskStatus" class="badge"></span>
                        <span id="viewTaskPriority" class="badge"></span>
                    </div>
                    <div class="mb-3">
                        <h6>Description</h6>
                        <p id="viewTaskDescription"></p>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6>Created By</h6>
                            <p id="viewTaskCreator"></p>
                        </div>
                        <div class="col-md-6">
                            <h6>Deadline</h6>
                            <p id="viewTaskDeadline"></p>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6>Created On</h6>
                            <p id="viewTaskCreatedAt"></p>
                        </div>
                        <div class="col-md-6">
                            <h6>Last Updated</h6>
                            <p id="viewTaskUpdatedAt"></p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="editTaskBtn">Edit Task</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        // Global variables for storing data
        let allPeriods = [];
        let allTasks = [];
        
        // DOM ready
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize dashboard data
            loadAllData();
            
            // Event listeners for task and period creation
            document.getElementById('saveTaskBtn').addEventListener('click', createTask);
            document.getElementById('savePeriodBtn').addEventListener('click', createPeriod);
            document.getElementById('updateTaskBtn').addEventListener('click', updateTask);
            document.getElementById('filterTasks').addEventListener('click', filterTasks);
            document.getElementById('modalFilterTasks').addEventListener('click', filterModalTasks);
            document.getElementById('refreshDashboard').addEventListener('click', loadAllData);
        });
        
        // Load all dashboard data
        function loadAllData() {
            loadPeriods();
            loadTasks();
            loadDashboardStats();
            
            // Update the UI elements
            const activePeriods = document.getElementById('activePeriods');
            const openTasks = document.getElementById('openTasks');
            const completedTasks = document.getElementById('completedTasks');
            const upcomingDeadlines = document.getElementById('upcomingDeadlines');
            
            if (activePeriods) activePeriods.textContent = '...';
            if (openTasks) openTasks.textContent = '...';
            if (completedTasks) completedTasks.textContent = '...';
            if (upcomingDeadlines) upcomingDeadlines.textContent = '...';
        }
        
        // Load periods data
        function loadPeriods() {
            fetch('api/periods.php', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    allPeriods = data.periods;
                    
                    // Populate period dropdowns
                    const periodSelect = document.getElementById('taskPeriod');
                    const editPeriodSelect = document.getElementById('editTaskPeriod');
                    const periodFilter = document.getElementById('periodFilter');
                    const modalPeriodFilter = document.getElementById('modalPeriodFilter');
                    
                    // Clear existing options except the first one
                    while (periodSelect.options.length > 1) {
                        periodSelect.remove(1);
                    }
                    
                    while (editPeriodSelect.options.length > 1) {
                        editPeriodSelect.remove(1);
                    }
                    
                    while (periodFilter.options.length > 1) {
                        periodFilter.remove(1);
                    }
                    
                    while (modalPeriodFilter.options.length > 1) {
                        modalPeriodFilter.remove(1);
                    }
                    
                    // Add new options
                    allPeriods.forEach(period => {
                        const option = document.createElement('option');
                        option.value = period.id;
                        option.textContent = period.name;
                        
                        const option2 = option.cloneNode(true);
                        const option3 = option.cloneNode(true);
                        const option4 = option.cloneNode(true);
                        
                        periodSelect.appendChild(option);
                        editPeriodSelect.appendChild(option2);
                        periodFilter.appendChild(option3);
                        modalPeriodFilter.appendChild(option4);
                    });
                    
                    // Update Periods table
                    updatePeriodsTable();
                    
                    // Update recent periods on dashboard
                    updateRecentPeriods();
                }
            })
            .catch(error => {
                console.error('Error loading periods:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error Loading Periods',
                    text: 'Could not load periods. Please try refreshing the page.'
                });
            });
        }
        
        // Load tasks data
        function loadTasks() {
            fetch('api/tasks.php', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    allTasks = data.tasks;
                    
                    // Update tasks table
                    updateTasksTable();
                    
                    // Update task progress on dashboard
                    updateTaskProgress();
                    
                    // Update deadlines list
                    updateDeadlinesList();
                }
            })
            .catch(error => {
                console.error('Error loading tasks:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error Loading Tasks',
                    text: 'Could not load tasks. Please try refreshing the page.'
                });
            });
        }
        
        // Load dashboard statistics
        function loadDashboardStats() {
            fetch('api/dashboard_stats.php', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update stat cards
                    document.getElementById('activePeriods').textContent = data.stats.active_periods;
                    document.getElementById('openTasks').textContent = data.stats.open_tasks;
                    document.getElementById('completedTasks').textContent = data.stats.completed_tasks;
                    document.getElementById('upcomingDeadlines').textContent = data.stats.upcoming_deadlines;
                    
                    // Update progress bar
                    const progressBar = document.getElementById('progressBar');
                    const progressPercent = data.stats.completed_percentage || 0;
                    progressBar.style.width = `${progressPercent}%`;
                    progressBar.setAttribute('aria-valuenow', progressPercent);
                }
            })
            .catch(error => {
                console.error('Error loading dashboard stats:', error);
            });
        }
        
        // Update Periods table
        function updatePeriodsTable() {
            const periodsTableContainer = document.getElementById('periodsTableContainer');
            const periodsModalTableBody = document.getElementById('periodsModalTableBody');
            
            if (!allPeriods || allPeriods.length === 0) {
                periodsTableContainer.innerHTML = `
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle-fill me-2"></i>
                        No periods found. Create your first period to get started!
                    </div>
                `;
                
                periodsModalTableBody.innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center">No periods found</td>
                    </tr>
                `;
                return;
            }
            
            // Create table for periods section
            let tableHtml = `
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Tasks</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
            `;
            
            let modalTableHtml = '';
            
            allPeriods.forEach(period => {
                const startDate = new Date(period.start_date).toLocaleDateString();
                const endDate = new Date(period.end_date).toLocaleDateString();
                const taskCount = allTasks.filter(task => task.period_id == period.id).length;
                
                const now = new Date();
                const start = new Date(period.start_date);
                const end = new Date(period.end_date);
                
                let status = '';
                let statusClass = '';
                
                if (now < start) {
                    status = 'Upcoming';
                    statusClass = 'bg-info';
                } else if (now >= start && now <= end) {
                    status = 'Active';
                    statusClass = 'bg-success';
                } else {
                    status = 'Completed';
                    statusClass = 'bg-secondary';
                }
                
                const rowHtml = `
                    <tr>
                        <td>${period.name}</td>
                        <td>${startDate}</td>
                        <td>${endDate}</td>
                        <td>${taskCount}</td>
                        <td><span class="badge ${statusClass}">${status}</span></td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" onclick="viewPeriod(${period.id})">
                                <i class="bi bi-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-secondary" onclick="editPeriod(${period.id})">
                                <i class="bi bi-pencil"></i>
                            </button>
                        </td>
                    </tr>
                `;
                
                tableHtml += rowHtml;
                modalTableHtml += rowHtml;
            });
            
            tableHtml += `
                        </tbody>
                    </table>
                </div>
            `;
            
            periodsTableContainer.innerHTML = tableHtml;
            periodsModalTableBody.innerHTML = modalTableHtml;
        }
        
        // Update tasks table
        function updateTasksTable(filteredTasks = null) {
            const tasksTableBody = document.getElementById('tasksTableBody');
            const tasksModalTableBody = document.getElementById('tasksModalTableBody');
            const tasks = filteredTasks || allTasks;
            
            if (!tasks || tasks.length === 0) {
                tasksTableBody.innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center">No tasks found</td>
                    </tr>
                `;
                
                tasksModalTableBody.innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center">No tasks found</td>
                    </tr>
                `;
                return;
            }
            
            let tableHtml = '';
            let modalTableHtml = '';
            
            tasks.forEach(task => {
                const periodName = allPeriods.find(p => p.id == task.period_id)?.name || 'Unknown';
                const deadline = task.deadline ? new Date(task.deadline).toLocaleDateString() : 'Not set';
                
                let statusBadge = '';
                switch (task.status) {
                    case 'not_started':
                        statusBadge = '<span class="badge bg-secondary">Not Started</span>';
                        break;
                    case 'in_progress':
                        statusBadge = '<span class="badge bg-primary">In Progress</span>';
                        break;
                    case 'completed':
                        statusBadge = '<span class="badge bg-success">Completed</span>';
                        break;
                    case 'on_hold':
                        statusBadge = '<span class="badge bg-warning">On Hold</span>';
                        break;
                    default:
                        statusBadge = '<span class="badge bg-secondary">Unknown</span>';
                }
                
                let priorityBadge = '';
                switch (task.priority) {
                    case 'low':
                        priorityBadge = '<span class="badge bg-info">Low</span>';
                        break;
                    case 'medium':
                        priorityBadge = '<span class="badge bg-warning">Medium</span>';
                        break;
                    case 'high':
                        priorityBadge = '<span class="badge bg-danger">High</span>';
                        break;
                    default:
                        priorityBadge = '<span class="badge bg-secondary">Unknown</span>';
                }
                
                const rowHtml = `
                    <tr>
                        <td>${task.title}</td>
                        <td>${periodName}</td>
                        <td>${statusBadge}</td>
                        <td>${priorityBadge}</td>
                        <td>${deadline}</td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" onclick="viewTask(${task.id})">
                                <i class="bi bi-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-secondary" onclick="editTask(${task.id})">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-success" onclick="updateTaskStatus(${task.id}, 'completed')">
                                <i class="bi bi-check-lg"></i>
                            </button>
                        </td>
                    </tr>
                `;
                
                tableHtml += rowHtml;
                modalTableHtml += rowHtml;
            });
            
            tasksTableBody.innerHTML = tableHtml;
            tasksModalTableBody.innerHTML = modalTableHtml;
        }
        
        // Update recent periods on dashboard
        function updateRecentPeriods() {
            const recentPeriodsContainer = document.getElementById('recentPeriods');
            
            if (!allPeriods || allPeriods.length === 0) {
                recentPeriodsContainer.innerHTML = `
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle-fill me-2"></i>
                        No periods found. Create your first period to get started!
                    </div>
                `;
                return;
            }
            
            // Sort periods by start date (most recent first)
            const sortedPeriods = [...allPeriods].sort((a, b) => {
                return new Date(b.start_date) - new Date(a.start_date);
            }).slice(0, 5); // Get top 5
            
            let html = '';
            
            sortedPeriods.forEach(period => {
                const startDate = new Date(period.start_date).toLocaleDateString();
                const endDate = new Date(period.end_date).toLocaleDateString();
                const taskCount = allTasks.filter(task => task.period_id == period.id).length;
                
                const now = new Date();
                const start = new Date(period.start_date);
                const end = new Date(period.end_date);
                
                let status = '';
                let statusClass = '';
                
                if (now < start) {
                    status = 'Upcoming';
                    statusClass = 'bg-info';
                } else if (now >= start && now <= end) {
                    status = 'Active';
                    statusClass = 'bg-success';
                } else {
                    status = 'Completed';
                    statusClass = 'bg-secondary';
                }
                
                html += `
                    <div class="d-flex justify-content-between align-items-center mb-2 border-bottom pb-2">
                        <div>
                            <h6 class="mb-0">${period.name}</h6>
                            <small class="text-muted">${startDate} - ${endDate}</small>
                        </div>
                        <div>
                            <span class="badge ${statusClass}">${status}</span>
                            <span class="badge bg-primary">${taskCount} Tasks</span>
                        </div>
                    </div>
                `;
            });
            
            recentPeriodsContainer.innerHTML = html;
        }
        
        // Update task progress on dashboard
        function updateTaskProgress() {
            const taskProgressContainer = document.getElementById('taskProgress');
            
            if (!allTasks || allTasks.length === 0) {
                taskProgressContainer.innerHTML = `
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle-fill me-2"></i>
                        No tasks found. Create your first task to track progress!
                    </div>
                `;
                return;
            }
            
            // Count tasks by status
            const statusCounts = {
                not_started: 0,
                in_progress: 0,
                completed: 0,
                on_hold: 0
            };
            
            allTasks.forEach(task => {
                if (statusCounts.hasOwnProperty(task.status)) {
                    statusCounts[task.status]++;
                }
            });
            
            // Calculate completion percentage
            const totalTasks = allTasks.length;
            const completedTasks = statusCounts.completed;
            const completionPercentage = Math.round((completedTasks / totalTasks) * 100) || 0;
            
            // Update progress bar
            const progressBar = document.getElementById('progressBar');
            progressBar.style.width = `${completionPercentage}%`;
            progressBar.setAttribute('aria-valuenow', completionPercentage);
            
            // Create HTML for task progress
            let html = `
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Completed:</span>
                            <strong>${completedTasks}/${totalTasks} (${completionPercentage}%)</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Not Started:</span>
                            <strong>${statusCounts.not_started}</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>In Progress:</span>
                            <strong>${statusCounts.in_progress}</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>On Hold:</span>
                            <strong>${statusCounts.on_hold}</strong>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <canvas id="taskStatusChart"></canvas>
                    </div>
                </div>
            `;
            
            taskProgressContainer.innerHTML = html;
            
            // Create status chart
            const ctx = document.getElementById('taskStatusChart').getContext('2d');
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Completed', 'In Progress', 'Not Started', 'On Hold'],
                    datasets: [{
                        data: [
                            statusCounts.completed, 
                            statusCounts.in_progress, 
                            statusCounts.not_started, 
                            statusCounts.on_hold
                        ],
                        backgroundColor: ['#198754', '#0d6efd', '#6c757d', '#ffc107']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                boxWidth: 12
                            }
                        }
                    }
                }
            });
        }
        
        // Update deadlines list
        function updateDeadlinesList() {
            const deadlinesContainer = document.getElementById('deadlinesList');
            
            if (!allTasks || allTasks.length === 0) {
                deadlinesContainer.innerHTML = `
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle-fill me-2"></i>
                        No tasks found. Create tasks with deadlines to track them here!
                    </div>
                `;
                return;
            }
            
            // Filter tasks with deadlines and not completed
            const tasksWithDeadlines = allTasks.filter(task => 
                task.deadline && task.status !== 'completed'
            );
            
            if (tasksWithDeadlines.length === 0) {
                deadlinesContainer.innerHTML = `
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        No upcoming deadlines. All tasks with deadlines are completed!
                    </div>
                `;
                return;
            }
            
            // Sort by deadline (earliest first)
            const sortedTasks = [...tasksWithDeadlines].sort((a, b) => {
                return new Date(a.deadline) - new Date(b.deadline);
            });
            
            // Get the next 5 deadlines
            const upcomingDeadlines = sortedTasks.slice(0, 5);
            
            let html = '<ul class="list-group list-group-flush">';
            
            upcomingDeadlines.forEach(task => {
                const deadline = new Date(task.deadline);
                const today = new Date();
                const periodName = allPeriods.find(p => p.id == task.period_id)?.name || 'Unknown';
                
                // Calculate days remaining
                const diffTime = Math.abs(deadline - today);
             const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                
                let badgeClass = 'bg-info';
                let daysText = `${diffDays} days left`;
                
                if (deadline < today) {
                    badgeClass = 'bg-danger';
                    daysText = 'Overdue';
                } else if (diffDays <= 3) {
                    badgeClass = 'bg-warning';
                }
                
                html += `
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">${task.title}</h6>
                            <small class="text-muted">${periodName} | Due: ${deadline.toLocaleDateString()}</small>
                        </div>
                        <span class="badge ${badgeClass}">${daysText}</span>
                    </li>
                `;
            });
            
            html += '</ul>';
            deadlinesContainer.innerHTML = html;
        }
        
        // Create new task
        function createTask() {
            const form = document.getElementById('newTaskForm');
            const formData = new FormData(form);
            
            // Basic validation
            if (!formData.get('title') || !formData.get('period_id')) {
                Swal.fire({
                    icon: 'error',
                    title: 'Validation Error',
                    text: 'Please fill in all required fields.'
                });
                return;
            }
            
            // Show loading spinner
            const saveBtn = document.getElementById('saveTaskBtn');
            const originalBtnText = saveBtn.textContent;
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...';
            
            fetch('api/tasks.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // Reset button
                saveBtn.disabled = false;
                saveBtn.innerHTML = originalBtnText;
                
                if (data.success) {
                    // Close modal
                    bootstrap.Modal.getInstance(document.getElementById('newTaskModal')).hide();
                    
                    // Reset form
                    form.reset();
                    
                    // Refresh data
                    loadAllData();
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: 'Task created successfully!'
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'An error occurred while creating the task.'
                    });
                }
            })
            .catch(error => {
                // Reset button
                saveBtn.disabled = false;
                saveBtn.innerHTML = originalBtnText;
                
                console.error('Error creating task:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred while creating the task.'
                });
            });
        }
        
        // Create new period
        function createPeriod() {
            const form = document.getElementById('newPeriodForm');
            const formData = new FormData(form);
            
            // Basic validation
            if (!formData.get('name') || !formData.get('start_date') || !formData.get('end_date')) {
                Swal.fire({
                    icon: 'error',
                    title: 'Validation Error',
                    text: 'Please fill in all required fields.'
                });
                return;
            }
            
            // Check if end date is after start date
            const startDate = new Date(formData.get('start_date'));
            const endDate = new Date(formData.get('end_date'));
            
            if (endDate < startDate) {
                Swal.fire({
                    icon: 'error',
                    title: 'Validation Error',
                    text: 'End date must be after start date.'
                });
                return;
            }
            
            // Show loading spinner
            const saveBtn = document.getElementById('savePeriodBtn');
            const originalBtnText = saveBtn.textContent;
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...';
            
            fetch('api/periods.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // Reset button
                saveBtn.disabled = false;
                saveBtn.innerHTML = originalBtnText;
                
                if (data.success) {
                    // Close modal
                    bootstrap.Modal.getInstance(document.getElementById('newPeriodModal')).hide();
                    
                    // Reset form
                    form.reset();
                    
                    // Refresh data
                    loadAllData();
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: 'Period created successfully!'
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'An error occurred while creating the period.'
                    });
                }
            })
            .catch(error => {
                // Reset button
                saveBtn.disabled = false;
                saveBtn.innerHTML = originalBtnText;
                
                console.error('Error creating period:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred while creating the period.'
                });
            });
        }
        
        // View task details
        function viewTask(taskId) {
            const task = allTasks.find(t => t.id == taskId);
            
            if (!task) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Task not found'
                });
                return;
            }
            
            // Set modal content
            document.getElementById('viewTaskTitle').textContent = task.title;
            document.getElementById('viewTaskDescription').textContent = task.description || 'No description provided';
            
            const periodName = allPeriods.find(p => p.id == task.period_id)?.name || 'Unknown';
            document.getElementById('viewTaskPeriod').textContent = periodName;
            
            // Set status badge
            const statusBadge = document.getElementById('viewTaskStatus');
            switch (task.status) {
                case 'not_started':
                    statusBadge.textContent = 'Not Started';
                    statusBadge.className = 'badge bg-secondary';
                    break;
                case 'in_progress':
                    statusBadge.textContent = 'In Progress';
                    statusBadge.className = 'badge bg-primary';
                    break;
                case 'completed':
                    statusBadge.textContent = 'Completed';
                    statusBadge.className = 'badge bg-success';
                    break;
                case 'on_hold':
                    statusBadge.textContent = 'On Hold';
                    statusBadge.className = 'badge bg-warning';
                    break;
                default:
                    statusBadge.textContent = 'Unknown';
                    statusBadge.className = 'badge bg-secondary';
            }
            
            // Set priority badge
            const priorityBadge = document.getElementById('viewTaskPriority');
            switch (task.priority) {
                case 'low':
                    priorityBadge.textContent = 'Low Priority';
                    priorityBadge.className = 'badge bg-info';
                    break;
                case 'medium':
                    priorityBadge.textContent = 'Medium Priority';
                    priorityBadge.className = 'badge bg-warning';
                    break;
                case 'high':
                    priorityBadge.textContent = 'High Priority';
                    priorityBadge.className = 'badge bg-danger';
                    break;
                default:
                    priorityBadge.textContent = 'Unknown Priority';
                    priorityBadge.className = 'badge bg-secondary';
            }
            
            // Set other details
            document.getElementById('viewTaskCreator').textContent = task.created_by_name || 'Unknown';
            document.getElementById('viewTaskDeadline').textContent = task.deadline ? new Date(task.deadline).toLocaleDateString() : 'Not set';
            document.getElementById('viewTaskCreatedAt').textContent = task.created_at ? new Date(task.created_at).toLocaleString() : 'Unknown';
            document.getElementById('viewTaskUpdatedAt').textContent = task.updated_at ? new Date(task.updated_at).toLocaleString() : 'Not updated';
            
            // Update edit button action
            document.getElementById('editTaskBtn').onclick = function() {
                bootstrap.Modal.getInstance(document.getElementById('viewTaskModal')).hide();
                editTask(taskId);
            };
            
            // Show modal
            const viewTaskModal = new bootstrap.Modal(document.getElementById('viewTaskModal'));
            viewTaskModal.show();
        }
        
        // Edit task
        function editTask(taskId) {
            const task = allTasks.find(t => t.id == taskId);
            
            if (!task) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Task not found'
                });
                return;
            }
            
            // Populate form
            document.getElementById('editTaskId').value = task.id;
            document.getElementById('editTaskTitle').value = task.title;
            document.getElementById('editTaskDescription').value = task.description || '';
            document.getElementById('editTaskPeriod').value = task.period_id;
            document.getElementById('editTaskStatus').value = task.status;
            document.getElementById('editTaskPriority').value = task.priority;
            
            // Format date as YYYY-MM-DD for date input
            if (task.deadline) {
                const deadline = new Date(task.deadline);
                const formattedDate = deadline.toISOString().split('T')[0];
                document.getElementById('editTaskDeadline').value = formattedDate;
            } else {
                document.getElementById('editTaskDeadline').value = '';
            }
            
            // Show modal
            const editTaskModal = new bootstrap.Modal(document.getElementById('editTaskModal'));
            editTaskModal.show();
        }
        
        // Update task
        function updateTask() {
            const form = document.getElementById('editTaskForm');
            const formData = new FormData(form);
            
            // Basic validation
            if (!formData.get('title') || !formData.get('period_id')) {
                Swal.fire({
                    icon: 'error',
                    title: 'Validation Error',
                    text: 'Please fill in all required fields.'
                });
                return;
            }
            
            // Show loading spinner
            const updateBtn = document.getElementById('updateTaskBtn');
            const originalBtnText = updateBtn.textContent;
            updateBtn.disabled = true;
            updateBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Updating...';
            
            fetch('api/tasks.php?action=update', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // Reset button
                updateBtn.disabled = false;
                updateBtn.innerHTML = originalBtnText;
                
                if (data.success) {
                    // Close modal
                    bootstrap.Modal.getInstance(document.getElementById('editTaskModal')).hide();
                    
                    // Refresh data
                    loadAllData();
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: 'Task updated successfully!'
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'An error occurred while updating the task.'
                    });
                }
            })
            .catch(error => {
                // Reset button
                updateBtn.disabled = false;
                updateBtn.innerHTML = originalBtnText;
                
                console.error('Error updating task:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred while updating the task.'
                });
            });
        }
        
        // Update task status quickly
        function updateTaskStatus(taskId, status) {
            const formData = new FormData();
            formData.append('task_id', taskId);
            formData.append('status', status);
            formData.append('csrf_token', '<?php echo $_SESSION['csrf_token']; ?>');
            
            fetch('api/tasks.php?action=update_status', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Refresh data
                    loadAllData();
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: 'Task status updated successfully!',
                        timer: 1500,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'An error occurred while updating the task status.'
                    });
                }
            })
            .catch(error => {
                console.error('Error updating task status:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred while updating the task status.'
                });
            });
        }
        
        // Filter tasks
        function filterTasks() {
            const periodFilter = document.getElementById('periodFilter').value;
            const statusFilter = document.getElementById('statusFilter').value;
            
            let filteredTasks = [...allTasks];
            
            if (periodFilter) {
                filteredTasks = filteredTasks.filter(task => task.period_id == periodFilter);
            }
            
            if (statusFilter) {
                filteredTasks = filteredTasks.filter(task => task.status === statusFilter);
            }
            
            // Update tasks table with filtered results
            updateTasksTable(filteredTasks);
        }
        
        // Filter tasks in modal
        function filterModalTasks() {
            const periodFilter = document.getElementById('modalPeriodFilter').value;
            const statusFilter = document.getElementById('modalStatusFilter').value;
            
            let filteredTasks = [...allTasks];
            
            if (periodFilter) {
                filteredTasks = filteredTasks.filter(task => task.period_id == periodFilter);
            }
            
            if (statusFilter) {
                filteredTasks = filteredTasks.filter(task => task.status === statusFilter);
            }
            
            // Update modal tasks table with filtered results
            const tasksModalTableBody = document.getElementById('tasksModalTableBody');
            
            if (!filteredTasks || filteredTasks.length === 0) {
                tasksModalTableBody.innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center">No matching tasks found</td>
                    </tr>
                `;
                return;
            }
            
            let tableHtml = '';
            
            filteredTasks.forEach(task => {
                const periodName = allPeriods.find(p => p.id == task.period_id)?.name || 'Unknown';
                const deadline = task.deadline ? new Date(task.deadline).toLocaleDateString() : 'Not set';
                
                let statusBadge = '';
                switch (task.status) {
                    case 'not_started':
                        statusBadge = '<span class="badge bg-secondary">Not Started</span>';
                        break;
                    case 'in_progress':
                        statusBadge = '<span class="badge bg-primary">In Progress</span>';
                        break;
                    case 'completed':
                        statusBadge = '<span class="badge bg-success">Completed</span>';
                        break;
                    case 'on_hold':
                        statusBadge = '<span class="badge bg-warning">On Hold</span>';
                        break;
                    default:
                        statusBadge = '<span class="badge bg-secondary">Unknown</span>';
                }
                
                let priorityBadge = '';
                switch (task.priority) {
                    case 'low':
                        priorityBadge = '<span class="badge bg-info">Low</span>';
                        break;
                    case 'medium':
                        priorityBadge = '<span class="badge bg-warning">Medium</span>';
                        break;
                    case 'high':
                        priorityBadge = '<span class="badge bg-danger">High</span>';
                        break;
                    default:
                        priorityBadge = '<span class="badge bg-secondary">Unknown</span>';
                }
                
                tableHtml += `
                    <tr>
                        <td>${task.title}</td>
                        <td>${periodName}</td>
                        <td>${statusBadge}</td>
                        <td>${priorityBadge}</td>
                        <td>${deadline}</td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" onclick="viewTask(${task.id})">
                                <i class="bi bi-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-secondary" onclick="editTask(${task.id})">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-success" onclick="updateTaskStatus(${task.id}, 'completed')">
                                <i class="bi bi-check-lg"></i>
                            </button>
                        </td>
                    </tr>
                `;
            });
            
            tasksModalTableBody.innerHTML = tableHtml;
        }
        
        // View period details
        function viewPeriod(periodId) {
            const period = allPeriods.find(p => p.id == periodId);
            
            if (!period) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Period not found'
                });
                return;
            }
            
            const startDate = new Date(period.start_date).toLocaleDateString();
            const endDate = new Date(period.end_date).toLocaleDateString();
            const periodTasks = allTasks.filter(task => task.period_id == periodId);
            
            // Calculate tasks by status
            const statusCounts = {
                not_started: 0,
                in_progress: 0,
                completed: 0,
                on_hold: 0
            };
            
            periodTasks.forEach(task => {
                if (statusCounts.hasOwnProperty(task.status)) {
                    statusCounts[task.status]++;
                }
            });
            
            // Calculate completion percentage
            const totalTasks = periodTasks.length;
            const completionPercentage = totalTasks > 0 
                ? Math.round((statusCounts.completed / totalTasks) * 100) 
                : 0;
            
            // Create tasks list HTML
            let tasksHtml = '';
            
            if (periodTasks.length === 0) {
                tasksHtml = '<div class="text-center p-3">No tasks found for this period</div>';
            } else {
                tasksHtml = '<ul class="list-group list-group-flush">';
                
                periodTasks.forEach(task => {
                    let statusBadge = '';
                    switch (task.status) {
                        case 'not_started':
                            statusBadge = '<span class="badge bg-secondary">Not Started</span>';
                            break;
                        case 'in_progress':
                            statusBadge = '<span class="badge bg-primary">In Progress</span>';
                            break;
                        case 'completed':
                            statusBadge = '<span class="badge bg-success">Completed</span>';
                            break;
                        case 'on_hold':
                            statusBadge = '<span class="badge bg-warning">On Hold</span>';
                            break;
                        default:
                            statusBadge = '<span class="badge bg-secondary">Unknown</span>';
                    }
                    
                    let priorityBadge = '';
                    switch (task.priority) {
                        case 'low':
                            priorityBadge = '<span class="badge bg-info">Low</span>';
                            break;
                        case 'medium':
                            priorityBadge = '<span class="badge bg-warning">Medium</span>';
                            break;
                        case 'high':
                            priorityBadge = '<span class="badge bg-danger">High</span>';
                            break;
                        default:
                            priorityBadge = '<span class="badge bg-secondary">Unknown</span>';
                    }
                    
                    tasksHtml += `
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0">${task.title}</h6>
                                <small>${statusBadge} ${priorityBadge}</small>
                            </div>
                            <div>
                                <button class="btn btn-sm btn-outline-primary" onclick="viewTask(${task.id})">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-secondary" onclick="editTask(${task.id})">
                                    <i class="bi bi-pencil"></i>
                                </button>
                            </div>
                        </li>
                    `;
                });
                
                tasksHtml += '</ul>';
            }
            
            // Show period details in modal
            Swal.fire({
                title: period.name,
                html: `
                    <div class="text-start">
                        <p>${period.description || 'No description provided'}</p>
                        <div class="d-flex justify-content-between mb-3">
                            <div>
                                <strong>Start Date:</strong> ${startDate}
                            </div>
                            <div>
                                <strong>End Date:</strong> ${endDate}
                            </div>
                        </div>
                        
                        <h5>Task Progress</h5>
                        <div class="progress mb-3" style="height: 10px;">
                            <div class="progress-bar bg-success" role="progressbar" 
                                 style="width: ${completionPercentage}%;" 
                                 aria-valuenow="${completionPercentage}" aria-valuemin="0" aria-valuemax="100">
                            </div>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <span>Completed: ${statusCounts.completed}/${totalTasks}</span>
                            <span>${completionPercentage}%</span>
                        </div>
                        
                        <h5>Tasks</h5>
                        ${tasksHtml}
                    </div>
                `,
                width: '600px',
                showCloseButton: true,
                showConfirmButton: false,
                focusConfirm: false
            });
        }
        
        // Edit period
        function editPeriod(periodId) {
            // Implement period editing functionality
            // (Similar to editTask function)
        }
    </script>
</body>
</html>