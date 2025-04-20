<?php
require_once '../config/config.php';
require_once '../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $invoice_id = isset($data['invoice_id']) ? (int)$data['invoice_id'] : 0;

    if ($invoice_id > 0) {
        try {
            $stmt = $db->prepare("UPDATE invoices SET status = 'PAID' WHERE id = ?");
            $result = $stmt->execute([$invoice_id]);

            if ($result) {
                echo json_encode(['success' => true]);
                exit;
            }
        } catch (PDOException $e) {
            // Log error jika diperlukan
        }
    }
}

echo json_encode(['success' => false]);
