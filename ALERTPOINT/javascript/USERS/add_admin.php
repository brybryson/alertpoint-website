<?php

// Add these lines at the very top for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Prevent HTML error output and ensure JSON response
ini_set('display_errors', 0);
error_reporting(0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Capture any unexpected output
ob_start();

try {
    // Fix the path to database.php - adjust based on your actual folder structure
    require_once '../../config/database.php';
    
    // Clean any output that might have been generated
    ob_clean();
    
} catch (Exception $e) {
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Database connection error: ' . $e->getMessage()
    ]);
    exit;
}

class AdminHandler {
    private $conn;
    private $upload_dir;
    
    public function __construct() {
        try {
            $database = new Database();
            $this->conn = $database->getConnection();
            
            if (!$this->conn) {
                throw new Exception('Database connection failed');
            }
            
            // Fixed upload directory path
            $this->upload_dir = realpath(dirname(__FILE__) . '/../../uploads/admin/');
            
            if (!$this->upload_dir) {
                // If the directory doesn't exist, create it
                $upload_path = dirname(__FILE__) . '/../../uploads/admin/';
                if (!is_dir($upload_path)) {
                    mkdir($upload_path, 0755, true);
                }
                $this->upload_dir = realpath($upload_path);
            }
            
            // Ensure trailing slash
            $this->upload_dir = rtrim($this->upload_dir, '/') . '/';
            
        } catch (Exception $e) {
            throw new Exception('Constructor error: ' . $e->getMessage());
        }
    }
    
    public function generateNextAdminId() {
        try {
            $query = "SELECT admin_id FROM admins ORDER BY admin_id DESC LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            if ($stmt->rowCount() == 0) {
                return 'ADM0001';
            }
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $lastId = $result['admin_id'];
            
            // Extract numeric part and increment
            $numericPart = (int)substr($lastId, 3);
            $nextNum = $numericPart + 1;
            
            return 'ADM' . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
        } catch (Exception $e) {
            throw new Exception('Error generating admin ID: ' . $e->getMessage());
        }
    }
    
    public function uploadProfileImage($fileData, $adminId) {
        try {
            // If no file data provided, return null
            if (empty($fileData) || $fileData === null) {
                return null;
            }
            
            // Handle base64 image data
            if (strpos($fileData, 'data:image/') === 0) {
                // Extract image data from base64
                $data = explode(',', $fileData);
                if (count($data) != 2) {
                    throw new Exception('Invalid image data format');
                }
                
                $imageData = base64_decode($data[1]);
                if ($imageData === false) {
                    throw new Exception('Failed to decode base64 image data');
                }
                
                // Validate image data
                if (strlen($imageData) < 100) {
                    throw new Exception('Image data too small, possibly corrupted');
                }
                
                $mimeType = explode(';', explode(':', $data[0])[1])[0];
                
                // Determine file extension
                $extension = '';
                switch ($mimeType) {
                    case 'image/jpeg':
                    case 'image/jpg':
                        $extension = '.jpg';
                        break;
                    case 'image/png':
                        $extension = '.png';
                        break;
                    default:
                        throw new Exception('Unsupported image format: ' . $mimeType);
                }
                
                $filename = $adminId . $extension;
                $filepath = $this->upload_dir . $filename;
                
                // Debug logging
                error_log("Attempting to save image to: " . $filepath);
                error_log("Upload directory exists: " . (is_dir($this->upload_dir) ? 'Yes' : 'No'));
                error_log("Upload directory writable: " . (is_writable($this->upload_dir) ? 'Yes' : 'No'));
                
                $result = file_put_contents($filepath, $imageData);
                if ($result === false) {
                    throw new Exception('Failed to save image file to: ' . $filepath . '. Check directory permissions.');
                }
                
                // Verify file was created and has content
                if (!file_exists($filepath) || filesize($filepath) < 100) {
                    throw new Exception('Image file was not saved properly');
                }
                
                // Return relative path for web access
                return '/ALERTPOINT/uploads/admin/' . $filename;
            }
            
            return null;
        } catch (Exception $e) {
            error_log('Image upload error: ' . $e->getMessage());
            throw new Exception('Error uploading image: ' . $e->getMessage());
        }
    }
    
    public function validateInput($data) {
        $errors = [];
        
        // Validate required fields
        $required = ['admin_fn', 'admin_ln', 'birth_month', 'birth_day', 'birth_year', 'role', 'username', 'password'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                $errors[] = "Field $field is required";
            }
        }
        
        // Validate name lengths
        if (!empty($data['admin_fn']) && strlen(trim($data['admin_fn'])) < 2) {
            $errors[] = "First name must be at least 2 characters";
        }
        
        if (!empty($data['admin_ln']) && strlen(trim($data['admin_ln'])) < 2) {
            $errors[] = "Last name must be at least 2 characters";
        }
        
        // Validate password
        if (!empty($data['password']) && (strlen($data['password']) < 8 || !preg_match('/[a-zA-Z]/', $data['password']) || !preg_match('/[0-9]/', $data['password']))) {
            $errors[] = "Password must be at least 8 characters with letters and numbers";
        }
        
        // Validate birthdate
        if (!empty($data['birth_month']) && !empty($data['birth_day']) && !empty($data['birth_year'])) {
            if (!checkdate($data['birth_month'], $data['birth_day'], $data['birth_year'])) {
                $errors[] = "Invalid birthdate";
            }
        }
        
        return $errors;
    }
    
    public function createAdmin($data) {
        try {
            // Validate input
            $validation_errors = $this->validateInput($data);
            if (!empty($validation_errors)) {
                throw new Exception('Validation errors: ' . implode(', ', $validation_errors));
            }
            
            // Check if username already exists
            $check_query = "SELECT admin_id FROM admins WHERE username = :username";
            $check_stmt = $this->conn->prepare($check_query);
            $check_stmt->bindParam(':username', $data['username']);
            $check_stmt->execute();
            
            if ($check_stmt->rowCount() > 0) {
                throw new Exception('Username already exists');
            }
            
            // Generate admin ID
            $adminId = $this->generateNextAdminId();
            
            // Upload profile image if provided
            $picturePath = null;
            if (!empty($data['photo']) && $data['photo'] !== null) {
                $picturePath = $this->uploadProfileImage($data['photo'], $adminId);
            }
            
            // Format birthdate
            $birthdate = $this->formatBirthdate(
                $data['birth_month'],
                $data['birth_day'],
                $data['birth_year']
            );
            
            // Hash password
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            
            // Prepare SQL query
            $query = "INSERT INTO admins (
                admin_id, first_name, middle_name, last_name, 
                barangay_position, birthdate, username, password, 
                picture, account_status, user_status, account_created, last_active
            ) VALUES (
                :admin_id, :first_name, :middle_name, :last_name,
                :barangay_position, :birthdate, :username, :password,
                :picture, 'active', 'offline', NOW(), NOW()
            )";
            
            $stmt = $this->conn->prepare($query);
            
            // Bind parameters
            $stmt->bindParam(':admin_id', $adminId);
            $stmt->bindParam(':first_name', $data['admin_fn']);
            $stmt->bindParam(':middle_name', $data['admin_mn']);
            $stmt->bindParam(':last_name', $data['admin_ln']);
            $stmt->bindParam(':barangay_position', $data['role']);
            $stmt->bindParam(':birthdate', $birthdate);
            $stmt->bindParam(':username', $data['username']);
            $stmt->bindParam(':password', $hashedPassword);
            $stmt->bindParam(':picture', $picturePath);
            
            if ($stmt->execute()) {
                return [
                    'success' => true,
                    'message' => 'Admin account created successfully',
                    'data' => [
                        'admin_id' => $adminId,
                        'first_name' => $data['admin_fn'],
                        'middle_name' => $data['admin_mn'],
                        'last_name' => $data['admin_ln'],
                        'username' => $data['username'],
                        'birthdate' => $birthdate,
                        'role' => $data['role'],
                        'picture' => $picturePath
                    ]
                ];
            } else {
                throw new Exception('Failed to execute database query');
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error creating admin: ' . $e->getMessage()
            ];
        }
    }
    
    private function formatBirthdate($month, $day, $year) {
        $monthNames = [
            '01' => 'January', '02' => 'February', '03' => 'March',
            '04' => 'April', '05' => 'May', '06' => 'June',
            '07' => 'July', '08' => 'August', '09' => 'September',
            '10' => 'October', '11' => 'November', '12' => 'December'
        ];
        
        $monthName = $monthNames[$month] ?? 'Unknown';
        return $monthName . ' ' . (int)$day . ', ' . $year;
    }
}

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            throw new Exception('Invalid JSON data received');
        }
        
        // Debug logging
        error_log('Received data: ' . print_r($input, true));
        
        $adminHandler = new AdminHandler();
        $result = $adminHandler->createAdmin($input);
        
        // Clean any remaining output and send JSON
        ob_clean();
        echo json_encode($result);
        
    } catch (Exception $e) {
        ob_clean();
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
} else {
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Only POST method allowed'
    ]);
}

// End output buffering
ob_end_flush();
?>