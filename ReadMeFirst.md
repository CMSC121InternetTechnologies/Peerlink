# Developer Notes

## How to Run Tests

To successfully run the integration tests for the authentication APIs, you need to have a local server running so the Guzzle HTTP client can send actual requests to your endpoints.

1. **Start the Local PHP Server:**
   Open a terminal, navigate to the **root directory** of your project (where the `api`, `config`, and `tests` folders are located), and run the following command:
   ```bash
   php -S localhost:8000
   ```
   *Keep this terminal window open. This server will handle the incoming test requests.*

2. **Run the Test Suite:**
   Open a **second** terminal window, ensure you are in the project root directory, and execute PHPUnit:
   ```bash
   ./vendor/bin/phpunit tests/AuthTest.php
   ```
   *(Note for Windows users: If the path fails, use `vendor\bin\phpunit tests\AuthTest.php`)*

## If you don't have phpunit to run tests
**Since composer.json is here:**
run `composer install` to install all dependencies all at once.

---

## How to Use the Authentication APIs

The authentication system is built on RESTful principles, accepting JSON payloads and returning JSON responses. 

### 1. Registration (`api/auth/register.php`)
This endpoint creates a new user account and hashes their password.
* **Method:** `POST`
* **Payload Requirements:**
  ```json
  {
    "username": "johndoe",
    "email": "johndoe@example.com",
    "password": "your_secure_password"
  }
  ```
* **Behavior:** It checks the configuration files to see if the email already exists. If not, it creates a unique ID, hashes the password using `password_hash()`, and saves the record.

### 2. Login (`api/auth/login.php`)
This endpoint verifies user credentials and issues a JSON Web Token (JWT) for session management.
* **Method:** `POST`
* **Payload Requirements:**
  ```json
  {
    "email": "johndoe@example.com",
    "password": "your_secure_password"
  }
  ```
* **Behavior:** It searches the database for the provided email. If found, it uses `password_verify()` against the stored hash. Upon success, it generates a JWT valid for 24 hours and returns it to the client. The frontend should store this token (e.g., in `localStorage`) and attach it as a `Bearer` token in the `Authorization` header for all future protected requests.

---

## Understanding the Configuration Files

The `config/` directory holds the core logic that powers the storage and security of the application. 

### `jwt.php` (Security Helper)
This file handles the creation and validation of JSON Web Tokens.
* **Usage:** It relies on the `SECRET_KEY` pulled from the `.env` file. 
* **Functions:** * `generate_jwt($payload)`: Takes user data (like ID and username), adds an expiration time, and signs it using HMAC SHA-256.
  * `verify_jwt($jwt)`: Deconstructs an incoming token, verifies the signature against the server's secret key, and ensures it hasn't been tampered with.

### `mock_db.php` (Database Helper)
Because this prototype does not use a SQL database, this file acts as the bridge between your PHP scripts and the JSON storage file.
* **Usage:** It provides two simple helper functions to interact with the data safely.
* **Functions:**
  * `getUsers()`: Reads the `users.json` file and converts it into a usable PHP array. If the file doesn't exist, it creates it automatically.
  * `saveUsers($users)`: Takes a PHP array, encodes it back into pretty-printed JSON, and overwrites the `users.json` file.

### `users.json` (The Mock Database)
This is a flat file that acts as your database tables. 
* **Usage:** It stores an array of user objects. You can open this file in your text editor to manually verify that test accounts are being created, view the generated mock IDs (`user_...`), and see the resulting bcrypt password hashes.

## Project TODO
### 04/16/2026
- [] Make an .env file for environmental var. dont forget to gitignore it
- [] Refactor custom JWT helper to use the `firebase/php-jwt` library for production
- [] Replace mock JSON database with an actual db for production

## Tech Debt and Migration Path
### 1. Database Migration (`mock_db.php` -> MySQL)
Currently, the system uses a flat-file JSON structure (`users.json`) to act as the database. This is strictly for rapid prototyping and will not scale.
* **Action:** Replace the `getUsers()` and `saveUsers()` functions inside `mock_db.php` with standard PDO SQL queries.
* **Cleanup:** Delete `users.json` from the server once the migration is complete.
* **Setup:** The `.env` file already contains placeholder variables (`DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`) ready to be utilized for the PDO connection string.

### 2. JWT Library Replacement (`jwt.php`)
The current `jwt.php` file contains a custom implementation of HS256 JWT generation and verification.
* **Action:** Swap the custom `generate_jwt` and `verify_jwt` functions with the `firebase/php-jwt` Composer package. Run `composer require firebase/php-jwt` to install it.
* **Reason:** While the custom implementation is great for learning, established libraries handle edge cases, strict compliance (RFC 7519), and potential security vulnerabilities automatically.

### 3. Security & Environment Variables
* **Action:** Ensure that the `SECRET_KEY` in `jwt.php` is dynamically pulled from the `.env` file (e.g., `$_ENV['SECRET_KEY']`).
* **Warning:** The fallback hardcoded secret (`sekretong_malupet`) MUST be removed from `jwt.php` before deploying to a production server to prevent security breaches if the `.env` file fails to load. Ensure `.env` is listed in your `.gitignore` file.

**Desirre Bless Barbosa, 04/16/26.**