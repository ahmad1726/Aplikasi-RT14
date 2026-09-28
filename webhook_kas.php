<?php
$json = file_get_contents('php://input');
$data = json_decode($json, true);

$order_id = $data['order_id'];
$status = $data['transaction_status']; 
$gross_amount = $data['gross_amount'];

if ($status === 'settlement' || $status === 'paid') {
    $stmt = $pdo->prepare("INSERT INTO kas_rt (reference_id, keterangan, kategori, jenis, jumlah, metode_pembayaran, status) 
        VALUES (:ref, 'Iuran Warga (Otomatis)', 'Iuran Warga', 'masuk', :jumlah, 'QRIS/E-Wallet', 'success')
        ON DUPLICATE KEY UPDATE status = 'success'");
    $stmt->execute([
        ':ref' => $order_id,
        ':jumlah' => $gross_amount
    ]);
}

http_response_code(200);
echo json_encode(['status' => 'ok']);
?>