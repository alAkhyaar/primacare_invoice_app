<?php
// Ambil semua data invoice
$stmt = $db->query("
    SELECT 
        i.*, 
        c.name as customer_name,
        c.gender,
        c.address as customer_address 
    FROM invoices i 
    JOIN customers c ON i.customer_id = c.id 
    ORDER BY i.created_at DESC
");
$invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3>Daftar Invoice</h3>
        <a href="<?php echo BASE_URL; ?>?page=create_invoice" class="btn btn-primary">Buat Invoice Baru</a>
    </div>
    <div class="card-body">
        <?php
        // Tampilkan pesan sukses
        if (isset($_SESSION['success_message'])) {
            echo "<div class='alert alert-success'>" . $_SESSION['success_message'] . "</div>";
            unset($_SESSION['success_message']);
        }

        // Tampilkan pesan error
        if (isset($_SESSION['error_message'])) {
            echo "<div class='alert alert-danger'>" . $_SESSION['error_message'] . "</div>";
            unset($_SESSION['error_message']);
        }
        ?>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>No. Invoice</th>
                        <th>Pelanggan</th>
                        <th>Tanggal</th>
                        <th>Jatuh Tempo</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($invoices)): ?>
                        <tr>
                            <td colspan="7" class="text-center">Belum ada invoice</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($invoices as $invoice): ?>
                            <tr>
                                <td><?php echo $invoice['invoice_number']; ?></td>
                                <td>
                                    <?php
                                    $title = $invoice['gender'] == 'L' ? 'Tn.' : 'Ny.';
                                    echo $title . ' ' . $invoice['customer_name'];
                                    ?>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($invoice['invoice_date'])); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($invoice['due_date'])); ?></td>
                                <td>Rp <?php echo number_format($invoice['total_amount'], 0, ',', '.'); ?></td>
                                <td>
                                    <?php if ($invoice['status'] == 'PAID'): ?>
                                        <span class="badge bg-success">LUNAS</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning">BELUM LUNAS</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?php echo BASE_URL; ?>?page=view_invoice&id=<?php echo $invoice['id']; ?>"
                                        class="btn btn-sm btn-info">
                                        Lihat
                                    </a>
                                    <a href="<?php echo BASE_URL; ?>?page=edit_invoice&id=<?php echo $invoice['id']; ?>"
                                        class="btn btn-sm btn-warning">
                                        Edit
                                    </a>
                                    <?php if ($invoice['status'] != 'PAID'): ?>
                                        <a href="<?php echo BASE_URL; ?>?page=mark_paid&id=<?php echo $invoice['id']; ?>"
                                            class="btn btn-sm btn-success"
                                            onclick="return confirm('Tandai invoice ini sebagai LUNAS?')">
                                            Tandai Lunas
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>