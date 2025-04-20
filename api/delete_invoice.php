<?php
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['invoice_id'])) {
    try {
        $db->beginTransaction();

        // Hapus invoice items
        $stmt = $db->prepare("DELETE FROM invoice_items WHERE invoice_id = ?");
        $stmt->execute([$_POST['invoice_id']]);

        // Dapatkan customer_id dari invoice
        $stmt = $db->prepare("SELECT customer_id FROM invoices WHERE id = ?");
        $stmt->execute([$_POST['invoice_id']]);
        $customer_id = $stmt->fetch(PDO::FETCH_ASSOC)['customer_id'];

        // Hapus invoice
        $stmt = $db->prepare("DELETE FROM invoices WHERE id = ?");
        $stmt->execute([$_POST['invoice_id']]);

        // Hapus customer
        $stmt = $db->prepare("DELETE FROM customers WHERE id = ?");
        $stmt->execute([$customer_id]);

        $db->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan saat menghapus invoice']);
    }
}
