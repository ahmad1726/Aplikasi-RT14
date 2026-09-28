<?php
header("Content-Type: application/json");
include 'koneksi.php'; 

$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($action == 'login') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $username = trim($input['username'] ?? '');
    $password = trim($input['password'] ?? '');

    if (empty($username) || empty($password)) {
        echo json_encode(["success" => false, "message" => "Username dan password wajib diisi!"]);
        exit;
    }

    // Query cek admin
    $stmt = $conn->prepare("SELECT * FROM admin WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        // Cek password (mendukung plain text atau password_verify jika di-hash)
        if ($password === $row['password'] || password_verify($password, $row['password'])) {
            echo json_encode(["success" => true, "message" => "Login berhasil!"]);
        } else {
            echo json_encode(["success" => false, "message" => "Password salah!"]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Username tidak ditemukan!"]);
    }
    exit;
}

echo json_encode(["success" => false, "message" => "Aksi tidak valid."]);
?>