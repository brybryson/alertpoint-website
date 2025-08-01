<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
$pdo = null;
try {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/ALERTPOINT/config/database.php';
    
    // Create Database instance and get connection
    $database = new Database();
    $pdo = $database->getConnection();
    
    if (!$pdo) {
        throw new Exception("Failed to establish database connection");
    }
    
} catch (Exception $e) {
    error_log("Database connection failed: " . $e->getMessage());
    $pdo = null;
}

// Handle form submission
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_admin') {
    if ($pdo) {
        try {
            // Generate admin ID
            $stmt = $pdo->query("SELECT admin_id FROM admins ORDER BY admin_id DESC LIMIT 1");
            $lastAdmin = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($lastAdmin) {
                $lastNumber = intval(substr($lastAdmin['admin_id'], 3));
                $newNumber = $lastNumber + 1;
            } else {
                $newNumber = 1;
            }
            $adminId = 'ADM' . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
            
            // Format birthdate
            $birthdate = date('F j, Y', strtotime($_POST['birthdate']));
            
            // Hash password
            $hashedPassword = password_hash($_POST['password'], PASSWORD_DEFAULT);
            
            // Insert new admin
            $stmt = $pdo->prepare("INSERT INTO admins (admin_id, first_name, middle_name, last_name, barangay_position, birthdate, username, password, account_status, user_status, account_created, last_active, role) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', 'offline', NOW(), NOW(), 'Admin')");
            
            $result = $stmt->execute([
                $adminId,
                $_POST['admin_fn'],
                $_POST['admin_mn'],
                $_POST['admin_ln'],
                $_POST['role'],
                $birthdate,
                $_POST['username'],
                $hashedPassword
            ]);
            
            if ($result) {
                $message = 'Admin created successfully!';
                $messageType = 'success';
                // Redirect to prevent form resubmission
                header("Location: " . $_SERVER['PHP_SELF'] . "?success=1");
                exit();
            } else {
                $message = 'Error creating admin account.';
                $messageType = 'error';
            }
            
        } catch (PDOException $e) {
            $message = 'Database error: ' . $e->getMessage();
            $messageType = 'error';
            error_log("Admin creation error: " . $e->getMessage());
        }
    } else {
        $message = 'Database connection not available.';
        $messageType = 'error';
    }
}

// Check for success message
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $message = 'Admin created successfully!';
    $messageType = 'success';
}

// Get actual counts from database
$totalActiveAdmins = 0;
$totalArchivedAdmins = 0;

if ($pdo) {
    try {
        // Count active admins
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM admins WHERE account_status = 'active'");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $totalActiveAdmins = $result['count'];
        
        // Count archived admins
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM admins WHERE account_status = 'inactive'");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $totalArchivedAdmins = $result['count'];
        
    } catch (PDOException $e) {
        error_log("Count query error: " . $e->getMessage());
        $totalActiveAdmins = 0;
        $totalArchivedAdmins = 0;
    }
}

// Function to calculate time difference for "last seen"
function getLastSeenText($lastActive) {
    if (empty($lastActive)) return "Never";
    
    try {
        // Set timezone to match your database
        $now = new DateTime('now', new DateTimeZone('Asia/Manila'));
        $lastActiveTime = new DateTime($lastActive, new DateTimeZone('Asia/Manila'));
        $diff = $now->diff($lastActiveTime);
        
        // Calculate total minutes from the difference
        $totalMinutes = ($diff->days * 24 * 60) + ($diff->h * 60) + $diff->i;
        
        if ($totalMinutes < 1) {
            return "Just now";
        } elseif ($totalMinutes < 60) {
            return $totalMinutes . " minute" . ($totalMinutes != 1 ? "s" : "") . " ago";
        } elseif ($totalMinutes < 1440) { // Less than 24 hours (1440 minutes)
            $hours = floor($totalMinutes / 60);
            return $hours . " hour" . ($hours != 1 ? "s" : "") . " ago";
        } else {
            return $diff->days . " day" . ($diff->days != 1 ? "s" : "") . " ago";
        }
    } catch (Exception $e) {
        return "Unknown";
    }
}

// Fetch admin data from database
$activeAdmins = [];
$archivedAdmins = [];

if ($pdo) {
    try {
        // Fetch active admin data
        $stmt = $pdo->prepare("SELECT id, admin_id, first_name, middle_name, last_name, barangay_position, birthdate, username, picture, account_status, user_status, account_created, last_active, role FROM admins WHERE account_status = 'active' ORDER BY account_created DESC");
        $stmt->execute();
        $activeAdmins = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Fetch archived admin data
        $stmt = $pdo->prepare("SELECT id, admin_id, first_name, middle_name, last_name, barangay_position, birthdate, username, picture, account_status, user_status, account_created, last_active, role FROM admins WHERE account_status = 'inactive' ORDER BY account_created DESC");
        $stmt->execute();
        $archivedAdmins = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Database query error: " . $e->getMessage());
        $activeAdmins = [];
        $archivedAdmins = [];
    }
}

// Function to generate admin card HTML
function generateAdminCard($admin, $isArchived = false) {
    $fullName = trim($admin['first_name'] . ' ' . (!empty($admin['middle_name']) ? $admin['middle_name'] . ' ' : '') . $admin['last_name']);
    $initials = strtoupper(substr($admin['first_name'], 0, 1) . substr($admin['last_name'], 0, 1));
    
    // For archived users, always show as offline
    $userStatus = $isArchived ? 'offline' : $admin['user_status'];
    $statusClass = $userStatus === 'online' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
    $statusText = ucfirst($userStatus);
    $statusIndicator = $userStatus === 'online' ? 'bg-green-500' : 'bg-red-500';
    $statusRippleClass = $userStatus === 'online' ? 'online' : 'offline';
    $lastSeenText = getLastSeenText($admin['last_active']);
    
    // Check if picture exists
    $picturePath = '';
    $pictureExists = false;
    
    if (!empty($admin['picture'])) {
        if (strpos($admin['picture'], '/ALERTPOINT') === 0) {
            $picturePath = $admin['picture'];
        } else {
            $picturePath = '/ALERTPOINT' . $admin['picture'];
        }
        
        $fullPath = $_SERVER['DOCUMENT_ROOT'] . $picturePath;
        $pictureExists = file_exists($fullPath);
    }
    
    $cardClass = $isArchived ? 'archived-admin-card' : 'admin-card';
    $dataRole = $isArchived ? 'archived-admin' : 'admin';
    
    ob_start();
    ?>
    <div class="user-card <?php echo $cardClass; ?> bg-white rounded-xl p-6 border border-gray-200 shadow-sm" 
        data-role="<?php echo $dataRole; ?>" 
        data-status="<?php echo strtolower($userStatus); ?>" 
        data-name="<?php echo strtolower($fullName); ?>"
        data-user-role="Admin">
        <div class="flex items-start justify-between mb-4">
            <div class="flex items-center space-x-4">
                <?php if ($pictureExists): ?>
                    <div class="w-12 h-12 rounded-full relative">
                        <img src="<?php echo htmlspecialchars($picturePath); ?>" alt="<?php echo htmlspecialchars($fullName); ?>" class="w-full h-full object-cover rounded-full">
                        <div class="absolute -bottom-1 -right-1 w-4 h-4 <?php echo $statusIndicator; ?> rounded-full border-2 border-white z-50 status-indicator <?php echo $statusRippleClass; ?>"></div>
                    </div>
                <?php else: ?>
                    <div class="w-12 h-12 bg-purple-500 text-white rounded-full flex items-center justify-center font-semibold text-lg relative">
                        <?php echo $initials; ?>
                        <div class="absolute -bottom-1 -right-1 w-4 h-4 <?php echo $statusIndicator; ?> rounded-full border-2 border-white z-50 status-indicator <?php echo $statusRippleClass; ?>"></div>
                    </div>
                <?php endif; ?>
                <div>
                    <h3 class="font-semibold text-gray-900 text-lg"><?php echo htmlspecialchars($fullName); ?></h3>
                    <p class="text-sm text-gray-600"><?php echo htmlspecialchars($admin['barangay_position']); ?></p>
                    <p class="text-xs text-gray-500 flex items-center mt-1">
                        <i class="fas fa-user-shield text-gray-400 mr-2"></i><?php echo htmlspecialchars($admin['username']); ?>
                    </p>
                </div>
            </div>
            <div class="flex flex-col items-end">
                <span class="px-3 py-1 rounded-full text-xs font-medium <?php echo $statusClass; ?>">
                    <?php echo $statusText; ?>
                </span>
                <span class="text-xs text-gray-500 mt-1 capitalize px-2 py-1"><?php echo htmlspecialchars($admin['role']); ?></span>
            </div>
        </div>
        
        <div class="flex items-center justify-between pt-4 border-t border-gray-100">
            <div class="text-xs text-gray-500">
                Last seen: <?php echo $lastSeenText; ?>
            </div>
            <div class="flex space-x-2">
                <?php if ($isArchived): ?>
                    <button class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-xs transition-colors" onclick="restoreAdmin(<?php echo $admin['id']; ?>)">
                        <i class="fas fa-undo mr-1"></i>Restore
                    </button>
                    <button class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-xs transition-colors" onclick="deleteAdmin(<?php echo $admin['id']; ?>)">
                        <i class="fas fa-trash mr-1"></i>Delete
                    </button>
                <?php else: ?>
                    <button class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-xs transition-colors" onclick="editAdmin(<?php echo $admin['id']; ?>)">
                        <i class="fas fa-edit mr-1"></i>Edit
                    </button>
                    <button class="bg-orange-600 hover:bg-orange-700 text-white px-3 py-1 rounded text-xs transition-colors" onclick="archiveAdmin(<?php echo $admin['id']; ?>)">
                        <i class="fas fa-archive mr-1"></i>Archive
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AlertPoint User Management</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- CSS Files -->
    <link rel="stylesheet" href="/ALERTPOINT/css/Users.css">
    <link rel="stylesheet" href="/ALERTPOINT/css/footer.css">
    <link rel="stylesheet" href="/ALERTPOINT/css/nav-bar.css">

    <!-- Firebase Scripts -->
    <script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-firestore-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-storage-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-auth-compat.js"></script>
</head>
<body class="min-h-screen bg-gray-100">

    <!-- Header -->
    <header class="bg-white shadow-sm border-b">
        <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center">
                    <i class="fas fa-map-marker-alt text-3xl text-blue-600 mr-3"></i>
                    <div>
                        <h1 class="text-xl font-bold text-gray-900">AlertPoint</h1>
                        <p class="text-sm text-gray-600">Barangay 170, Caloocan City</p>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="text-right">
                        <p id="current-time" class="text-sm font-medium text-gray-900"></p>
                        <p class="text-xs text-gray-500">Philippine Standard Time</p>
                    </div>
                        <div class="relative">
                        <!-- Cog icon -->
                        <i onclick="toggleSettingsDropdown()" class="fas fa-cog text-gray-400 cursor-pointer hover:text-gray-600"></i>

                        <!-- Dropdown Menu -->
                        <div id="settingsDropdown" class="absolute right-0 mt-2 w-52 bg-white border border-gray-200 rounded-lg shadow-lg z-50 transform scale-95 opacity-0 transition-all duration-200 ease-in-out pointer-events-none">
                            <a href="#" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-user mr-2 text-gray-500"></i> Profile
                            </a>
                            <div class="border-t border-gray-200 my-1"></div>
                            <a href="/Prototype/html/Login.html" class="flex items-center px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                <i class="fas fa-sign-out-alt mr-2"></i> Logout
                            </a>
                        </div>
                        </div>


                </div>
            </div>
        </div>
    </header>

    

    <!-- Navigation -->
    <nav class="bg-white border-b">
        <div class="max-w-8xl mx-auto px-4">
            <div class="flex justify-center space-x-2 md:space-x-6">
            <!-- Dashboard -->
            <a href="/Prototype/html/Dashboard.php"
                class="nav-tab flex flex-col md:flex-row items-center md:items-start md:justify-start justify-center px-3 py-4 text-sm font-medium border-b-2 text-gray-500  duration-200">
                <i class="fas fa-chart-bar text-lg"></i>
                <span class="mt-1 md:mt-0 md:ml-2 hidden md:inline">Dashboard</span>
            </a>

            <!-- Alerts -->
            <a href="/Prototype/html/Alerts.html"
                class="nav-tab flex flex-col md:flex-row items-center md:items-start md:justify-start justify-center px-3 py-4 text-sm font-medium border-b-2 text-gray-500  duration-200">
                <i class="fas fa-bell text-lg"></i>
                <span class="mt-1 md:mt-0 md:ml-2 hidden md:inline">Alerts</span>
            </a>

            <!-- Reports -->
            <a href="/Prototype/html/Reports.html"
                class="nav-tab flex flex-col md:flex-row items-center md:items-start md:justify-start justify-center px-3 py-4 text-sm font-medium border-b-2 text-gray-500  duration-200">
                <i class="fas fa-chart-line text-lg"></i>
                <span class="mt-1 md:mt-0 md:ml-2 hidden md:inline">Reports</span>
            </a>

            <!-- Users  -->
            <a href="/Prototype/html/Users.html"
                class="nav-tab active flex flex-col md:flex-row items-center md:items-start md:justify-start justify-center px-3 py-4 text-sm font-medium border-b-2 text-black border-black">
                <i class="fas fa-users text-lg"></i>
                <span class="mt-1 md:mt-0 md:ml-2 hidden md:inline">Users</span>
            </a>

            <!-- Evacuation Plan -->
            <a href="/Prototype/html/EvacuationPlan.html"
                class="nav-tab flex flex-col md:flex-row items-center md:items-start md:justify-start justify-center px-3 py-4 text-sm font-medium border-b-2 text-gray-500  duration-200">
                <i class="fas fa-route text-lg"></i>
                <span class="mt-1 md:mt-0 md:ml-2 hidden md:inline">Evacuation Plan</span>
            </a>

            <!-- Settings -->
            <a href="/Prototype/html/Settings.html"
                class="nav-tab flex flex-col md:flex-row items-center md:items-start md:justify-start justify-center px-3 py-4 text-sm font-medium border-b-2 text-gray-500  duration-200">
                <i class="fas fa-cog text-lg"></i>
                <span class="mt-1 md:mt-0 md:ml-2 hidden md:inline">Settings</span>
            </a>
            </div>
        </div>
    </nav>



    <!-- Main Content -->
    <main class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Page Header (Responsive) -->
        <div class="mb-6">
            <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
                <!-- Title and Description -->
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 mb-1">User Management</h1>
                    <p class="text-gray-600 text-base">Manage resident accounts and admin users for AlertPoint system</p>
                </div>

                <!-- Action Buttons -->
                <div class="flex flex-col sm:flex-row sm:space-x-3 space-y-3 sm:space-y-0">
                    <!-- <button onclick="showAddUserModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-md flex items-center justify-center space-x-2 text-sm shadow-md transition-all">
                        <i class="fas fa-user-plus"></i>
                        <span>Add New User</span>
                    </button> -->

                     <!-- Add Admin Button -->
                    <button onclick="openAddAdminModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md transition-colors">
                        <i class="fas fa-user-gear mr-2"></i>Add Admin
                    </button>

                </div>
            </div>
        </div>


        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
            <div class="stat-card hover-card rounded-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Total Residents</p>
                        <p class="text-2xl font-bold text-gray-900" id="total-residents">0</p>
                    </div>
                    <div class="p-3">
                        <i class="fas fa-users text-blue-600 text-3xl"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card hover-card rounded-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Online Users</p>
                        <p class="text-2xl font-bold text-gray-900" id="online-users">0</p>
                    </div>
                    <div class="p-3">
                        <i class="fas fa-wifi text-green-600 text-3xl"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card hover-card rounded-lg p-6" data-total-admins="<?php echo $totalActiveAdmins; ?>">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Total Admins</p>
                        <p class="text-2xl font-bold text-gray-900" id="total-admins"><?php echo $totalActiveAdmins; ?></p>
                    </div>
                    <div class="p-3">
                        <i class="fas fa-user-shield text-purple-600 text-3xl"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card hover-card rounded-lg p-6" data-archived-count="<?php echo $totalArchivedAdmins; ?>">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Archived</p>
                        <p class="text-2xl font-bold text-gray-900" id="archived-count"><?php echo $totalArchivedAdmins; ?></p>
                    </div>
                    <div class="p-3">
                        <i class="fas fa-archive text-red-600 text-3xl"></i>
                    </div>
                </div>
            </div>
        </div>


        <!-- Display Messages -->
        <?php if (!empty($message)): ?>
            <div class="mb-6 p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                <div class="flex items-center">
                    <i class="fas <?php echo $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?> mr-2"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Filters and Search -->
        <div class="bg-white rounded-lg shadow-md p-4 mb-6">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <!-- Filter Tabs -->
                <div class="flex flex-wrap gap-2">
                    <button onclick="filterUsers('all')" class="filter-tab active px-4 py-2 rounded-md text-sm font-medium bg-blue-600 text-white" id="filter-all">
                        <i class="fas fa-users mr-1"></i>All
                    </button>
                    <button onclick="filterUsers('residents')" class="filter-tab px-4 py-2 rounded-md text-sm font-medium bg-gray-100 text-gray-700 hover:bg-gray-200" id="filter-residents">
                        <i class="fas fa-home mr-1"></i>Residents
                    </button>
                    <button onclick="filterUsers('admins')" class="filter-tab px-4 py-2 rounded-md text-sm font-medium bg-gray-100 text-gray-700 hover:bg-gray-200" id="filter-admins">
                        <i class="fas fa-user-shield mr-1"></i>Admins
                    </button>
                    <button onclick="filterUsers('online')" class="filter-tab px-4 py-2 rounded-md text-sm font-medium bg-gray-100 text-gray-700 hover:bg-gray-200" id="filter-online">
                        <i class="fas fa-circle text-green-500 mr-1"></i>Online
                    </button>
                    <button onclick="filterUsers('offline')" class="filter-tab px-4 py-2 rounded-md text-sm font-medium bg-gray-100 text-gray-700 hover:bg-gray-200" id="filter-offline">
                        <i class="fas fa-circle text-red-500 mr-1"></i>Offline
                    </button>
                </div>

                <!-- Search and Archive Button -->
                <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                    <div class="relative w-full sm:w-64">
                        <input type="text" placeholder="Search users..." class="search-input pl-10 pr-3 py-2 rounded-md w-full text-sm border border-gray-300" id="search-input" oninput="searchUsers()">
                        <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm"></i>
                    </div>
                    <button onclick="toggleArchivedSection()" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md text-sm transition-all duration-200" id="archived-toggle">
                        <i class="fas fa-archive mr-1"></i>Archived
                    </button>
                </div>
            </div>
        </div>

        <!-- Users Section -->
        <div class="bg-white rounded-xl shadow-lg mb-8" id="active-users-section">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-900">AlertPoint Users</h2>
                <?php if ($pdo): ?>
                    <p class="text-xs text-green-600 mt-1">✓ Database Connected</p>
                <?php else: ?>
                    <p class="text-xs text-red-600 mt-1">✗ Database Connection Failed</p>
                <?php endif; ?>
            </div>

            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="users-grid">
                    
                    <!-- Sample Resident User Card (Online) -->
                    <div class="user-card resident-card bg-white rounded-xl p-6 border border-gray-200 shadow-sm" 
                        data-role="resident" 
                        data-status="online" 
                        data-name="juan dela cruz"
                        data-user-role="Resident">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex items-center space-x-4">
                                <div class="w-12 h-12 bg-blue-500 text-white rounded-full flex items-center justify-center font-semibold text-lg relative">
                                    JD
                                    <div class="absolute -bottom-1 -right-1 w-4 h-4 bg-green-500 rounded-full border-2 border-white  status-indicator online"></div>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900 text-lg">Juan Dela Cruz</h3>
                                    <p class="text-sm text-gray-600">placeholder Rizal Street</p>
                                    <p class="text-xs text-gray-500 flex items-center mt-1">
                                        <i class="fas fa-phone text-gray-400 mr-2"></i>09123456789
                                    </p>
                                </div>
                            </div>
                            <div class="flex flex-col items-end">
                                <span class="px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    Online
                                </span>
                                <span class="text-xs text-gray-500 mt-1 capitalize px-2 py-1">Resident</span>
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                            <div class="text-xs text-gray-500">
                                Last seen: Just now
                            </div>
                            <div class="flex space-x-2">
                                <button class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-xs transition-colors">
                                    <i class="fas fa-edit mr-1"></i>Edit
                                </button>
                                <button class="bg-orange-600 hover:bg-orange-700 text-white px-3 py-1 rounded text-xs transition-colors">
                                    <i class="fas fa-archive mr-1"></i>Archive
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Sample Offline Resident Card -->
                    <div class="user-card resident-card bg-white rounded-xl p-6 border border-gray-200 shadow-sm" 
                        data-role="resident" 
                        data-status="offline" 
                        data-name="maria santos"
                        data-user-role="Resident">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex items-center space-x-4">
                                <div class="w-12 h-12 bg-pink-500 text-white rounded-full flex items-center justify-center font-semibold text-lg relative">
                                    MS
                                    <div class="absolute -bottom-1 -right-1 w-4 h-4 bg-red-500 rounded-full border-2 border-white  status-indicator offline"></div>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900 text-lg">Maria Santos</h3>
                                    <p class="text-sm text-gray-600">placeholder Luna Street</p>
                                    <p class="text-xs text-gray-500 flex items-center mt-1">
                                        <i class="fas fa-phone text-gray-400 mr-2"></i>09987654321
                                    </p>
                                </div>
                            </div>
                            <div class="flex flex-col items-end">
                                <span class="px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    Offline
                                </span>
                                <span class="text-xs text-gray-500 mt-1 capitalize px-2 py-1">Resident</span>
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                            <div class="text-xs text-gray-500">
                                Last seen: 2 hours ago
                            </div>
                            <div class="flex space-x-2">
                                <button class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-xs transition-colors">
                                    <i class="fas fa-edit mr-1"></i>Edit
                                </button>
                                <button class="bg-orange-600 hover:bg-orange-700 text-white px-3 py-1 rounded text-xs transition-colors">
                                    <i class="fas fa-archive mr-1"></i>Archive
                                </button>
                            </div>
                        </div>
                    </div>

                    <?php if (empty($activeAdmins)): ?>
                        <div class="col-span-full text-center py-8 text-gray-500" id="no-active-admins">
                            <i class="fas fa-users text-4xl mb-4"></i>
                            <p>No active admin users found in the database.</p>
                            <?php if (!$pdo): ?>
                                <p class="text-red-500 text-sm mt-2">Database connection issue detected.</p>
                            <?php else: ?>
                                <p class="text-sm mt-2">Create your first admin account using the "Add Admin" button above.</p>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <?php foreach ($activeAdmins as $admin): ?>
                            <?php echo generateAdminCard($admin, false); ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    
                </div>
                
                <!-- Pagination for Active Users -->
                <div class="flex items-center justify-between mt-6" id="active-pagination">
                    <div class="text-sm text-gray-500">
                        Showing <span id="active-showing-start">1</span>-<span id="active-showing-end">9</span> of <span id="active-total-count">0</span> users
                    </div>
                    <div class="flex space-x-2" id="active-pagination-buttons">
                        <!-- Pagination buttons will be inserted here by JavaScript -->
                    </div>
                </div>
                
                <!-- No Results Message (initially hidden) -->
                <div class="text-center py-8 text-gray-500 hidden" id="no-results">
                    <i class="fas fa-user-slash text-4xl mb-4 text-red-500"></i>
                    <p class="text-lg font-semibold">Invalid User</p>
                    <p class="text-sm">No users match your search criteria.</p>
                </div>
            </div>
        </div>

        <!-- Archived Users Section -->
        <div class="bg-white rounded-xl shadow-lg mb-8 hidden" id="archived-section">
            <div class="p-6 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-semibold text-gray-900 flex items-center">
                        <i class="fas fa-archive text-orange-600 mr-3"></i>
                        Archived Users
                    </h2>
                    <p class="text-gray-600">These accounts are temporarily disabled</p>
                </div>
            </div>
            
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="archived-grid">
                    
                    <?php if (empty($archivedAdmins)): ?>
                        <div class="col-span-full text-center py-8 text-gray-500" id="no-archived-admins">
                            <i class="fas fa-archive text-4xl mb-4 text-orange-500"></i>
                            <p>No archived admin users found.</p>
                            <p class="text-sm mt-2">Archived users will appear here when you archive active accounts.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($archivedAdmins as $admin): ?>
                            <?php echo generateAdminCard($admin, true); ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    
                </div>
                
                <!-- Pagination for Archived Users -->
                <div class="flex items-center justify-between mt-6" id="archived-pagination">
                    <div class="text-sm text-gray-500">
                        Showing <span id="archived-showing-start">1</span>-<span id="archived-showing-end">9</span> of <span id="archived-total-count">0</span> users
                    </div>
                    <div class="flex space-x-2" id="archived-pagination-buttons">
                        <!-- Pagination buttons will be inserted here by JavaScript -->
                    </div>
                </div>
            </div>
        </div>

    </main>

     <!-- Add Admin Modal -->
    <div id="addAdminModal" class="fixed inset-0 z-40 flex items-center justify-center bg-black bg-opacity-50 hidden">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl mx-4 animate-fade-in max-h-[95vh] overflow-y-auto">
            <!-- Header -->
            <div class="flex justify-between items-center p-6 border-b border-gray-200 bg-blue-50 rounded-t-xl">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-user-plus text-blue-600"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900">Add Barangay Admin</h3>
                </div>
                <button onclick="confirmClose()" class="text-gray-400 hover:text-gray-600 hover:bg-gray-100 p-2 rounded-full transition-colors">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>

            <!-- Form -->
            <div class="p-6">
                <form id="addAdminForm" class="space-y-6">
                    
                    <!-- Two Column Layout -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        
                        <!-- Left Column -->
                        <div class="space-y-6">
                            
                            <!-- Personal Information Section -->
                            <div class="space-y-4">
                                <div class="flex items-center space-x-3 pb-2 border-b border-gray-200">
                                    <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                        <i class="fas fa-user text-blue-600 text-sm"></i>
                                    </div>
                                    <h4 class="text-lg font-medium text-gray-900">Personal Information</h4>
                                </div>
                                
                                <!-- Profile Photo - Inside Personal Information -->
                                <div class="flex items-center space-x-4 p-4 bg-gray-50 rounded-lg">
                                    <div class="profile-image-container">
                                        <img id="profilePreview" src="" alt="Profile Preview" class="profile-image hidden">
                                        <div id="uploadPlaceholder" class="upload-placeholder">
                                            <i class="fas fa-camera text-gray-400 text-lg"></i>
                                        </div>
                                        <div class="image-overlay">
                                            <i class="fas fa-camera text-white text-sm"></i>
                                        </div>
                                        <input type="file" id="profileImageInput" accept=".png,.jpg,.jpeg" class="hidden">
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-gray-700 mb-2">Profile Photo</p>
                                        <div class="flex space-x-2 mb-2">
                                            <button type="button" onclick="uploadPhoto()" class="px-3 py-1 text-xs bg-blue-500 hover:bg-blue-600 text-white rounded transition-colors">
                                                <i class="fas fa-upload mr-1"></i>Upload
                                            </button>
                                            <button type="button" onclick="removePhoto()" id="removePhotoBtn" class="px-3 py-1 text-xs bg-red-500 hover:bg-red-600 text-white rounded transition-colors hidden">
                                                <i class="fas fa-trash mr-1"></i>Remove
                                            </button>
                                        </div>
                                        <p class="text-xs text-gray-500">PNG, JPG, JPEG only. Max 5MB</p>
                                        <div class="error-message text-red-500 text-xs mt-1 hidden" id="photoError"></div>
                                    </div>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            <i class="fas fa-user-circle text-gray-400 mr-2"></i>First Name *
                                        </label>
                                        <input type="text" id="admin_fn" name="admin_fn" 
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" 
                                               placeholder="Enter first name" required>
                                        <div class="error-message text-red-500 text-xs mt-1 hidden"></div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            <i class="fas fa-user-circle text-gray-400 mr-2"></i>Last Name *
                                        </label>
                                        <input type="text" id="admin_ln" name="admin_ln" 
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" 
                                               placeholder="Enter last name" required>
                                        <div class="error-message text-red-500 text-xs mt-1 hidden"></div>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-user text-gray-400 mr-2"></i>Middle Name
                                    </label>
                                    <input type="text" id="admin_mn" name="admin_mn" 
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" 
                                           placeholder="Enter middle name (optional)">
                                </div>

                                <!-- Birthdate Section -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-calendar text-gray-400 mr-2"></i>Birthdate *
                                    </label>
                                    <div class="grid grid-cols-3 gap-3">
                                        <div>
                                            <select id="birth_month" name="birth_month" 
                                                    class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors bg-white" required>
                                                <option value="">Month</option>
                                                <option value="01">January</option>
                                                <option value="02">February</option>
                                                <option value="03">March</option>
                                                <option value="04">April</option>
                                                <option value="05">May</option>
                                                <option value="06">June</option>
                                                <option value="07">July</option>
                                                <option value="08">August</option>
                                                <option value="09">September</option>
                                                <option value="10">October</option>
                                                <option value="11">November</option>
                                                <option value="12">December</option>
                                            </select>
                                        </div>
                                        <div>
                                            <select id="birth_day" name="birth_day" 
                                                    class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors bg-white" required>
                                                <option value="">Day</option>
                                            </select>
                                        </div>
                                        <div>
                                            <select id="birth_year" name="birth_year" 
                                                    class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors bg-white" required>
                                                <option value="">Year</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="error-message text-red-500 text-xs mt-1 hidden" id="birthdateError"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column -->
                        <div class="space-y-6">
                            
                            <!-- Barangay Position Section -->
                            <div class="space-y-4">
                                <div class="flex items-center space-x-3 pb-2 border-b border-gray-200">
                                    <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                        <i class="fas fa-briefcase text-green-600 text-sm"></i>
                                    </div>
                                    <h4 class="text-lg font-medium text-gray-900">Barangay Position</h4>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-id-badge text-gray-400 mr-2"></i>Position/Role *
                                    </label>
                                    <select id="role" name="role" 
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors bg-white" required>
                                        <option value="">Select Position</option>
                                        <option value="Barangay Captain (Punong Barangay)">Barangay Captain (Punong Barangay)</option>
                                        <option value="Barangay Councilor (Kagawad)">Barangay Councilor (Kagawad)</option>
                                        <option value="SK Chairperson">SK Chairperson</option>
                                        <option value="Barangay Secretary">Barangay Secretary</option>
                                        <option value="Barangay Treasurer">Barangay Treasurer</option>
                                        <option value="Barangay Tanod (Watchmen)">Barangay Tanod (Watchmen)</option>
                                        <option value="Lupong Tagapamayapa Member">Lupong Tagapamayapa Member</option>
                                        <option value="Barangay Health Worker (BHW)">Barangay Health Worker (BHW)</option>
                                        <option value="Day Care Worker">Day Care Worker</option>
                                        <option value="Barangay Nutrition Scholar (BNS)">Barangay Nutrition Scholar (BNS)</option>
                                        <option value="BDRRMC Member">BDRRMC Member</option>
                                        <option value="other">Other (Specify)</option>
                                    </select>
                                    <div class="error-message text-red-500 text-xs mt-1 hidden"></div>
                                </div>
                                
                                <div id="customRoleDiv" class="hidden">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-edit text-gray-400 mr-2"></i>Specify Position *
                                    </label>
                                    <input type="text" id="customRole" name="customRole" 
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" 
                                           placeholder="Enter custom position">
                                    <div class="error-message text-red-500 text-xs mt-1 hidden"></div>
                                </div>
                            </div>

                            <!-- Account Information Section -->
                            <div class="space-y-4">
                                <div class="flex items-center space-x-3 pb-2 border-b border-gray-200">
                                    <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center">
                                        <i class="fas fa-key text-purple-600 text-sm"></i>
                                    </div>
                                    <h4 class="text-lg font-medium text-gray-900">Account Information</h4>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-at text-gray-400 mr-2"></i>Username *
                                    </label>
                                    <input type="text" id="username" name="username" readonly
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-50 text-gray-600 cursor-not-allowed">
                                    <div class="flex items-center text-xs text-gray-500 mt-2">
                                        <i class="fas fa-info-circle mr-1"></i>
                                        Auto-generated: admin_[firstname]
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-lock text-gray-400 mr-2"></i>Password *
                                    </label>
                                    <div class="relative">
                                        <input type="password" id="password" name="password" 
                                               class="w-full px-4 py-3 pr-12 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" 
                                               placeholder="Enter password" required>
                                        <button type="button" onclick="togglePassword()" class="absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors">
                                            <i id="passwordToggle" class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <div class="error-message text-red-500 text-xs mt-1 hidden"></div>
                                    <div class="flex items-center text-xs text-gray-500 mt-2">
                                        <i class="fas fa-shield-alt mr-1"></i>
                                        Minimum 8 characters with letters and numbers
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex justify-end space-x-4 pt-6 border-t border-gray-200">
                        <button type="button" onclick="confirmClose()" 
                                class="px-6 py-3 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors flex items-center space-x-2">
                            <i class="fas fa-times"></i>
                            <span>Cancel</span>
                        </button>
                        <button type="submit" 
                                class="px-6 py-3 text-sm font-medium bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors flex items-center space-x-2 shadow-lg">
                            <i class="fas fa-plus"></i>
                            <span>Create Admin</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div id="confirmationModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 hidden">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-4 animate-fade-in">
            <div class="p-6 text-center">
                <div id="confirmIcon" class="w-12 h-12 mx-auto mb-4 rounded-full flex items-center justify-center">
                    <i id="confirmIconClass" class="text-xl"></i>
                </div>
                <h3 id="confirmTitle" class="text-lg font-medium mb-4"></h3>
                <div id="confirmMessage" class="text-gray-600 text-sm mb-6"></div>
                <div class="flex justify-center space-x-3">
                    <button id="confirmCancel" class="px-4 py-2 text-sm bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md transition-colors">
                        Cancel
                    </button>
                    <button id="confirmAction" class="px-4 py-2 text-sm rounded-md transition-colors">
                        Confirm
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Admin Details Confirmation Modal -->
    <div id="adminDetailsModal" class="fixed inset-0 z-[9999] flex items-center justify-center bg-black bg-opacity-50 hidden">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg mx-4 animate-fade-in relative z-[10000]">
            <!-- Header -->
            <div class="flex justify-between items-center p-6 border-b border-gray-200 bg-blue-50 rounded-t-xl">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-user-check text-blue-600"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900">Confirm Admin Details</h3>
                </div>
            </div>

            <!-- Content -->
            <div class="p-6">
                <div class="text-center mb-6">
                    <p class="text-gray-600 text-sm mb-4">Please review the admin details before creating the account:</p>
                </div>
                
                <!-- Details Display -->
                <div id="adminDetailsContent" class="space-y-3 mb-6 bg-gray-50 p-4 rounded-lg">
                    <!-- Details will be populated by JavaScript -->
                </div>

                <div class="text-center text-gray-600 text-sm mb-6">
                    Are all the details correct?
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-center space-x-4">
                    <button id="cancelDetailsBtn" type="button" 
                            class="px-6 py-3 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors flex items-center space-x-2">
                        <i class="fas fa-times"></i>
                        <span>Cancel</span>
                    </button>
                    <button id="confirmDetailsBtn" type="button" 
                            class="px-6 py-3 text-sm font-medium bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors flex items-center space-x-2 shadow-lg">
                        <i class="fas fa-check"></i>
                        <span>Confirm</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div id="successModal" class="fixed inset-0 z-[9999] flex items-center justify-center bg-black bg-opacity-50 hidden">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 animate-fade-in relative z-[10000]">
            <!-- Content -->
            <div class="p-8 text-center">
                <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-check-circle text-green-500 text-2xl"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">Success!</h3>
                <p id="successMessage" class="text-gray-600 text-sm mb-6">
                    Admin account has been created successfully.
                </p>
                <button id="successOkBtn" type="button" 
                        class="px-6 py-3 text-sm font-medium bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors shadow-lg">
                    <i class="fas fa-check mr-2"></i>OK
                </button>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay hidden">
        <div class="bg-white p-6 rounded-lg shadow-lg text-center">
            <div class="loading-spinner mx-auto mb-4"></div>
            <p class="text-gray-600">Creating admin account...</p>
        </div>
    </div>


    <!-- EDIT AND ARCHIVE ADMIN MODALS -->
    <!-- Edit Admin Modal -->
    <div id="editAdminModal" class="fixed inset-0 z-40 flex items-center justify-center bg-black bg-opacity-50 hidden">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl mx-4 animate-fade-in max-h-[95vh] overflow-y-auto">
            <!-- Header -->
            <div class="flex justify-between items-center p-6 border-b border-gray-200 bg-blue-50 rounded-t-xl">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-user-edit text-blue-600"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900">Edit Admin Details</h3>
                </div>
                <button onclick="closeEditAdminModal()" class="text-gray-400 hover:text-gray-600 hover:bg-gray-100 p-2 rounded-full transition-colors">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>

            <!-- Form -->
            <div class="p-6">
                <form id="editAdminForm" class="space-y-6">
                    <input type="hidden" id="edit_admin_id" name="edit_admin_id">
                    
                    <!-- Two Column Layout -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        
                        <!-- Left Column -->
                        <div class="space-y-6">
                            
                            <!-- Personal Information Section -->
                            <div class="space-y-4">
                                <div class="flex items-center space-x-3 pb-2 border-b border-gray-200">
                                    <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                        <i class="fas fa-user text-blue-600 text-sm"></i>
                                    </div>
                                    <h4 class="text-lg font-medium text-gray-900">Personal Information</h4>
                                </div>
                                
                                <!-- Profile Photo -->
                                <div class="flex items-center space-x-4 p-4 bg-gray-50 rounded-lg">
                                    <div class="profile-image-container">
                                        <img id="editProfilePreview" src="" alt="Profile Preview" class="profile-image hidden">
                                        <div id="editUploadPlaceholder" class="upload-placeholder">
                                            <i class="fas fa-camera text-gray-400 text-lg"></i>
                                        </div>
                                        <div class="image-overlay">
                                            <i class="fas fa-camera text-white text-sm"></i>
                                        </div>
                                        <input type="file" id="editProfileImageInput" accept=".png,.jpg,.jpeg" class="hidden">
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-gray-700 mb-2">Profile Photo</p>
                                        <div class="flex space-x-2 mb-2">
                                            <button type="button" onclick="editUploadPhoto()" class="px-3 py-1 text-xs bg-blue-500 hover:bg-blue-600 text-white rounded transition-colors">
                                                <i class="fas fa-upload mr-1"></i>Upload
                                            </button>
                                            <button type="button" onclick="editRemovePhoto()" id="editRemovePhotoBtn" class="px-3 py-1 text-xs bg-red-500 hover:bg-red-600 text-white rounded transition-colors hidden">
                                                <i class="fas fa-trash mr-1"></i>Remove
                                            </button>
                                        </div>
                                        <p class="text-xs text-gray-500">PNG, JPG, JPEG only. Max 5MB</p>
                                        <div class="error-message text-red-500 text-xs mt-1 hidden" id="editPhotoError"></div>
                                    </div>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            <i class="fas fa-user-circle text-gray-400 mr-2"></i>First Name *
                                        </label>
                                        <input type="text" id="edit_admin_fn" name="edit_admin_fn" 
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" 
                                            placeholder="Enter first name" required>
                                        <div class="error-message text-red-500 text-xs mt-1 hidden"></div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            <i class="fas fa-user-circle text-gray-400 mr-2"></i>Last Name *
                                        </label>
                                        <input type="text" id="edit_admin_ln" name="edit_admin_ln" 
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" 
                                            placeholder="Enter last name" required>
                                        <div class="error-message text-red-500 text-xs mt-1 hidden"></div>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-user text-gray-400 mr-2"></i>Middle Name
                                    </label>
                                    <input type="text" id="edit_admin_mn" name="edit_admin_mn" 
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" 
                                        placeholder="Enter middle name (optional)">
                                </div>

                                <!-- Birthdate Section -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-calendar text-gray-400 mr-2"></i>Birthdate *
                                    </label>
                                    <div class="grid grid-cols-3 gap-3">
                                        <div>
                                            <select id="edit_birth_month" name="edit_birth_month" 
                                                    class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors bg-white" required>
                                                <option value="">Month</option>
                                                <option value="01">January</option>
                                                <option value="02">February</option>
                                                <option value="03">March</option>
                                                <option value="04">April</option>
                                                <option value="05">May</option>
                                                <option value="06">June</option>
                                                <option value="07">July</option>
                                                <option value="08">August</option>
                                                <option value="09">September</option>
                                                <option value="10">October</option>
                                                <option value="11">November</option>
                                                <option value="12">December</option>
                                            </select>
                                        </div>
                                        <div>
                                            <select id="edit_birth_day" name="edit_birth_day" 
                                                    class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors bg-white" required>
                                                <option value="">Day</option>
                                            </select>
                                        </div>
                                        <div>
                                            <select id="edit_birth_year" name="edit_birth_year" 
                                                    class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors bg-white" required>
                                                <option value="">Year</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="error-message text-red-500 text-xs mt-1 hidden" id="editBirthdateError"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column -->
                        <div class="space-y-6">
                            
                            <!-- Barangay Position Section -->
                            <div class="space-y-4">
                                <div class="flex items-center space-x-3 pb-2 border-b border-gray-200">
                                    <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                        <i class="fas fa-briefcase text-green-600 text-sm"></i>
                                    </div>
                                    <h4 class="text-lg font-medium text-gray-900">Barangay Position</h4>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-id-badge text-gray-400 mr-2"></i>Position/Role *
                                    </label>
                                    <select id="edit_role" name="edit_role" 
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors bg-white" required>
                                        <option value="">Select Position</option>
                                        <option value="Barangay Captain (Punong Barangay)">Barangay Captain (Punong Barangay)</option>
                                        <option value="Barangay Councilor (Kagawad)">Barangay Councilor (Kagawad)</option>
                                        <option value="SK Chairperson">SK Chairperson</option>
                                        <option value="Barangay Secretary">Barangay Secretary</option>
                                        <option value="Barangay Treasurer">Barangay Treasurer</option>
                                        <option value="Barangay Tanod (Watchmen)">Barangay Tanod (Watchmen)</option>
                                        <option value="Lupong Tagapamayapa Member">Lupong Tagapamayapa Member</option>
                                        <option value="Barangay Health Worker (BHW)">Barangay Health Worker (BHW)</option>
                                        <option value="Day Care Worker">Day Care Worker</option>
                                        <option value="Barangay Nutrition Scholar (BNS)">Barangay Nutrition Scholar (BNS)</option>
                                        <option value="BDRRMC Member">BDRRMC Member</option>
                                        <option value="other">Other (Specify)</option>
                                    </select>
                                    <div class="error-message text-red-500 text-xs mt-1 hidden"></div>
                                </div>
                                
                                <div id="editCustomRoleDiv" class="hidden">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-edit text-gray-400 mr-2"></i>Specify Position *
                                    </label>
                                    <input type="text" id="editCustomRole" name="editCustomRole" 
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" 
                                        placeholder="Enter custom position">
                                    <div class="error-message text-red-500 text-xs mt-1 hidden"></div>
                                </div>
                            </div>

                            <!-- Account Information Section (Read-only) -->
                            <div class="space-y-4">
                                <div class="flex items-center space-x-3 pb-2 border-b border-gray-200">
                                    <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center">
                                        <i class="fas fa-key text-purple-600 text-sm"></i>
                                    </div>
                                    <h4 class="text-lg font-medium text-gray-900">Account Information</h4>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-id-card text-gray-400 mr-2"></i>Admin ID
                                    </label>
                                    <input type="text" id="edit_admin_id_display" readonly
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-50 text-gray-600 cursor-not-allowed">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-at text-gray-400 mr-2"></i>Username
                                    </label>
                                    <input type="text" id="edit_username" readonly
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-50 text-gray-600 cursor-not-allowed">
                                    <div class="flex items-center text-xs text-gray-500 mt-2">
                                        <i class="fas fa-info-circle mr-1"></i>
                                        Username cannot be changed
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex justify-end space-x-4 pt-6 border-t border-gray-200">
                        <button type="button" onclick="closeEditAdminModal()" 
                                class="px-6 py-3 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors flex items-center space-x-2">
                            <i class="fas fa-times"></i>
                            <span>Cancel</span>
                        </button>
                        <button type="submit" 
                                class="px-6 py-3 text-sm font-medium bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors flex items-center space-x-2 shadow-lg">
                            <i class="fas fa-save"></i>
                            <span>Update Admin</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Confirmation Modal -->
    <div id="editConfirmationModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 hidden">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg mx-4 animate-fade-in relative z-[10000]">
            <!-- Header -->
            <div class="flex justify-between items-center p-6 border-b border-gray-200 bg-blue-50 rounded-t-xl">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-question-circle text-blue-600"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900">Confirm Changes</h3>
                </div>
            </div>

            <!-- Content -->
            <div class="p-6">
                <div class="text-center mb-6">
                    <p class="text-gray-600 text-sm mb-4">Are you sure you want to update this admin's information?</p>
                </div>
                
                <!-- Details Display -->
                <div id="editConfirmationContent" class="space-y-3 mb-6 bg-gray-50 p-4 rounded-lg">
                    <!-- Details will be populated by JavaScript -->
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-center space-x-4">
                    <button id="cancelEditBtn" type="button" 
                            class="px-6 py-3 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors flex items-center space-x-2">
                        <i class="fas fa-times"></i>
                        <span>Cancel</span>
                    </button>
                    <button id="confirmEditBtn" type="button" 
                            class="px-6 py-3 text-sm font-medium bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors flex items-center space-x-2 shadow-lg">
                        <i class="fas fa-check"></i>
                        <span>Confirm Update</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Archive Confirmation Modal -->
    <div id="archiveConfirmationModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 hidden">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 animate-fade-in relative z-[10000]">
            <!-- Header -->
            <div class="flex justify-between items-center p-6 border-b border-gray-200 bg-orange-50 rounded-t-xl">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-orange-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-archive text-orange-600"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900">Archive Admin</h3>
                </div>
            </div>

            <!-- Content -->
            <div class="p-6">
                <div class="text-center mb-6">
                    <div class="w-16 h-16 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-exclamation-triangle text-orange-500 text-2xl"></i>
                    </div>
                    <h4 class="text-lg font-semibold text-gray-900 mb-2">Are you sure?</h4>
                    <p class="text-gray-600 text-sm mb-4">
                        This will archive the admin account. The admin will not be able to access the system until the account is restored.
                    </p>
                    <div id="archiveAdminDetails" class="bg-gray-50 p-3 rounded-lg mb-4">
                        <!-- Admin details will be populated by JavaScript -->
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-center space-x-4">
                    <button id="cancelArchiveBtn" type="button" 
                            class="px-6 py-3 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors flex items-center space-x-2">
                        <i class="fas fa-times"></i>
                        <span>Cancel</span>
                    </button>
                    <button id="confirmArchiveBtn" type="button" 
                            class="px-6 py-3 text-sm font-medium bg-orange-600 hover:bg-orange-700 text-white rounded-lg transition-colors flex items-center space-x-2 shadow-lg">
                        <i class="fas fa-archive"></i>
                        <span>Archive Admin</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Success Modal -->
    <div id="editSuccessModal" class="fixed inset-0 z-[9999] flex items-center justify-center bg-black bg-opacity-50 hidden">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 animate-fade-in relative z-[10000]">
            <!-- Content -->
            <div class="p-8 text-center">
                <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-check-circle text-green-500 text-2xl"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">Success!</h3>
                <p id="editSuccessMessage" class="text-gray-600 text-sm mb-6">
                    Admin information has been updated successfully.
                </p>
                <button id="editSuccessOkBtn" type="button" 
                        class="px-6 py-3 text-sm font-medium bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors shadow-lg">
                    <i class="fas fa-check mr-2"></i>OK
                </button>
            </div>
        </div>
    </div>

    <!-- Archive Success Modal -->
    <div id="archiveSuccessModal" class="fixed inset-0 z-[9999] flex items-center justify-center bg-black bg-opacity-50 hidden">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 animate-fade-in relative z-[10000]">
            <!-- Content -->
            <div class="p-8 text-center">
                <div class="w-16 h-16 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-archive text-orange-500 text-2xl"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">Admin Archived!</h3>
                <p id="archiveSuccessMessage" class="text-gray-600 text-sm mb-6">
                    The admin account has been archived successfully.
                </p>
                <button id="archiveSuccessOkBtn" type="button" 
                        class="px-6 py-3 text-sm font-medium bg-orange-600 hover:bg-orange-700 text-white rounded-lg transition-colors shadow-lg">
                    <i class="fas fa-check mr-2"></i>OK
                </button>
            </div>
        </div>
    </div>


    <!-- UNARCHIVE AND DELETE -->
     <!-- Add these modals after the existing Archive Success Modal in Users.php -->

    <!-- Restore Confirmation Modal -->
    <div id="restoreConfirmationModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 hidden">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 animate-fade-in relative z-[10000]">
            <!-- Header -->
            <div class="flex justify-between items-center p-6 border-b border-gray-200 bg-green-50 rounded-t-xl">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-undo text-green-600"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900">Restore Admin</h3>
                </div>
            </div>

            <!-- Content -->
            <div class="p-6">
                <div class="text-center mb-6">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-user-check text-green-500 text-2xl"></i>
                    </div>
                    <h4 class="text-lg font-semibold text-gray-900 mb-2">Restore Admin Account</h4>
                    <p class="text-gray-600 text-sm mb-4">
                        This will restore the admin account and allow them to access the system again.
                    </p>
                    <div id="restoreAdminDetails" class="bg-gray-50 p-3 rounded-lg mb-4">
                        <!-- Admin details will be populated by JavaScript -->
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-center space-x-4">
                    <button id="cancelRestoreBtn" type="button" 
                            class="px-6 py-3 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors flex items-center space-x-2">
                        <i class="fas fa-times"></i>
                        <span>Cancel</span>
                    </button>
                    <button id="confirmRestoreBtn" type="button" 
                            class="px-6 py-3 text-sm font-medium bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors flex items-center space-x-2 shadow-lg">
                        <i class="fas fa-undo"></i>
                        <span>Restore Admin</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteConfirmationModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 hidden">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 animate-fade-in relative z-[10000]">
            <!-- Header -->
            <div class="flex justify-between items-center p-6 border-b border-gray-200 bg-red-50 rounded-t-xl">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-trash text-red-600"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900">Delete Admin</h3>
                </div>
            </div>

            <!-- Content -->
            <div class="p-6">
                <div class="text-center mb-6">
                    <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-exclamation-triangle text-red-500 text-2xl"></i>
                    </div>
                    <h4 class="text-lg font-semibold text-gray-900 mb-2">Permanent Deletion</h4>
                    <p class="text-gray-600 text-sm mb-4">
                        <strong class="text-red-600">Warning:</strong> This action cannot be undone. The admin account and all associated data will be permanently deleted from the system.
                    </p>
                    <div id="deleteAdminDetails" class="bg-red-50 border border-red-200 p-3 rounded-lg mb-4">
                        <!-- Admin details will be populated by JavaScript -->
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-center space-x-4">
                    <button id="cancelDeleteBtn" type="button" 
                            class="px-6 py-3 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors flex items-center space-x-2">
                        <i class="fas fa-times"></i>
                        <span>Cancel</span>
                    </button>
                    <button id="confirmDeleteBtn" type="button" 
                            class="px-6 py-3 text-sm font-medium bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors flex items-center space-x-2 shadow-lg">
                        <i class="fas fa-trash"></i>
                        <span>Delete Permanently</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Restore Success Modal -->
    <div id="restoreSuccessModal" class="fixed inset-0 z-[9999] flex items-center justify-center bg-black bg-opacity-50 hidden">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 animate-fade-in relative z-[10000]">
            <!-- Content -->
            <div class="p-8 text-center">
                <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-user-check text-green-500 text-2xl"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">Admin Restored!</h3>
                <p id="restoreSuccessMessage" class="text-gray-600 text-sm mb-6">
                    The admin account has been restored successfully and can now access the system.
                </p>
                <button id="restoreSuccessOkBtn" type="button" 
                        class="px-6 py-3 text-sm font-medium bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors shadow-lg">
                    <i class="fas fa-check mr-2"></i>OK
                </button>
            </div>
        </div>
    </div>

    <!-- Delete Success Modal -->
    <div id="deleteSuccessModal" class="fixed inset-0 z-[9999] flex items-center justify-center bg-black bg-opacity-50 hidden">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 animate-fade-in relative z-[10000]">
            <!-- Content -->
            <div class="p-8 text-center">
                <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-check-circle text-red-500 text-2xl"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">Admin Deleted!</h3>
                <p id="deleteSuccessMessage" class="text-gray-600 text-sm mb-6">
                    The admin account has been permanently deleted from the system.
                </p>
                <button id="deleteSuccessOkBtn" type="button" 
                        class="px-6 py-3 text-sm font-medium bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors shadow-lg">
                    <i class="fas fa-check mr-2"></i>OK
                </button>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white mt-12">
        <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Main Footer Content -->
            <div class="py-8">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                    <!-- Company Info -->
                    <div>
                        <div class="flex items-center mb-4">
                            <i class="fas fa-map-marker-alt text-2xl text-blue-400 mr-2"></i>
                            <h3 class="text-lg font-bold">AlertPoint</h3>
                        </div>
                        <p class="text-gray-300 text-sm mb-4">
                           Disaster Risk Management Monitoring system for Barangay 170, Caloocan City
                        </p>
                        <div class="flex space-x-3">
                            <i class="fab fa-facebook text-blue-400 hover:text-blue-300 cursor-pointer"></i>
                            <i class="fab fa-twitter text-blue-400 hover:text-blue-300 cursor-pointer"></i>
                            <i class="fas fa-envelope text-blue-400 hover:text-blue-300 cursor-pointer"></i>
                        </div>
                    </div>

                    <!-- Quick Links -->
                    <div>
                        <h4 class="text-md font-semibold mb-4">Quick Links</h4>
                        <ul class="space-y-2 text-sm">
                            <li><a href="/Prototype/html/Dashboard.html" class="text-gray-300 hover:text-white transition-colors">Dashboard</a></li>
                            <li><a href="/Prototype/html/Alerts.html" class="text-gray-300 hover:text-white transition-colors">Active Alerts</a></li>
                            <li><a href="/Prototype/html/Reports.html" class="text-gray-300 hover:text-white transition-colors">Reports</a></li>
                            <li><a href="/Prototype/html/Users.html" class="text-gray-300 hover:text-white transition-colors">User Management</a></li>
                        </ul>
                    </div>

                    <!-- Services -->
                    <div>
                        <h4 class="text-md font-semibold mb-4">Services</h4>
                        <ul class="space-y-2 text-sm text-gray-300">
                            <li>Flood Monitoring</li>
                            <li>Temperature Tracking</li>
                            <li>Humidity Analysis</li>
                            <li>Emergency Alerts</li>
                            <li>AI Insights</li>
                        </ul>
                    </div>

                    <!-- Contact Info -->
                    <div>
                        <h4 class="text-md font-semibold mb-4">Contact</h4>
                        <div class="space-y-2 text-sm text-gray-300">
                            <div class="flex items-center">
                                <i class="fas fa-map-marker-alt mr-2 text-blue-400"></i>
                                <span>Barangay 170, Caloocan City</span>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-phone mr-2 text-blue-400"></i>
                                <span>+63 (2) 8123-4567</span>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-envelope mr-2 text-blue-400"></i>
                                <span>admin@alertpoint.gov.ph</span>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-clock mr-2 text-blue-400"></i>
                                <span>24/7 Monitoring</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Footer -->
            <div class="border-t border-gray-700 py-4">
                <div class="flex flex-col md:flex-row justify-between items-center">
                    <div class="text-sm text-gray-400 mb-2 md:mb-0">
                        © <span id="current-year">2025</span> AlertPoint Environmental Monitoring System. All rights reserved.
                    </div>
                    <div class="flex space-x-6 text-sm">
                        <a href="#" class="text-gray-400 hover:text-white transition-colors">Privacy Policy</a>
                        <a href="#" class="text-gray-400 hover:text-white transition-colors">Terms of Service</a>
                        <a href="#" class="text-gray-400 hover:text-white transition-colors">Support</a>
                    </div>
                </div>
                
                <!-- System Status -->
                <div class="mt-3 text-center">
                    <div class="flex justify-center items-center space-x-4 text-xs text-gray-400">
                        <div class="flex items-center">
                            <div class="w-2 h-2 bg-green-500 rounded-full mr-1 animate-pulse"></div>
                            <span>System Online</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-server mr-1 text-green-400"></i>
                            <span>Server Status: Active</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-wifi mr-1 text-green-400"></i>
                            <span>Connection: Stable</span>
                        </div>
                        <div class="flex items-center">
                            <span>Last Update: </span>
                            <span id="last-update-time" class="ml-1"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </footer>



    <script src="/ALERTPOINT/javascript/footer.js"></script>
    <script src="/ALERTPOINT/javascript/nav-bar.js"></script>
    <script src="/ALERTPOINT/javascript/USERS/Users.js"></script>
    <script src="/ALERTPOINT/javascript/settings.js"></script>

    <script src="/ALERTPOINT/javascript/USERS/Add_Admin.js"></script>
    <script src="/ALERTPOINT/javascript/USERS/archive_edit_admin.js"></script>





</body>
</html>