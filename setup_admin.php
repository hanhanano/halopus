<?php
require_once 'config/koneksi.php';

function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

try {
        $pdo->exec("
        CREATE TABLE IF NOT EXISTS admins (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(255) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            full_name VARCHAR(255) NOT NULL,
            role ENUM('super_admin', 'admin', 'librarian', 'staff') DEFAULT 'admin',
            status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
            last_login TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");

        $defaultAdmins = [
        [
            'username' => 'admin',
            'email' => 'admin@perpustakaan.com',
            'password' => hashPassword('admin123'),
            'full_name' => 'Administrator Sistem',
            'role' => 'super_admin'
        ],
        [
            'username' => 'librarian',
            'email' => 'librarian@perpustakaan.com',
            'password' => hashPassword('librarian123'),
            'full_name' => 'Pustakawan Utama',
            'role' => 'admin'
        ],
        [
            'username' => 'user',
            'email' => 'user@perpustakaan.com',
            'password' => hashPassword('user123'),
            'full_name' => 'User Perpustakaan',
            'role' => 'staff'
        ],
        [
            'username' => 'guest',
            'email' => 'guest@perpustakaan.com',
            'password' => hashPassword('guest123'),
            'full_name' => 'Tamu Perpustakaan',
            'role' => 'staff'
        ]
    ];

        $stmt = $pdo->prepare("
        INSERT INTO admins (username, email, password, full_name, role, status) 
        VALUES (?, ?, ?, ?, ?, 'active')
        ON DUPLICATE KEY UPDATE
            password = VALUES(password),
            full_name = VALUES(full_name),
            role = VALUES(role),
            status = VALUES(status),
            updated_at = CURRENT_TIMESTAMP
    ");

    foreach ($defaultAdmins as $admin) {
        $stmt->execute([
            $admin['username'],
            $admin['email'],
            $admin['password'],
            $admin['full_name'],
            $admin['role']
        ]);
    }

    echo " Admin accounts setup successfully!<br><br>";
    echo "<strong>Default Login Credentials:</strong><br>";
    echo "• Username: <code>admin</code> | Password: <code>admin123</code> (Super Admin)<br>";
    echo "• Username: <code>librarian</code> | Password: <code>librarian123</code> (Admin)<br>";
    echo "• Username: <code>user</code> | Password: <code>user123</code> (Staff)<br>";
    echo "• Username: <code>guest</code> | Password: <code>guest123</code> (Staff)<br><br>";
    echo "<strong> IMPORTANT:</strong> Please change these default passwords after first login!<br><br>";
    echo "<a href='login.php'>Go to Login Page</a>";

} catch(PDOException $e) {
    echo " Error setting up admin accounts: " . $e->getMessage();
}
?>
