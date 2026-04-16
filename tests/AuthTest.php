<?php

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;

class AuthTest extends TestCase
{
    private $http;
    private $testEmail = 'test_user@example.com';
    private $testPassword = 'password123';
    private $testUsername = 'testuser';

    protected function setUp(): void
    {
        $this->http = new Client([
            'base_uri' => 'http://localhost:8000/',
            'http_errors' => false // Prevents Guzzle from throwing exceptions on 4xx/5xx errors
        ]);

        // ckean up the mock database before each test
        $dbPath = __DIR__ . '/../config/users.json';
        if (file_exists($dbPath)) {
            file_put_contents($dbPath, json_encode([]));
        }
    }
// JWT Helper Functions Unit Test
    public function testJwtGenerationAndVerification()
    {
        require_once __DIR__ . '/../config/jwt.php';

        $payload = ['user_id' => 999, 'username' => 'jwttester'];
        $token = generate_jwt($payload);

        $this->assertIsString($token);
        $this->assertEquals(3, count(explode('.', $token)));

        $verifiedPayload = verify_jwt($token);
        $this->assertIsArray($verifiedPayload);
        $this->assertEquals(999, $verifiedPayload['user_id']);
    }

// Registration API Integ Test
    public function testUserRegistrationSuccess()
    {
        $response = $this->http->post('api/auth/register.php', [
            'json' => [
                'email' => $this->testEmail,
                'username' => $this->testUsername,
                'password' => $this->testPassword
            ]
        ]);

        $this->assertEquals(201, $response->getStatusCode());
        
        $body = json_decode($response->getBody(), true);
        $this->assertEquals('User registered successfully', $body['message']);
    }

    public function testUserRegistrationDuplicateEmail()
    {
        // first request creates the user
        $this->http->post('api/auth/register.php', [
            'json' => ['email' => $this->testEmail, 'username' => $this->testUsername, 'password' => $this->testPassword]
        ]);

        // second request should fail with 409 Conflict
        $response = $this->http->post('api/auth/register.php', [
            'json' => ['email' => $this->testEmail, 'username' => 'anotheruser', 'password' => 'newpass']
        ]);

        $this->assertEquals(409, $response->getStatusCode());
        
        $body = json_decode($response->getBody(), true);
        $this->assertEquals('Email already registered', $body['message']);
    }

    // Login Test Integ Test
    public function testUserLoginSuccessReturnsJwt()
    {
        // register the user first
        $this->http->post('api/auth/register.php', [
            'json' => ['email' => $this->testEmail, 'username' => $this->testUsername, 'password' => $this->testPassword]
        ]);

        // attempt Login
        $response = $this->http->post('api/auth/login.php', [
            'json' => [
                'email' => $this->testEmail,
                'password' => $this->testPassword
            ]
        ]);

        $this->assertEquals(200, $response->getStatusCode());
        
        $body = json_decode($response->getBody(), true);
        $this->assertEquals('Login successful', $body['message']);
        $this->assertArrayHasKey('token', $body);
    }

    public function testUserLoginFailsWithWrongPassword()
    {
        // register the user
        $this->http->post('api/auth/register.php', [
            'json' => ['email' => $this->testEmail, 'username' => $this->testUsername, 'password' => $this->testPassword]
        ]);

        // attempt Login with wrong password
        $response = $this->http->post('api/auth/login.php', [
            'json' => [
                'email' => $this->testEmail,
                'password' => 'wrongpassword123'
            ]
        ]);

        $this->assertEquals(401, $response->getStatusCode());
        
        $body = json_decode($response->getBody(), true);
        $this->assertEquals('Invalid email or password', $body['message']);
    }
}


?>