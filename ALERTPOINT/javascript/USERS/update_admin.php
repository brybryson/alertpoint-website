<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Disable error display to prevent JSON corruption
error_reporting(0);
ini_set('display_errors', 0);

// Database connection
require_once '../../config/database.php';

// Response array
$response = [
    'success' => false,
    'message' => '',
    'data' => null
];

try {
    // Check if request method is POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Only POST method allowed');
    }
    
    // Validate required fields
    if (!isset($_POST['admin_id']) || empty(trim($_POST['admin_id']))) {
        throw new Exception('Admin ID is required');
    }
    
    if (!isset($_POST['barangay_position']) || empty(trim($_POST['barangay_position']))) {
        throw new Exception('Barangay position is required');
    }
    
    $adminId = trim($_POST['admin_id']);
    $barangayPosition = trim($_POST['barangay_position']);
    
    // Database connection
    $database = new Database();
    $pdo = $database->getConnection();
    
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }
    
    // Begin transaction
    $pdo->beginTransaction();
    
    try {
        // Check if admin exists and is active
        $checkStmt = $pdo->prepare("
            SELECT admin_id, first_name, middle_name, last_name, picture 
            FROM admins_tbl 
            WHERE admin_id = ? AND account_status = 'active'
        ");
        $checkStmt->execute([$adminId]);
        $existingAdmin = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$existingAdmin) {
            throw new Exception('Admin not found or not active');
        }
        
        // Handle profile image upload if provided
        $newPicturePath = null;
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $newPicturePath = handleImageUpload($_FILES['profile_image'], $adminId);
        }
        
        // Prepare update query
        if ($newPicturePath !== null) {
            // Update both position and picture
            $updateStmt = $pdo->prepare("
                UPDATE admins_tbl 
                SET barangay_position = ?, picture = ?, last_active = NOW() 
                WHERE admin_id = ? AND account_status = 'active'
            ");
            $updateResult = $updateStmt->execute([$barangayPosition, $newPicturePath, $adminId]);
        } else {
            // Update only position
            $updateStmt = $pdo->prepare("
                UPDATE admins_tbl 
                SET barangay_position = ?, last_active = NOW() 
                WHERE admin_id = ? AND account_status = 'active'
            ");
            $updateResult = $updateStmt->execute([$barangayPosition, $adminId]);
        }
        
        if (!$updateResult) {
            throw new Exception('Failed to update admin information');
        }
        
        // Check if any rows were affected
        if ($updateStmt->rowCount() === 0) {
            throw new Exception('No changes were made or admin not found');
        }
        
        // If we uploaded a new image, try to delete the old one
        if ($newPicturePath !== null && !empty($existingAdmin['picture'])) {
            deleteOldImage($existingAdmin['picture']);
        }
        
        // Commit transaction
        $pdo->commit();
        
        // Success response
        $fullName = trim($existingAdmin['first_name'] . ' ' . $existingAdmin['middle_name'] . ' ' . $existingAdmin['last_name']);
        $response['success'] = true;
        $response['message'] = "Admin '{$fullName}' updated successfully";
        $response['data'] = [
            'admin_id' => $adminId,
            'barangay_position' => $barangayPosition,
            'picture_updated' => ($newPicturePath !== null),
            'new_picture_path' => $newPicturePath
        ];
        
    } catch (Exception $e) {
        // Rollback transaction
        $pdo->rollback();
        
        // If we uploaded a file during this transaction, clean it up
        if (isset($newPicturePath) && $newPicturePath && file_exists($_SERVER['DOCUMENT_ROOT'] . $newPicturePath)) {
            unlink($_SERVER['DOCUMENT_ROOT'] . $newPicturePath);
        }
        
        throw $e;
    }
    
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    
    // Log error for debugging
    error_log("Update Admin Error: " . $e->getMessage());
    
} catch (PDOException $e) {
    $response['success'] = false;
    $response['message'] = 'Database error occurred';
    
    // Log detailed error for debugging
    error_log("Update Admin PDO Error: " . $e->getMessage());
}

// Return JSON response
echo json_encode($response);

/**
 * Handle image upload
 */
function handleImageUpload($file, $adminId) {
    // Validate file
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
    $maxFileSize = 5 * 1024 * 1024; // 5MB
    
    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception('Invalid file type. Only JPG, JPEG, and PNG are allowed.');
    }
    
    if ($file['size'] > $maxFileSize) {
        throw new Exception('File size too large. Maximum size is 5MB.');
    }
    
    // Create upload directory if it doesn't exist
    $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/ALERTPOINT/uploads/admin/';
    if (!file_exists($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            throw new Exception('Failed to create upload directory');
        }
    }
    
    // Generate unique filename
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $fileName = 'admin_' . uniqid() . '.' . $fileExtension;
    $filePath = $uploadDir . $fileName;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        throw new Exception('Failed to upload file');
    }
    
    // Return the relative path for database storage
    return '../../uploads/admin/' . $fileName;
}

/**
 * Delete old image file
 */
function deleteOldImage($oldPicturePath) {
    if (empty($oldPicturePath) || $oldPicturePath === 'NULL' || strtolower($oldPicturePath) === 'null') {
        return;
    }
    
    try {
        // Convert path to absolute path
        $absolutePath = '';
        
        if (strpos($oldPicturePath, '/ALERTPOINT/') === 0) {
            $absolutePath = $_SERVER['DOCUMENT_ROOT'] . $oldPicturePath;
        } elseif (strpos($oldPicturePath, '../../') === 0) {
            $absolutePath = $_SERVER['DOCUMENT_ROOT'] . str_replace('../../', '/ALERTPOINT/', $oldPicturePath);
        } else {
            $absolutePath = $_SERVER['DOCUMENT_ROOT'] . '/ALERTPOINT/' . ltrim($oldPicturePath, '/');
        }
        
        // Delete file if it exists
        if (file_exists($absolutePath)) {
            unlink($absolutePath);
        }
    } catch (Exception $e) {
        // Log error but don't throw - deleting old image is not critical
        error_log("Failed to delete old image: " . $e->getMessage());
    }
}
?>