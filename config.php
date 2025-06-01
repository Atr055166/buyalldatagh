<?php
// config.php
session_start();
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "databundle_sales";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create tables if they don't exist
$conn->query("CREATE TABLE IF NOT EXISTS orders (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reference VARCHAR(50) NOT NULL,
    network VARCHAR(20) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    bundle VARCHAR(20) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    status ENUM('pending','completed','failed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$conn->query("CREATE TABLE IF NOT EXISTS admins (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(30) NOT NULL,
    password VARCHAR(255) NOT NULL
)");

//To alter the admin page to include the registration data
$sql = "
ALTER TABLE admins
ADD COLUMN email VARCHAR(100) NOT NULL AFTER username,
ADD COLUMN full_name VARCHAR(100) NOT NULL AFTER password,
ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN last_login TIMESTAMP NULL,
ADD UNIQUE INDEX unique_username (username),
ADD UNIQUE INDEX unique_email (email);
";





// Insert admin user if not exists
$adminCheck = $conn->query("SELECT * FROM admins WHERE username = 'admin'");
if ($adminCheck->num_rows == 0) {
    $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
    $conn->query("INSERT INTO admins (username, password) VALUES ('admin', '$hashedPassword')");
}
?>