<?php

namespace App\Controllers;

use App\Providers\Validation\ValidatePasswordProvider;
use App\Providers\Email\SendEmailProvider;
use App\Providers\Email\VerifyEmailProvider;
use App\Providers\Auth\PasswordResetProvider;
use App\Models\User;
use PDO;

class UserController
{
    private $userModel;
    private $validatePasswordProvider;
    private $sendEmailProvider;
    private $verifyEmailProvider;
    private $passwordResetProvider;

    public function __construct(PDO $db)
    {
        // Initialize models and providers
        $this->userModel = new User($db);
        $this->validatePasswordProvider = new ValidatePasswordProvider();
        $this->sendEmailProvider = new SendEmailProvider();
        $this->verifyEmailProvider = new VerifyEmailProvider();
        $this->passwordResetProvider = new PasswordResetProvider($db);
    }

    // Create a new user
    public function createUser($data)
    {
        // Ensure all fields are set
        $firstName = $data['first_name'] ?? '';
        $lastName = $data['last_name'] ?? '';
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';
        $birthdate = $data['birthdate'] ?? '';
        $role = $data['role'] ?? '';

        // Validate the password
        if (!$this->validatePasswordProvider->validate($password, $firstName, $lastName)) {
            return $this->sendResponse('error', 'Password must be at least 8 characters long, contain both upper and lower case letters, a number, and not include first or last names.');
        }

        // Ensure role is either '0001' or '0002'
        if (!in_array($role, ['0001', '0002'])) {
            return $this->sendResponse('error', 'Invalid role specified. Only buyers and sellers can register.');
        }

        // Assign the user data
        $this->userModel->first_name = $firstName;
        $this->userModel->last_name = $lastName;
        $this->userModel->email = $email;
        $this->userModel->password = $password;
        $this->userModel->birthdate = $birthdate;
        $this->userModel->role = $role;
        $this->userModel->verification_token = bin2hex(random_bytes(32));

        // Check if email already exists
        $result = $this->userModel->create();
        if ($result === 'email_exists') {
            return $this->sendResponse('error', 'Email already in use.');
        }

        if ($result === true) {
            // Send verification email
            $emailSent = $this->sendEmailProvider->sendVerificationEmail($email, $firstName, $this->userModel->verification_token);

            if ($emailSent) {
                return $this->sendResponse('success', 'Account created successfully. Please check your email for verification.');
            } else {
                return $this->sendResponse('error', 'Account created, but failed to send verification email. Please try again later.');
            }
        } else {
            return $this->sendResponse('error', 'Failed to create account.');
        }
    }

    // Send standardized response
    private function sendResponse($status, $message, $data = [])
    {
        error_log("Sending response: $status - $message");  // Logging response
        echo json_encode(array_merge(['status' => $status, 'message' => $message], $data));
        http_response_code($status === 'success' ? 200 : 400);  // Adjust the HTTP status code accordingly
        exit();  // Stop further execution
    }

    // Verify Email using VerifyEmailProvider
    public function verifyEmail($token)
    {
        $result = $this->verifyEmailProvider->verify($token, $this->userModel);
        $this->sendResponse($result['status'], $result['message']);
    }

    // Request Password Reset using PasswordResetProvider (under Auth)
    public function requestPasswordReset($email)
    {
        $result = $this->passwordResetProvider->requestReset($email);
        $this->sendResponse($result['status'], $result['message']);
    }

    public function resetPassword($data)
    {
        // Log the incoming request data
        error_log("Reset password request data: " . json_encode($data));
    
        // Check if 'otp' and 'new_password' are present
        if (empty($data['otp'])) {
            error_log('OTP is missing from request');
            return $this->sendResponse('error', 'OTP is required.');
        }
    
        if (empty($data['new_password'])) {
            error_log('New password is missing from request');
            return $this->sendResponse('error', 'New password is required.');
        }
    
        // Validate the new password
        if (!$this->validatePasswordProvider->validate($data['new_password'])) {
            error_log('New password validation failed');
            return $this->sendResponse('error', 'Invalid password. Ensure it meets the required criteria.');
        }
    
        // Proceed with OTP validation and password reset
        error_log('Proceeding with OTP validation');
        $result = $this->passwordResetProvider->resetPassword($data['otp'], $data['new_password']);
        error_log('Password reset result: ' . json_encode($result));
    
        // Send the response based on the result
        return $this->sendResponse($result['status'], $result['message']);
    }
    
    // Update user information
    public function updateUser($data, $uuid)
    {
        $userToUpdate = $this->userModel->read($uuid);

        // Check if user exists
        if (!$userToUpdate) {
            return $this->sendResponse('error', 'User not found.');
        }

        // Allowed fields for update
        $allowedFields = ['first_name', 'last_name', 'email', 'birthdate', 'role', 'address', 'phone_number'];
        foreach ($data as $key => $value) {
            if (!in_array($key, $allowedFields)) {
                unset($data[$key]);
            }
        }

        // Assign the updated fields
        foreach ($data as $key => $value) {
            $this->userModel->{$key} = $value;
        }

        $this->userModel->uuid = $uuid;

        // Update user info in the model
        if ($this->userModel->update()) {
            return $this->sendResponse('success', 'User information updated successfully.');
        } else {
            return $this->sendResponse('error', 'Failed to update user information.');
        }
    }
}
