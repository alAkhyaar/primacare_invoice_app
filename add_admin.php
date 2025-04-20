<?php
require_once 'config/database.php';

try {
    // Buat tabel users jika belum ada
    $db->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT PRIMARY KEY AUTO_INCREMENT,
            username VARCHAR(50) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            name VARCHAR(100) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // Hash password 'admin123'
    $password = password_hash('admin123', PASSWORD_DEFAULT);

    // Hapus user admin yang lama (jika ada)
    $stmt = $db->prepare("DELETE FROM users WHERE username = 'admin'");
    $stmt->execute();

    // Tambah user admin baru
    $stmt = $db->prepare("INSERT INTO users (username, password, name) VALUES (?, ?, ?)");
    $stmt->execute(['admin', $password, 'Amirah Zahidah']);

    echo "Admin berhasil ditambahkan!<br>";
    echo "Username: admin<br>";
    echo "Password: admin123<br>";
    echo "Hash Password: " . $password; // Untuk verifikasi

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
