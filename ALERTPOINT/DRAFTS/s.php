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
        $now = new DateTime();
        $lastActiveTime = new DateTime($lastActive);
        $diff = $now->diff($lastActiveTime);
        
        // Calculate total hours and days
        $totalHours = ($diff->days * 24) + $diff->h;
        $totalMinutes = ($totalHours * 60) + $diff->i;
        
        if ($totalMinutes < 1) {
            return "Just now";
        } elseif ($totalMinutes < 60) {
            return $totalMinutes . " minute" . ($totalMinutes != 1 ? "s" : "") . " ago";
        } elseif ($totalHours < 24) {
            return $totalHours . " hour" . ($totalHours != 1 ? "s" : "") . " ago";
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

<!-- Users Section -->
<div class="bg-white rounded-xl shadow-lg mb-8" id="active-users-section">
    <div class="p-6 border-b border-gray-200">
        <h2 class="text-xl font-semibold text-gray-900">AlertPoint Users</h2>
        <p class="text-sm text-gray-600 mt-1">Total Admins: <?php echo count($admins); ?></p>
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
                            <p class="text-sm text-gray-600">123 Rizal Street</p>
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


    <script src="/ALERTPOINT/javascript/USERS/Users.js"></script>
