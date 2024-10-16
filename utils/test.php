<?php

require_once __DIR__ . '/vendor/autoload.php';

use \App\Config\DatabaseConnection;

$db = new DatabaseConnection();
$conn = $db->getConnection();

if ($conn) {
    echo "Database connection successful!";
} else {
    echo "Database connection failed.";
}
