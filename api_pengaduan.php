<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");

// Konfigurasi Database XAMPP
require_once "koneksi.php";

if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Koneksi database gagal"]);
    exit();
}

$action = $_GET['action'] ?? '';

// 1. TAMBAH PENGADUAN BARU (Untuk Warga di index.html)
if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    $nama = $conn->real_escape_string($input['nama_pelapor'] ?? '');
    $blok = $conn->real_escape_string($input['blok'] ?? '');
    $no_rumah = $conn->real_escape_string($input['no_rumah'] ?? '');
    $kategori = $conn->real_escape_string($input['kategori'] ?? 'Lainnya');
    $detail = $conn->real_escape_string($input['detail_pengaduan'] ?? '');

    if (empty($nama) || empty($blok) || empty($no_rumah) || empty($detail)) {
        echo json_encode(["success" => false, "message" => "Harap isi semua kolom wajib!"]);
        exit();
    }

    $sql = "INSERT INTO pengaduan (nama_pelapor, blok, no_rumah, kategori, detail_pengaduan) 
            VALUES ('$nama', '$blok', '$no_rumah', '$kategori', '$detail')";

    if ($conn->query($sql)) {
        echo json_encode(["success" => true, "message" => "Laporan pengaduan berhasil dikirim!"]);
    } else {
        echo json_encode(["success" => false, "message" => "Gagal menyimpan pengaduan."]);
    }
}

// 2. AMBIL SEMUA DATA PENGADUAN (Untuk Admin & Publik)
elseif ($action === 'get') {
    $sql = "SELECT * FROM pengaduan ORDER BY tanggal_lapor DESC";
    $result = $conn->query($sql);
    $data = [];

    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    echo json_encode(["success" => true, "data" => $data]);
}

// 3. UPDATE STATUS PENGADUAN (Khusus Admin)
elseif ($action === 'update_status' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    $id = intval($input['id'] ?? 0);
    $status = $conn->real_escape_string($input['status'] ?? 'Pending');
    $tanggapan = $conn->real_escape_string($input['tanggapan_admin'] ?? '');

    $sql = "UPDATE pengaduan SET status = '$status', tanggapan_admin = '$tanggapan' WHERE id = $id";

    if ($conn->query($sql)) {
        echo json_encode(["success" => true, "message" => "Status pengaduan berhasil diperbarui!"]);
    } else {
        echo json_encode(["success" => false, "message" => "Gagal memperbarui status."]);
    }
}

// 4. HAPUS PENGADUAN (Khusus Admin)
elseif ($action === 'delete') {
    $id = intval($_GET['id'] ?? 0);

    if ($id <= 0) {
        echo json_encode(["success" => false, "message" => "ID pengaduan tidak valid!"]);
        exit();
    }

    $sql = "DELETE FROM pengaduan WHERE id = $id";

    if ($conn->query($sql)) {
        echo json_encode(["success" => true, "message" => "Laporan pengaduan berhasil dihapus!"]);
    } else {
        echo json_encode(["success" => false, "message" => "Gagal menghapus pengaduan."]);
    }
}

$conn->close();
?>