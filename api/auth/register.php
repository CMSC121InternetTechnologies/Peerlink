<?php

header("Content-Type: application/json");
// use actual db next time
require_once '../../config/mock_db.php';

if($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(["message" => "Method not allowed"]));
}

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->email) || !isset($data->username) || !isset($data->password)) {
    http_response_code(400);
    exit(json_encode(["message" => "Missing required fields"]));
}

$users = getUsers();

// checking if email already exists
foreach ($users as $user) {
    if ($user['email'] === $data->email) {
        http_response_code(409); // Conflict
        exit(json_encode(["message" => "Email already registered"]));
    }
}

// Hash Password
$hashed_password = password_hash($data->password, PASSWORD_DEFAULT);

// Create new user record
$newUser = [
    'id' => uniqid('user_'), // Generate a random mock ID
    'username' => $data->username,
    'email' => $data->email,
    'password' => $hashed_password
];

// Save to mock database, use database later on
$users[] = $newUser;
saveUsers($users);

http_response_code(201);
echo json_encode(["message" => "User registered successfully"]);
?>