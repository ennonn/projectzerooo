<?php

namespace App\Controllers;

use App\Models\User;
use App\Providers\ValidateRole;

class AdminController {
    private $userModel; // Store User model instance
    private $roleValidator; // Store role validation instance

    public function __construct(User $userModel, ValidateRole $roleValidator) {
        $this->userModel = $userModel;
        $this->roleValidator = $roleValidator;
    }

    // Read user
    public function readUser($uuid) {
        $userData = $this->userModel->read($uuid); // Use userModel here
        
        if ($userData) {
            return [
                'status' => 'success',
                'data' => $userData
            ];
        } else {
            return [
                'status' => 'error',
                'message' => 'User not found.'
            ];
        }
    }

    // Update user
    public function updateUser($data)
{
    // Admin validation would happen here

    // Extract user UUID from the incoming data
    $uuid = $data['uuid'];
    $userToUpdate = $this->userModel->read($uuid);

    if (!$userToUpdate) {
        echo json_encode([
            'status' => 'error',
            'message' => 'User not found.'
        ]);
        return;
    }

    // Only allow admin to change role
    if (isset($data['role'])) {
        // Ensure role can only be '0001' or '0002' for normal users
        if (!in_array($data['role'], ['0001', '0002', '4DM1N'])) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid role specified.'
            ]);
            return;
        }
    }

    // Update other user fields
    $this->userModel->uuid = $uuid; // Set the UUID for the user being updated
    $this->userModel->first_name = $data['first_name'];
    $this->userModel->last_name = $data['last_name'];
    $this->userModel->email = $data['email'];
    $this->userModel->birthdate = $data['birthdate'];
    
    // Handle password if needed
    if (isset($data['password'])) {
        $this->userModel->password = $data['password']; // Assuming it's hashed inside the model
    }

    // Update method call
    if ($this->userModel->update()) {
        echo json_encode([
            'status' => 'success',
            'message' => 'User information updated successfully.'
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to update user information.'
        ]);
    }
}


    // Delete user
    public function deleteUser($uuid) {
        $this->userModel->uuid = $uuid;

        if ($this->userModel->delete()) {
            return [
                'status' => 'success',
                'message' => 'User deleted successfully.'
            ];
        } else {
            return [
                'status' => 'error',
                'message' => 'Failed to delete user.'
            ];
        }
    }
}
