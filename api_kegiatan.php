<?php
header('Content-Type: application/json');
require_once "koneksi.php";
if ($conn->connect_error) {
    die(json_encode(["success" => false, "message" => "Koneksi database gagal"]));
}

$action = $_GET['action'] ?? '';

// 1. GET DATA KEGIATAN
if ($action === 'get') {
    $sql = "SELECT * FROM kegiatan ORDER BY created_at DESC";
    $result = $conn->query($sql);
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    echo json_encode(["success" => true, "data" => $data]);
    exit();
}

// 2. SIMPAN / EDIT KEGIATAN (ADMIN)
if ($action === 'save') {
    $id = $_POST['id'] ?? '';
    $judul = $_POST['judul'] ?? '';
    $kategori = $_POST['kategori'] ?? '';
    $tanggal = $_POST['tanggal_kegiatan'] ?? '';
    $lokasi = $_POST['lokasi'] ?? '';
    $caption = $_POST['caption'] ?? '';

    $foto_name = $_POST['existing_foto'] ?? '';

    // Handle Upload Foto
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['foto']['tmp_name'];
        $fileName = $_FILES['foto']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
        if (in_array($fileExtension, $allowedExts)) {
            $newFileName = time() . '_' . uniqid() . '.' . $fileExtension;
            $uploadFileDir = './uploads/';
            
            if (!is_dir($uploadFileDir)) {
                mkdir($uploadFileDir, 0755, true);
            }
            
            $dest_path = $uploadFileDir . $newFileName;
            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                $foto_name = $newFileName;
            }
        }
    }

    if (empty($id)) {
        // Insert Baru
        $stmt = $conn->prepare("INSERT INTO kegiatan (judul, kategori, tanggal_kegiatan, lokasi, foto, caption) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $judul, $kategori, $tanggal, $lokasi, $foto_name, $caption);
    } else {
        // Update Data
        $stmt = $conn->prepare("UPDATE kegiatan SET judul=?, kategori=?, tanggal_kegiatan=?, lokasi=?, foto=?, caption=? WHERE id=?");
        $stmt->bind_param("ssssssi", $judul, $kategori, $tanggal, $lokasi, $foto_name, $caption, $id);
    }

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Kegiatan berhasil disimpan!"]);
    } else {
        echo json_encode(["success" => false, "message" => "Gagal menyimpan kegiatan."]);
    }
    exit();
}

// 3. HAPUS KEGIATAN (ADMIN)
if ($action === 'delete') {
    $id = $_GET['id'] ?? '';
    if (!empty($id)) {
        $stmt = $conn->prepare("DELETE FROM kegiatan WHERE id=?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Kegiatan berhasil dihapus!"]);
            exit();
        }
    }
    echo json_encode(["success" => false, "message" => "Gagal menghapus kegiatan."]);
    exit();
}