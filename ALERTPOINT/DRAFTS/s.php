<?php
// Database connection
require_once '../config/database.php';

// Initialize variables with default values
$activeAdmins = [];
$totalActiveAdmins = 0;
$totalArchivedAdmins = 0;
$pdo = null;

// Database connection and admin fetching
try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    if ($pdo) {
        // Fetch all active admins from database
        $stmt = $pdo->prepare("SELECT * FROM admins_tbl WHERE account_status = 'active' ORDER BY account_created DESC");
        $stmt->execute();
        $activeAdmins = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Count total active admins
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM admins_tbl WHERE account_status = 'active'");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $totalActiveAdmins = $result['count'];
        
        // Count archived/inactive admins
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM admins_tbl WHERE account_status IN ('inactive', 'suspended')");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $totalArchivedAdmins = $result['count'];
    }
} catch (Exception $e) {
    // Log error and use fallback values
    error_log("Database error in Users.php: " . $e->getMessage());
    $activeAdmins = [];
    $totalActiveAdmins = 0;
    $totalArchivedAdmins = 0;
    $pdo = null;
}

// Function to calculate time difference
function getTimeAgo($datetime) {
    if (empty($datetime) || $datetime === '0000-00-00 00:00:00') {
        return "Never";
    }
    
    try {
        $now = new DateTime();
        $lastActive = new DateTime($datetime);
        $diff = $now->diff($lastActive);
        
        if ($diff->days > 0) {
            return $diff->days == 1 ? "1 day ago" : $diff->days . " days ago";
        } elseif ($diff->h > 0) {
            return $diff->h == 1 ? "1 hour ago" : $diff->h . " hours ago";
        } elseif ($diff->i > 1) {
            return $diff->i . " minutes ago";
        } else {
            return "just now";
        }
    } catch (Exception $e) {
        return "Unknown";
    }
}

// Function to generate initials from name (first two initials only)
function getInitials($firstName, $middleName = '', $lastName = '') {
    $initials = '';
    
    // Always get first initial from first name
    if (!empty($firstName)) {
        $initials .= strtoupper(substr($firstName, 0, 1));
    }
    
    // Get second initial from middle name if available, otherwise from last name
    if (!empty($middleName)) {
        $initials .= strtoupper(substr($middleName, 0, 1));
    } elseif (!empty($lastName)) {
        $initials .= strtoupper(substr($lastName, 0, 1));
    }
    
    return $initials;
}

// Function to get full name
function getFullName($firstName, $middleName = '', $lastName = '') {
    $fullName = $firstName;
    if (!empty($middleName)) {
        $fullName .= " " . $middleName;
    }
    if (!empty($lastName)) {
        $fullName .= " " . $lastName;
    }
    return $fullName;
}

// Function to normalize picture path
function normalizePicturePath($picturePath) {
    if (empty($picturePath) || $picturePath === 'NULL' || strtolower($picturePath) === 'null') {
        return null;
    }
    
    // Handle relative paths that start with ../../
    if (strpos($picturePath, '../../') === 0) {
        // Remove ../../ and replace with /ALERTPOINT/
        $picturePath = str_replace('../../', '/ALERTPOINT/', $picturePath);
    }
    // Handle paths that already start with /ALERTPOINT/
    elseif (strpos($picturePath, '/ALERTPOINT/') === 0) {
        // Keep as is, it's already properly formatted
    }
    // Handle other relative paths - add /ALERTPOINT/ prefix
    elseif (strpos($picturePath, '/') !== 0 && strpos($picturePath, 'http') !== 0) {
        $picturePath = '/ALERTPOINT/' . $picturePath;
    }
    
    return $picturePath;
}
?>



   <!-- Dynamic Admin Cards from Database -->
                <?php if (!empty($activeAdmins)): ?>
                    <?php foreach ($activeAdmins as $admin): 
                        $fullName = getFullName(
                            $admin['first_name'] ?? '', 
                            $admin['middle_name'] ?? '', 
                            $admin['last_name'] ?? ''
                        );
                        $initials = getInitials(
                            $admin['first_name'] ?? '', 
                            $admin['middle_name'] ?? '', 
                            $admin['last_name'] ?? ''
                        );
                        $timeAgo = getTimeAgo($admin['last_active'] ?? null);
                        $isOnline = ($admin['user_status'] ?? 'offline') === 'online';
                        $statusBgColor = $isOnline ? 'bg-green-500' : 'bg-red-500';
                        $statusBadgeClass = $isOnline ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
                        $statusText = $isOnline ? 'Online' : 'Offline';
                        $statusClass = $isOnline ? 'online' : 'offline';
                        
                        // Generate random avatar background colors
                        $avatarColors = ['bg-violet-500', 'bg-blue-500', 'bg-green-500', 'bg-yellow-500', 'bg-red-500', 'bg-indigo-500', 'bg-purple-500', 'bg-pink-500'];
                        $avatarColor = $avatarColors[array_rand($avatarColors)];
                        
                        // Check if user has a profile picture
                        $picturePath = normalizePicturePath($admin['picture'] ?? null);
                        $hasPicture = !empty($picturePath);
                    ?>
                    
                    <div class="user-card admin-card bg-white rounded-xl p-6 border border-gray-200 shadow-sm" 
                        data-role="Admin" 
                        data-status="<?php echo htmlspecialchars($admin['user_status'] ?? 'offline'); ?>" 
                        data-name="<?php echo htmlspecialchars($fullName); ?>"
                        data-user-role="<?php echo htmlspecialchars($admin['barangay_position'] ?? 'Admin'); ?>">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex items-center space-x-4">
                                <div class="relative">
                                    <?php if ($hasPicture): ?>
                                        <div class="w-12 h-12 rounded-full overflow-hidden border-2 border-gray-200">
                                            <img src="<?php echo htmlspecialchars($picturePath); ?>" 
                                                alt="<?php echo htmlspecialchars($fullName); ?>" 
                                                class="w-full h-full object-cover"
                                                onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                        </div>
                                        <!-- Fallback initials div (hidden by default, shown on image error) -->
                                        <div class="w-12 h-12 <?php echo $avatarColor; ?> text-white rounded-full flex items-center justify-center font-semibold text-lg border-2 border-gray-200 absolute top-0 left-0" style="display: none;">
                                            <?php echo htmlspecialchars($initials); ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="w-12 h-12 <?php echo $avatarColor; ?> text-white rounded-full flex items-center justify-center font-semibold text-lg border-2 border-gray-200">
                                            <?php echo htmlspecialchars($initials); ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="absolute -bottom-1 -right-0 w-4 h-4 <?php echo $statusBgColor; ?> rounded-full border-2 border-white status-indicator <?php echo $statusClass; ?>"></div>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900 text-lg"><?php echo htmlspecialchars($fullName); ?></h3>
                                    <p class="text-sm text-gray-600"><?php echo htmlspecialchars($admin['barangay_position'] ?? 'Admin'); ?></p>
                                    <p class="text-xs text-gray-500 flex items-center mt-1">
                                        <i class="fas fa-user-shield text-gray-400 mr-2"></i><?php echo htmlspecialchars($admin['username'] ?? 'N/A'); ?>
                                    </p>
                                </div>
                            </div>
                            <div class="flex flex-col items-end">
                                <span class="px-3 py-1 rounded-full text-xs font-medium <?php echo $statusBadgeClass; ?>">
                                    <?php echo $statusText; ?>
                                </span>
                                <span class="text-xs text-gray-500 mt-1 capitalize px-2 py-1"><?php echo htmlspecialchars($admin['role'] ?? 'Admin'); ?></span>
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                            <div class="text-xs text-gray-500">
                                Last seen: <?php echo htmlspecialchars($timeAgo); ?>
                            </div>
                            <div class="flex space-x-2">
                                <button class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-xs transition-colors"
                                        onclick="editAdmin('<?php echo htmlspecialchars($admin['admin_id'] ?? ''); ?>')">
                                    <i class="fas fa-edit mr-1"></i>Edit
                                </button>
                                <button class="bg-orange-600 hover:bg-orange-700 text-white px-3 py-1 rounded text-xs transition-colors"
                                        onclick="archiveAdmin('<?php echo htmlspecialchars($admin['admin_id'] ?? ''); ?>')">
                                    <i class="fas fa-archive mr-1"></i>Archive
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <?php endforeach; ?>
                <?php endif; ?>

                <!-- No Admins Message (show if no admins found) -->
                <?php if (empty($activeAdmins)): ?>
                <div class="col-span-full text-center py-8 text-gray-500" id="no-active-admins">
                    <i class="fas fa-user-shield text-4xl mb-4 text-gray-400"></i>
                    <p class="text-lg font-semibold">No Active Admins</p>
                    <p class="text-sm">No active admin accounts found in the database.</p>
                </div>
                <?php endif; ?>




                <!-- Edit Admin Modal -->
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
                                <div class="profile-image-container relative">
                                    <img id="editProfilePreview" src="" alt="Profile Preview" class="w-16 h-16 rounded-full object-cover border-2 border-gray-200 hidden">
                                    <div id="editUploadPlaceholder" class="w-16 h-16 bg-gray-200 rounded-full flex items-center justify-center border-2 border-gray-300">
                                        <i class="fas fa-camera text-gray-400 text-lg"></i>
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
                                        <i class="fas fa-user-circle text-gray-400 mr-2"></i>First Name
                                    </label>
                                    <input type="text" id="edit_admin_fn" name="edit_admin_fn" 
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" 
                                        placeholder="First name" readonly>
                                    <div class="error-message text-red-500 text-xs mt-1 hidden"></div>
                                    <div class="flex items-center text-xs text-gray-500 mt-1">
                                        <i class="fas fa-info-circle mr-1"></i>
                                        This field cannot be changed
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-user-circle text-gray-400 mr-2"></i>Last Name
                                    </label>
                                    <input type="text" id="edit_admin_ln" name="edit_admin_ln" 
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" 
                                        placeholder="Last name" readonly>
                                    <div class="error-message text-red-500 text-xs mt-1 hidden"></div>
                                    <div class="flex items-center text-xs text-gray-500 mt-1">
                                        <i class="fas fa-info-circle mr-1"></i>
                                        This field cannot be changed
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-user text-gray-400 mr-2"></i>Middle Name
                                </label>
                                <input type="text" id="edit_admin_mn" name="edit_admin_mn" 
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" 
                                    placeholder="Middle name" readonly>
                                <div class="flex items-center text-xs text-gray-500 mt-1">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    This field cannot be changed
                                </div>
                            </div>

                            <!-- Birthdate Section -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-calendar text-gray-400 mr-2"></i>Birthdate
                                </label>
                                <div class="grid grid-cols-3 gap-3">
                                    <div>
                                        <select id="edit_birth_month" name="edit_birth_month" 
                                                class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors bg-white" disabled>
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
                                                class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors bg-white" disabled>
                                            <option value="">Day</option>
                                        </select>
                                    </div>
                                    <div>
                                        <select id="edit_birth_year" name="edit_birth_year" 
                                                class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors bg-white" disabled>
                                            <option value="">Year</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="flex items-center text-xs text-gray-500 mt-2">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    Birthdate cannot be changed
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