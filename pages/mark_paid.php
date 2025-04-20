<?php
if (isset($_GET['id'])) {
    $invoice_id = cleanInput($_GET['id']);

    try {
        // Update status invoice menjadi PAID
        $stmt = $db->prepare("UPDATE invoices SET status = 'PAID' WHERE id = ?");
        $stmt->execute([$invoice_id]);

        // Tampilkan pesan sukses dan redirect
        $_SESSION['success_message'] = "Invoice berhasil ditandai sebagai LUNAS";
        header("Location: " . BASE_URL . "?page=list_invoice");
        exit;
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Terjadi kesalahan: " . $e->getMessage();
        header("Location: " . BASE_URL . "?page=list_invoice");
        exit;
    }
} else {
    // Jika tidak ada ID, kembali ke halaman list
    header("Location: " . BASE_URL . "?page=list_invoice");
    exit;
}
