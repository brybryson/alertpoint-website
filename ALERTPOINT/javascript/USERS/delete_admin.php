<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
require_once '../config/database.php';

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
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['admin_id'])) {
        throw new Exception('Admin ID is required');
    }
    
    $adminId = trim($input['admin_id']);
    
    if (empty($adminId)) {
        throw new Exception('Admin ID cannot be empty');
    }
    
    // Database connection
    $database = new Database();
    $pdo = $database->getConnection();
    
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }
    
    // Begin transaction
    $pdo->beginTransaction();
    
    try {
        // Check if admin exists
        $checkStmt = $pdo->prepare("
            SELECT admin_id, first_name, middle_name, last_name, barangay_position, 
                   account_status, picture 
            FROM admins_tbl 
            WHERE admin_id = ?
        ");
        $checkStmt->execute([$adminId]);
        $admin = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$admin) {
            throw new Exception('Admin not found');
        }
        
        // Check if admin is currently active (optional safety check)
        if ($admin['account_status'] === 'active') {
            throw new Exception('Cannot delete active admin. Please archive first.');
        }
        
        // Store admin info for response
        $fullName = trim($admin['first_name'] . ' ' . ($admin['middle_name'] ? $admin['middle_name'] . ' ' : '') . $admin['last_name']);
        $picturePath = $admin['picture'];
        
        // Log the deletion action before deleting (optional)
        $logStmt = $pdo->prepare("
            INSERT INTO admin_logs (admin_id, action, action_details, action_timestamp) 
            VALUES (?, 'DELETED', CONCAT('Admin account permanently deleted: ', ?), NOW())
        ");
        
        try {
            $logStmt->execute([$adminId, $fullName]);
        } catch (PDOException $e) {
            // If logging fails, don't fail the entire operation
            error_log("Failed to log delete action: " . $e->getMessage());
        }
        
        // Delete related data first (if you have related tables)
        // For example, if you have admin_sessions, admin_logs, etc.
        
        // Delete admin logs for this admin (optional - you might want to keep logs)
        /*
        $deleteLogsStmt = $pdo->prepare("DELETE FROM admin_logs WHERE admin_id = ?");
        $deleteLogsStmt->execute([$adminId]);
        */
        
        // Delete the admin record
        $deleteStmt = $pdo->prepare("DELETE FROM admins_tbl WHERE admin_id = ?");
        $deleteResult = $deleteStmt->execute([$adminId]);
        
        if (!$deleteResult) {
            throw new Exception('Failed to delete admin');
        }
        
        // Check if any rows were affected
        if ($deleteStmt->rowCount() === 0) {
            throw new Exception('Admin not found or already deleted');
        }
        
        // Delete profile picture file if exists
        if (!empty($picturePath)) {
            deleteProfilePicture($picturePath);
        }
        
        // Commit transaction
        $pdo->commit();
        
        // Success response
        $response['success'] = true;
        $response['message'] = "Admin '{$fullName}' has been permanently deleted";
        $response['data'] = [
            'admin_id' => $adminId,
            'full_name' => $fullName,
            'position' => $admin['barangay_position'],
            'previous_status' => $admin['account_status']
        ];
        
    } catch (Exception $e) {
        // Rollback transaction
        $pdo->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    
    // Log error for debugging
    error_log("Delete Admin Error: " . $e->getMessage());
    
} catch (PDOException $e) {
    $response['success'] = false;
    $response['message'] = 'Database error occurred';
    
    // Log detailed error for debugging
    error_log("Delete Admin PDO Error: " . $e->getMessage());
}

// Return JSON response
echo json_encode($response);

/**
 * Delete profile picture file
 */
function deleteProfilePicture($picturePath) {
    if (empty($picturePath) || $picturePath === 'NULL' || strtolower($picturePath) === 'null') {
        return;
    }
    
    try {
        // Convert path to absolute path
        $absolutePath = '';
        
        if (strpos($picturePath, '/ALERTPOINT/') === 0) {
            $absolutePath = $_SERVER['DOCUMENT_ROOT'] . $picturePath;
        } elseif (strpos($picturePath, '../../') === 0) {
            $absolutePath = $_SERVER['DOCUMENT_ROOT'] . str_replace('../../', '/ALERTPOINT/', $picturePath);
        } else {
            $absolutePath = $_SERVER['DOCUMENT_ROOT'] . '/ALERTPOINT/' . ltrim($picturePath, '/');
        }
        
        // Delete file if it exists
        if (file_exists($absolutePath)) {
            unlink($absolutePath);
            error_log("Deleted profile picture: " . $absolutePath);
        }
    } catch (Exception $e) {
        // Log error but don't throw - deleting image is not critical for data deletion
        error_log("Failed to delete profile picture: " . $e->getMessage());
    }
}
?>