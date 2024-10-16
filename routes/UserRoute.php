<?php

namespace Routes\UserRoute;

use App\Controllers\UserController;
use Config\DatabaseConnection;

require_once __DIR__ . '/../config/DatabaseConnection.php';

class UserRoute {

    public function handleUserRoute($uri, $method) {
        // Initialize the database connection
        $dbConnection = new DatabaseConnection();
        $db = $dbConnection->getConnection();

        // Initialize the UserController
        $userController = new UserController($db);

        // Normalize the URI for accurate matching
        $uri = str_replace('/user', '', $uri);

        // Debugging - Output the current URI after normalization
        error_log("User Route Handling for URI: $uri");

        // Define user-related routes
        switch ($uri) {
            case '/register': // POST - Register a new user
                if ($method === 'POST') {
                    error_log("Handling User Registration");
                    $data = json_decode(file_get_contents('php://input'), true);
                    $userController->createUser($data);
                }
                break;

            case '/verify': // GET - Verify user email
                if ($method === 'GET') {
                    error_log("Handling Email Verification");
                    $token = $_GET['token'] ?? '';
                    $userController->verifyEmail($token);
                }
                break;

            case '/request-password-reset': // POST - Request OTP for password reset
                if ($method === 'POST') {
                    error_log("Handling Password Reset Request");
                    $data = json_decode(file_get_contents('php://input'), true);
                    $userController->requestPasswordReset($data['email']);
                }
                break;

                case '/reset-password': // PUT - Verify OTP and reset password
                    if ($method === 'PUT') {
                        error_log("Handling Password Reset");
                        $data = json_decode(file_get_contents('php://input'), true);
                        $userController->resetPassword($data);
                    }
                    break;

            default:
                error_log("No matching user route found for URI: $uri");
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Route not found'
                ]);
                http_response_code(404);
                break;
        }
    }
}
