<?php

header("Content-Type: application/json");
require_once '../../config/mock_db.php'; //use actual db later once configured
require_once '../../config/jwt.php'; //jwt helper

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(["message" => "Method not allowed"]));
}

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->email) || !isset($data->password)) {
    http_response_code(400);
    exit(json_encode(["message" => "Missing credentials"]));
}

$users = getUsers();
$foundUser = null;

// find user by email in mock data, use db next time.
foreach ($users as $user) {
    if ($user['email'] === $data->email) {
        $foundUser = $user;
        break;
    }
}

if (!$foundUser) {
    http_response_code(401);
    exit(json_encode(["message" => "Invalid email or password"]));
}

// verify plain text password against the stored hash
if (password_verify($data->password, $foundUser['password'])) {
    $payload = [
        'user_id' => $foundUser['id'],
        'username' => $foundUser['username'],
        'exp' => time() + (60 * 60 * 24) // 24 hour expiration
    ];
    $token = generate_jwt($payload);
    
    http_response_code(200);
    echo json_encode([
        "message" => "Login successful",
        "token" => $token
    ]);
} else {
    http_response_code(401);
    echo json_encode(["message" => "Invalid email or password"]);
}


?>