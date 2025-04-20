<?php
// Ambil daftar invoice dengan informasi customer
$stmt = $db->prepare("
    SELECT i.*, c.name as customer_name, c.gender 
    FROM invoices i 
    JOIN customers c ON i.customer_id = c.id 
    ORDER BY i.invoice_date DESC
");
$stmt->execute();
$invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col-md-6">
            <h2>Selamat Datang di Primacare</h2>

        </div>
        <div class="col-md-6 text-end">
            <a href="<?php echo BASE_URL; ?>/?page=add_invoice" class="btn btn-primary">
                <i class="fas fa-plus"></i> Buat Invoice Baru
            </a>
        </div>
    </div>

    <!-- Tabel Daftar Invoice -->
    <div class="card">
        <div class="card-header bg-white">
            <h5 class="card-title mb-0">Daftar Invoice</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>No. Invoice</th>
                            <th>Tanggal</th>
                            <th>Nama Pasien</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($invoices as $invoice): ?>
                            <tr>
                                <td><?php echo $invoice['invoice_number']; ?></td>
                                <td><?php echo date('d/m/Y', strtotime($invoice['invoice_date'])); ?></td>
                                <td>
                                    <?php
                                    $title = $invoice['gender'] == 'L' ? 'Tn.' : 'Ny.';
                                    echo $title . ' ' . $invoice['customer_name'];
                                    ?>
                                </td>
                                <td>Rp <?php echo number_format($invoice['total_amount'], 0, ',', '.'); ?></td>
                                <td>
                                    <?php if ($invoice['status'] == 'PAID'): ?>
                                        <span class="badge bg-success">LUNAS</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning">BELUM LUNAS</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="<?php echo BASE_URL; ?>/?page=view_invoice&id=<?php echo $invoice['id']; ?>"
                                            class="btn btn-sm btn-info">
                                            Lihat
                                        </a>
                                        <a href="<?php echo BASE_URL; ?>/?page=edit_invoice&id=<?php echo $invoice['id']; ?>"
                                            class="btn btn-sm btn-warning">
                                            Ubah
                                        </a>
                                        <button class="btn btn-sm btn-danger btn-delete" data-id="<?php echo $invoice['id']; ?>">
                                            Hapus
                                        </button>
                                        <?php if ($invoice['status'] != 'PAID'): ?>
                                            <button onclick="markAsPaid(<?php echo $invoice['id']; ?>)"
                                                class="btn btn-sm btn-success">
                                                Lunas
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Konfirmasi Hapus</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Apakah Anda yakin ingin menghapus invoice ini?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">Hapus</button>
            </div>
        </div>
    </div>
</div>

<script>
    function markAsPaid(invoiceId) {
        if (confirm('Apakah Anda yakin ingin menandai invoice ini sebagai LUNAS?')) {
            fetch('<?php echo BASE_URL; ?>/api/mark_as_paid.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        invoice_id: invoiceId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Gagal mengubah status invoice');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Terjadi kesalahan');
                });
        }
    }

    // Inisialisasi modal
    const deleteModalElement = document.getElementById('deleteModal');
    const deleteModal = new bootstrap.Modal(deleteModalElement);
    let invoiceIdToDelete = null;

    // Event listener untuk tombol hapus
    document.querySelectorAll('.btn-delete').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            invoiceIdToDelete = this.dataset.id;
            deleteModal.show();
        });
    });

    // Event listener untuk konfirmasi hapus
    document.getElementById('confirmDelete').addEventListener('click', function() {
        if (invoiceIdToDelete) {
            const formData = new FormData();
            formData.append('invoice_id', invoiceIdToDelete);

            fetch('<?php echo BASE_URL; ?>/api/delete_invoice.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert('Gagal menghapus invoice: ' + (data.message || 'Terjadi kesalahan'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Terjadi kesalahan saat menghapus invoice');
                })
                .finally(() => {
                    deleteModal.hide();
                });
        }
    });
</script>

<style>
    .table th {
        background-color: #f8f9fa;
        border-bottom: 2px solid #dee2e6;
    }

    .badge {
        padding: 6px 10px;
        font-weight: 500;
    }

    .btn-group .btn {
        padding: 4px 8px;
        margin: 0 2px;
    }

    .card {
        border-radius: 10px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }

    .card-header {
        border-bottom: 1px solid #eee;
        padding: 15px 20px;
    }

    .card-body {
        padding: 20px;
    }
</style>