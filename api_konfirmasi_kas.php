<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'koneksi.php';

if (!isset($pdo)) {
    header("HTTP/1.1 500 Internal Server Error");
    echo json_encode(["success" => false, "message" => "Variabel \$pdo tidak ditemukan di koneksi.php"]);
    exit;
}

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($action === 'get') {
    try {
        $stmtSaldo = $pdo->query("SELECT 
            COALESCE(SUM(CASE WHEN jenis = 'masuk' THEN jumlah ELSE 0 END), 0) - 
            COALESCE(SUM(CASE WHEN jenis = 'keluar' THEN jumlah ELSE 0 END), 0) AS total_saldo 
            FROM kas_rt WHERE status IN ('Selesai', 'selesai', 'Valid', 'valid')");
        $saldo = $stmtSaldo->fetchColumn() ?? 0;

        $stmtTx = $pdo->query("SELECT * FROM kas_rt ORDER BY tanggal DESC");
        $transaksi = $stmtTx->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            "success" => true,
            "saldo" => $saldo,
            "data" => $transaksi,
            "transaksi" => $transaksi
        ]);
    } catch (Exception $e) {
        echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
    }
    exit;
}

if ($action === 'konfirmasi') {
    try {
        $nama_kk  = $_POST['nama'] ?? '';
        $blok     = $_POST['blok'] ?? '';
        $kategori = $_POST['kategori'] ?? 'Iuran Kas';
        
        $raw_nominal = $_POST['nominal'] ?? ($_POST['jumlah'] ?? 0);
        $nominal  = (int) preg_replace('/[^0-9]/', '', $raw_nominal);
        
        $bukti_file = '';

        if (isset($_FILES['bukti']) && $_FILES['bukti']['error'] === UPLOAD_ERR_OK) {
            $target_dir = "uploads/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            $file_ext = strtolower(pathinfo($_FILES["bukti"]["name"], PATHINFO_EXTENSION));
            $allowed_exts = ['jpg', 'jpeg', 'png', 'pdf'];
            
            if (in_array($file_ext, $allowed_exts)) {
                $bukti_file = time() . '_' . uniqid() . '.' . $file_ext;
                move_uploaded_file($_FILES["bukti"]["tmp_name"], $target_dir . $bukti_file);
            }
        }

        $keterangan = "Konfirmasi kas dari: " . $nama_kk . " (Blok/No: " . $blok . ")";
        if (!empty($bukti_file)) {
            $keterangan .= " | Bukti: " . $bukti_file;
        }

        $sql = "INSERT INTO kas_rt (reference_id, tanggal, keterangan, kategori, jenis, jumlah, metode_pembayaran, status) 
                VALUES (?, NOW(), ?, ?, 'masuk', ?, 'Transfer', 'pending')";
        
        $stmt = $pdo->prepare($sql);
        $reference_id = 'TRX-' . time();
        
        $stmt->execute([$reference_id, $keterangan, $kategori, $nominal]);

        echo json_encode(["success" => true, "message" => "Konfirmasi berhasil dikirim!"]);

    } catch (Exception $e) {
        echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
    }
    exit;
}

if ($action === 'update_status') {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $id     = $input['id'] ?? ($_POST['id'] ?? null);
        $status = $input['status'] ?? ($_POST['status'] ?? '');

        if (!$id || empty($status)) {
            echo json_encode(["success" => false, "message" => "Data ID atau status tidak valid."]);
            exit;
        }

        if ($status === 'Valid') {
            $sql = "UPDATE kas_rt SET status = 'Valid' WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $berhasil = $stmt->execute([$id]);

            if ($berhasil) {
                echo json_encode(["success" => true, "message" => "Konfirmasi kas berhasil divalidasi dan dicatat ke sistem!"]);
            } else {
                echo json_encode(["success" => false, "message" => "Gagal mengeksekusi query database."]);
            }
        } else {
            $sql = "UPDATE kas_rt SET status = 'Ditolak' WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $berhasil = $stmt->execute([$id]);
            
            if ($berhasil) {
                echo json_encode(["success" => true, "message" => "Konfirmasi kas telah ditolak."]);
            } else {
                echo json_encode(["success" => false, "message" => "Gagal mengeksekusi query database."]);
            }
        }

    } catch (Exception $e) {
        echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
    }
    exit;
}

if ($action === 'delete' && isset($_GET['id'])) {
    try {
        $id = $_GET['id'];
        
        // Ambil nama file bukti untuk dihapus dari server jika ada
        $stmtCek = $pdo->prepare("SELECT keterangan FROM kas_rt WHERE id = ?");
        $stmtCek->execute([$id]);
        $row = $stmtCek->fetch(PDO::FETCH_ASSOC);
        
        if ($row && strpos($row['keterangan'], '| Bukti:') !== false) {
            $parts = explode('| Bukti:', $row['keterangan']);
            $namaFile = trim($parts[1] ?? '');
            if (!empty($namaFile) && file_exists("uploads/" . $namaFile)) {
                @unlink("uploads/" . $namaFile);
            }
        }

        $sql = "DELETE FROM kas_rt WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        
        echo json_encode(["success" => true, "message" => "Data berhasil dihapus dari sistem"]);
    } catch (Exception $e) {
        echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
    }
    exit;
}
?>