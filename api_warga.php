<?php
// Aktifkan error reporting untuk kemudahan debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");

require_once "koneksi.php";

$action = $_GET['action'] ?? '';

// 1. BACA DATA WARGA (Read)
if ($action == 'get') {
    $sql = "SELECT * FROM warga ORDER BY id DESC";
    $result = $conn->query($sql);
    $data = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }
    echo json_encode(["success" => true, "data" => $data]);
    exit;
}

// 2. SIMPAN / UPDATE WARGA (Create & Update)
if ($action == 'save') {
    $id = $_POST['id'] ?? '';
    $blok = $_POST['blok'] ?? '';
    $no_rumah = $_POST['no_rumah'] ?? '';
    $nama_kk = $_POST['nama_kk'] ?? '';
    $raw_status = $_POST['status_hunian'] ?? 'Tetap';
    
    // Normalisasi nilai status agar sesuai ENUM('Tetap', 'Kontrak')
    $status_hunian = (strpos($raw_status, 'Kontrak') !== false) ? 'Kontrak' : 'Tetap';
    
    // Ambil jumlah laki-laki dan perempuan dari form POST
    $jml_laki = (int)($_POST['jml_laki'] ?? $_POST['jumlah_l'] ?? 0);
    $jml_perempuan = (int)($_POST['jml_perempuan'] ?? $_POST['jumlah_p'] ?? 0);

    $no_hp = $_POST['no_hp'] ?? $_POST['nomor_hp'] ?? '';

    if (!empty($id)) {
        // Mode Update (Kolom database: jml_laki dan jml_perempuan)
        $stmt = $conn->prepare("UPDATE warga SET blok=?, no_rumah=?, nama_kk=?, status_hunian=?, jml_laki=?, jml_perempuan=?, no_hp=? WHERE id=?");
        if ($stmt) {
            // "ssssiisi" -> string, string, string, string, int, int, string, int
            $stmt->bind_param("ssssiisi", $blok, $no_rumah, $nama_kk, $status_hunian, $jml_laki, $jml_perempuan, $no_hp, $id);
        }
    } else {
        // Mode Insert (Kolom database: jml_laki dan jml_perempuan)
        $stmt = $conn->prepare("INSERT INTO warga (blok, no_rumah, nama_kk, status_hunian, jml_laki, jml_perempuan, no_hp) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            // "ssssiis" -> string, string, string, string, int, int, string
            $stmt->bind_param("ssssiis", $blok, $no_rumah, $nama_kk, $status_hunian, $jml_laki, $jml_perempuan, $no_hp);
        }
    }

    if ($stmt && $stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Data warga berhasil disimpan"]);
    } else {
        $error_msg = $stmt ? $stmt->error : $conn->error;
        echo json_encode(["success" => false, "message" => "Gagal MySQL: " . $error_msg]);
    }
    exit;
}

// 3. HAPUS WARGA (Delete)
if ($action == 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $conn->prepare("DELETE FROM warga WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $id);
    }

    if ($stmt && $stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Data warga berhasil dihapus"]);
    } else {
        $error_msg = $stmt ? $stmt->error : $conn->error;
        echo json_encode(["success" => false, "message" => "Gagal hapus data: " . $error_msg]);
    }
    exit;
}

echo json_encode(["success" => false, "message" => "Aksi tidak valid"]);
?>