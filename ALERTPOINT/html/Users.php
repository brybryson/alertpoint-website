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
    
    echo "<!-- Database connection successful -->";
    
} catch (Exception $e) {
    error_log("Database connection failed: " . $e->getMessage());
    echo "<!-- Database connection failed: " . $e->getMessage() . " -->";
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
$admins = [];
if ($pdo) {
    try {
        // First, let's test if we can connect to the database
        $testQuery = $pdo->query("SELECT 1");
        echo "<!-- Database query test successful -->";
        
        // Now fetch admin data
        $stmt = $pdo->prepare("SELECT id, admin_id, first_name, middle_name, last_name, barangay_position, birthdate, username, picture, account_status, user_status, account_created, last_active, role FROM admins WHERE account_status = 'active' ORDER BY account_created DESC");
        $stmt->execute();
        $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Debug output
        echo "<!-- Admin count: " . count($admins) . " -->";
        if (count($admins) > 0) {
            foreach ($admins as $admin) {
                echo "<!-- Admin: " . htmlspecialchars($admin['first_name']) . " " . htmlspecialchars($admin['last_name']) . " (ID: " . htmlspecialchars($admin['admin_id']) . ") -->";
            }
        } else {
            echo "<!-- No admins found with account_status = 'active' -->";
            
            // Let's check if there are any admins at all
            $allAdminsStmt = $pdo->query("SELECT COUNT(*) as total FROM admins");
            $totalAdmins = $allAdminsStmt->fetch(PDO::FETCH_ASSOC);
            echo "<!-- Total admins in database: " . $totalAdmins['total'] . " -->";
            
            // Check what account_status values exist
            $statusStmt = $pdo->query("SELECT DISTINCT account_status FROM admins");
            $statuses = $statusStmt->fetchAll(PDO::FETCH_COLUMN);
            echo "<!-- Account statuses in database: " . implode(', ', $statuses) . " -->";
        }
        
    } catch (PDOException $e) {
        error_log("Database query error: " . $e->getMessage());
        echo "<!-- Database query error: " . htmlspecialchars($e->getMessage()) . " -->";
        $admins = [];
    }
} else {
    echo "<!-- No PDO connection available -->";
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
    <link rel="stylesheet" href="/ALERTPOINT/css/Users.css">
    <link rel="stylesheet" href="/ALERTPOINT/css/footer.css">
    <link rel="stylesheet" href="/ALERTPOINT/css/nav-bar.css">

    <!-- Firebase App (the core Firebase SDK) is always required and must be listed first -->
    <script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-app-compat.js"></script>

    <!-- Add Firebase products that you want to use -->
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

            <div class="stat-card hover-card rounded-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Total Admins</p>
                        <p class="text-2xl font-bold text-gray-900" id="total-admins">0</p>
                    </div>
                    <div class="p-3">
                        <i class="fas fa-user-shield text-purple-600 text-3xl"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card hover-card rounded-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Archived</p>
                        <p class="text-2xl font-bold text-gray-900" id="archived-count">0</p>
                    </div>
                    <div class="p-3">
                        <i class="fas fa-archive text-red-600 text-3xl"></i>
                    </div>
                </div>
            </div>
        </div>


        <!-- Filters and Search (Optimized) -->
        <div class="bg-white rounded-lg shadow-md p-4 mb-6">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <!-- Filter Tabs -->
                <div class="flex flex-wrap gap-2">
                    <button onclick="filterUsers('all')" class="filter-tab active px-4 py-2 rounded-md text-sm font-medium" id="filter-all">
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
                        <input type="text" placeholder="Search users..." class="search-input pl-10 pr-3 py-2 rounded-md w-full text-sm" id="search-input" oninput="searchUsers()">
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
        <!-- <p class="text-sm text-gray-600 mt-1">Total Admins: <?php echo count($admins); ?></p> -->
        <?php if ($pdo): ?>
            <p class="text-xs text-green-600 mt-1">✓ Database Connected</p>
        <?php else: ?>
            <p class="text-xs text-red-600 mt-1">✗ Database Connection Failed</p>
        <?php endif; ?>
    </div>
    <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="users-grid">
            
            <!-- Sample Resident User Card -->
            <div class="user-card bg-white rounded-xl p-6 border border-gray-200 shadow-sm">
                <div class="flex items-start justify-between mb-4">
                    <div class="flex items-center space-x-4">
                        <div class="w-12 h-12 bg-blue-500 text-white rounded-full flex items-center justify-center font-semibold text-lg relative">
                            JD
                            <div class="absolute -bottom-1 -right-1 w-4 h-4 bg-green-500 rounded-full border-2 border-white"></div>
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

            <?php if (empty($admins)): ?>
                <div class="col-span-full text-center py-8 text-gray-500">
                    <i class="fas fa-users text-4xl mb-4"></i>
                    <p>No admin users found in the database.</p>
                    <?php if (!$pdo): ?>
                        <p class="text-red-500 text-sm mt-2">Database connection issue detected.</p>
                    <?php else: ?>
                        <p class="text-sm mt-2">Check browser console for debugging information.</p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <?php foreach ($admins as $admin): ?>
                <?php 
                    $fullName = trim($admin['first_name'] . ' ' . (!empty($admin['middle_name']) ? $admin['middle_name'] . ' ' : '') . $admin['last_name']);
                    $initials = strtoupper(substr($admin['first_name'], 0, 1) . substr($admin['last_name'], 0, 1));
                    $statusClass = $admin['user_status'] === 'online' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
                    $statusText = ucfirst($admin['user_status']);
                    $statusIndicator = $admin['user_status'] === 'online' ? 'bg-green-500' : 'bg-red-500';
                    $lastSeenText = getLastSeenText($admin['last_active']);
                    
                    // Check if picture exists - fix the path handling
                    $picturePath = '';
                    $pictureExists = false;
                    
                    if (!empty($admin['picture'])) {
                        // Handle both absolute and relative paths
                        if (strpos($admin['picture'], '/ALERTPOINT') === 0) {
                            $picturePath = $admin['picture']; // Already has /ALERTPOINT
                        } else {
                            $picturePath = '/ALERTPOINT' . $admin['picture']; // Add /ALERTPOINT
                        }
                        
                        $fullPath = $_SERVER['DOCUMENT_ROOT'] . $picturePath;
                        $pictureExists = file_exists($fullPath);
                        
                        // Debug picture path
                        echo "<!-- Picture path for " . $admin['admin_id'] . ": " . $picturePath . " (exists: " . ($pictureExists ? 'yes' : 'no') . ") -->";
                    }
                ?>
                <!-- Admin User Card - ID: <?php echo $admin['id']; ?> -->
                <div class="user-card bg-white rounded-xl p-6 border border-gray-200 shadow-sm">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex items-center space-x-4">
                            <?php if ($pictureExists): ?>
                                <div class="w-12 h-12 rounded-full flex items-center justify-center font-semibold text-lg relative overflow-hidden">
                                    <img src="<?php echo htmlspecialchars($picturePath); ?>" alt="<?php echo htmlspecialchars($fullName); ?>" class="w-full h-full object-cover">
                                    <div class="absolute -bottom-1 -right-1 w-4 h-4 <?php echo $statusIndicator; ?> rounded-full border-2 border-white"></div>
                                </div>
                            <?php else: ?>
                                <div class="w-12 h-12 bg-purple-500 text-white rounded-full flex items-center justify-center font-semibold text-lg relative">
                                    <?php echo $initials; ?>
                                    <div class="absolute -bottom-1 -right-1 w-4 h-4 <?php echo $statusIndicator; ?> rounded-full border-2 border-white"></div>
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
                            <button class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-xs transition-colors" onclick="editAdmin(<?php echo $admin['id']; ?>)">
                                <i class="fas fa-edit mr-1"></i>Edit
                            </button>
                            <button class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-xs transition-colors" onclick="removeAdmin(<?php echo $admin['id']; ?>)">
                                <i class="fas fa-user-times mr-1"></i>Remove
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
            
        </div>
    </div>
</div>

        <!-- Archived Users Section -->
        <div class="archived-section rounded-xl shadow-lg p-6 hidden" id="archived-section">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-semibold text-gray-900 flex items-center">
                    <i class="fas fa-archive text-red-600 mr-3"></i>
                    Archived Users
                </h2>
                <p class="text-gray-600">These accounts are temporarily disabled</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="archived-grid">
                <!-- Archived user cards will be populated here -->
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

    <script src="/ALERTPOINT/javascript/USERS/Add_Admin.js"></script>



</body>
</html>