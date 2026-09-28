<?php
// Aktifkan error reporting untuk debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");

require_once "koneksi.php";

$action = $_GET['action'] ?? '';

// 1. AMBIL DATA IURAN DIJOIN DENGAN TABEL WARGA
if ($action == 'get') {
    $bulan = $_GET['bulan'] ?? date('m');
    $tahun = $_GET['tahun'] ?? date('Y');

    // Mengambil semua data warga lalu di-LEFT JOIN dengan data iuran pada bulan & tahun tersebut
    $sql = "SELECT 
                w.id AS warga_id, 
                w.blok, 
                w.no_rumah, 
                w.nama_kk, 
                i.id AS iuran_id, 
                i.bulan, 
                i.tahun, 
                i.nominal, 
                i.tanggal_bayar, 
                i.keterangan 
            FROM warga w
            LEFT JOIN iuran i ON w.id = i.warga_id AND i.bulan = ? AND i.tahun = ?
            ORDER BY w.blok ASC, w.no_rumah ASC";

    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("ss", $bulan, $tahun);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        echo json_encode(["success" => true, "data" => $data]);
    } else {
        echo json_encode(["success" => false, "message" => "Gagal Query: " . $conn->error]);
    }
    exit;
}

// 2. SIMPAN / BAYAR IURAN (Create & Update)
if ($action == 'save') {
    $warga_id = $_POST['warga_id'] ?? '';
    $bulan = $_POST['bulan'] ?? date('m');
    $tahun = $_POST['tahun'] ?? date('Y');
    $nominal = $_POST['nominal'] ?? 0;
    $tanggal_bayar = $_POST['tanggal_bayar'] ?? date('Y-m-d');
    $keterangan = $_POST['keterangan'] ?? 'Lunas';

    if (empty($warga_id) || empty($nominal)) {
        echo json_encode(["success" => false, "message" => "Warga dan nominal wajib diisi!"]);
        exit;
    }

    // Cek apakah data iuran untuk warga, bulan, dan tahun tersebut sudah ada
    $check = $conn->prepare("SELECT id FROM iuran WHERE warga_id = ? AND bulan = ? AND tahun = ?");
    $check->bind_param("iss", $warga_id, $bulan, $tahun);
    $check->execute();
    $existing = $check->get_result()->fetch_assoc();

    if ($existing) {
        // Update jika sudah ada
        $stmt = $conn->prepare("UPDATE iuran SET nominal = ?, tanggal_bayar = ?, keterangan = ? WHERE id = ?");
        $stmt->bind_param("dssi", $nominal, $tanggal_bayar, $keterangan, $existing['id']);
    } else {
        // Insert baru jika belum ada
        $stmt = $conn->prepare("INSERT INTO iuran (warga_id, bulan, tahun, nominal, tanggal_bayar, keterangan) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issdss", $warga_id, $bulan, $tahun, $nominal, $tanggal_bayar, $keterangan);
    }

    if ($stmt && $stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Data iuran berhasil disimpan"]);
    } else {
        echo json_encode(["success" => false, "message" => "Gagal menyimpan iuran: " . $conn->error]);
    }
    exit;
}

echo json_encode(["success" => false, "message" => "Aksi tidak valid"]);
?>