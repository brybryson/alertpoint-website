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
        // Check if admin exists and is inactive
        $checkStmt = $pdo->prepare("
            SELECT admin_id, first_name, middle_name, last_name, barangay_position, account_status 
            FROM admins_tbl 
            WHERE admin_id = ?
        ");
        $checkStmt->execute([$adminId]);
        $admin = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$admin) {
            throw new Exception('Admin not found');
        }
        
        if ($admin['account_status'] === 'active') {
            throw new Exception('Admin is already active');
        }
        
        if ($admin['account_status'] === 'suspended') {
            throw new Exception('Cannot restore suspended admin. Please contact system administrator.');
        }
        
        // Restore the admin (change status to active)
        $restoreStmt = $pdo->prepare("
            UPDATE admins_tbl 
            SET account_status = 'active', 
                user_status = 'offline',
                last_active = NOW() 
            WHERE admin_id = ? AND account_status IN ('inactive', 'archived')
        ");
        
        $restoreResult = $restoreStmt->execute([$adminId]);
        
        if (!$restoreResult) {
            throw new Exception('Failed to restore admin');
        }
        
        // Check if any rows were affected
        if ($restoreStmt->rowCount() === 0) {
            throw new Exception('Admin not found or cannot be restored');
        }
        
        // Log the restore action (optional)
        $logStmt = $pdo->prepare("
            INSERT INTO admin_logs (admin_id, action, action_details, action_timestamp) 
            VALUES (?, 'RESTORED', 'Admin account restored from archive', NOW())
        ");
        
        try {
            $logStmt->execute([$adminId]);
        } catch (PDOException $e) {
            // If logging fails, don't fail the entire operation
            error_log("Failed to log restore action: " . $e->getMessage());
        }
        
        // Commit transaction
        $pdo->commit();
        
        // Success response
        $fullName = trim($admin['first_name'] . ' ' . ($admin['middle_name'] ? $admin['middle_name'] . ' ' : '') . $admin['last_name']);
        $response['success'] = true;
        $response['message'] = "Admin '{$fullName}' has been restored successfully";
        $response['data'] = [
            'admin_id' => $adminId,
            'full_name' => $fullName,
            'position' => $admin['barangay_position'],
            'previous_status' => $admin['account_status'],
            'new_status' => 'active'
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
    error_log("Restore Admin Error: " . $e->getMessage());
    
} catch (PDOException $e) {
    $response['success'] = false;
    $response['message'] = 'Database error occurred';
    
    // Log detailed error for debugging
    error_log("Restore Admin PDO Error: " . $e->getMessage());
}

// Return JSON response
echo json_encode($response);
?>