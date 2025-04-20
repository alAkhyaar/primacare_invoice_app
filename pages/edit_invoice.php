<?php
if (isset($_GET['id'])) {
    $invoice_id = cleanInput($_GET['id']);

    // Ambil data invoice yang akan diedit
    $stmt = $db->prepare("
        SELECT i.*, c.name as customer_name, c.address as customer_address, c.gender 
        FROM invoices i 
        JOIN customers c ON i.customer_id = c.id 
        WHERE i.id = ?
    ");
    $stmt->execute([$invoice_id]);
    $invoice = $stmt->fetch(PDO::FETCH_ASSOC);

    // Ambil item invoice
    $stmt = $db->prepare("SELECT * FROM invoice_items WHERE invoice_id = ?");
    $stmt->execute([$invoice_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Proses form update
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        try {
            $db->beginTransaction();

            // Update data customer
            $stmt = $db->prepare("UPDATE customers SET name = ?, gender = ?, address = ? WHERE id = ?");
            $stmt->execute([
                cleanInput($_POST['customer_name']),
                cleanInput($_POST['gender']),
                cleanInput($_POST['customer_address']),
                $invoice['customer_id']
            ]);

            // Update data invoice
            $stmt = $db->prepare("
                UPDATE invoices 
                SET invoice_date = ?, due_date = ?, notes = ?
                WHERE id = ?
            ");
            $stmt->execute([
                cleanInput($_POST['invoice_date']),
                cleanInput($_POST['due_date']),
                cleanInput($_POST['notes']),
                $invoice_id
            ]);

            // Hapus item lama
            $stmt = $db->prepare("DELETE FROM invoice_items WHERE invoice_id = ?");
            $stmt->execute([$invoice_id]);

            // Insert item baru
            $total_amount = 0;
            $stmt = $db->prepare("INSERT INTO invoice_items (invoice_id, description, quantity, unit_price, total) VALUES (?, ?, ?, ?, ?)");

            foreach ($_POST['description'] as $key => $description) {
                $qty = cleanInput($_POST['quantity'][$key]);
                $price = cleanInput($_POST['unit_price'][$key]);
                $total = $qty * $price;
                $total_amount += $total;

                $stmt->execute([
                    $invoice_id,
                    cleanInput($description),
                    $qty,
                    $price,
                    $total
                ]);
            }

            // Update total di invoice
            $stmt = $db->prepare("UPDATE invoices SET total_amount = ? WHERE id = ?");
            $stmt->execute([$total_amount, $invoice_id]);

            $db->commit();
            echo "<div class='alert alert-success'>Invoice berhasil diperbarui!</div>";

            // Refresh data
            header("Location: " . BASE_URL . "?page=list_invoice");
            exit;
        } catch (Exception $e) {
            $db->rollBack();
            echo "<div class='alert alert-danger'>Terjadi kesalahan: " . $e->getMessage() . "</div>";
        }
    }
}
?>

<div class="card">
    <div class="card-header">
        <h3>Edit Invoice</h3>
    </div>
    <div class="card-body">
        <?php if (isset($invoice)): ?>
            <form method="POST" action="">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label>Nama Pelanggan</label>
                            <input type="text" name="customer_name" class="form-control"
                                value="<?php echo $invoice['customer_name']; ?>" required>
                        </div>
                        <div class="form-group mb-3">
                            <label>Jenis Kelamin</label>
                            <select name="gender" class="form-control" required>
                                <option value="">Pilih Jenis Kelamin</option>
                                <option value="L" <?php echo $invoice['gender'] == 'L' ? 'selected' : ''; ?>>Laki-laki</option>
                                <option value="P" <?php echo $invoice['gender'] == 'P' ? 'selected' : ''; ?>>Perempuan</option>
                            </select>
                        </div>
                        <div class="form-group mb-3">
                            <label>Alamat</label>
                            <textarea name="customer_address" class="form-control" required><?php echo $invoice['customer_address']; ?></textarea>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label>No. Invoice</label>
                            <input type="text" class="form-control" value="<?php echo $invoice['invoice_number']; ?>" readonly>
                        </div>
                        <div class="form-group mb-3">
                            <label>Tanggal Invoice</label>
                            <input type="date" name="invoice_date" class="form-control"
                                value="<?php echo $invoice['invoice_date']; ?>" required>
                        </div>
                        <div class="form-group mb-3">
                            <label>Tanggal Jatuh Tempo</label>
                            <input type="date" name="due_date" class="form-control"
                                value="<?php echo $invoice['due_date']; ?>" required>
                        </div>
                    </div>
                </div>

                <div class="items-container">
                    <?php foreach ($items as $item): ?>
                        <div class="item-row row mt-3">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label>Deskripsi</label>
                                    <input type="text" name="description[]" class="form-control"
                                        value="<?php echo $item['description']; ?>" required>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group mb-3">
                                    <label>Quantity</label>
                                    <input type="number" name="quantity[]" class="form-control quantity"
                                        value="<?php echo $item['quantity']; ?>" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group mb-3">
                                    <label>Harga Satuan</label>
                                    <input type="number" name="unit_price[]" class="form-control unit-price"
                                        value="<?php echo $item['unit_price']; ?>" required>
                                </div>
                            </div>
                            <div class="col-md-1">
                                <div class="form-group mb-3">
                                    <label>&nbsp;</label>
                                    <button type="button" class="btn btn-danger btn-remove-item" <?php echo count($items) === 1 ? 'style="display:none;"' : ''; ?>>X</button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="row mt-3">
                    <div class="col-md-12">
                        <button type="button" class="btn btn-success" id="add-item">+ Tambah Item</button>
                    </div>
                </div>

                <div class="form-group mb-3">
                    <label>Catatan</label>
                    <textarea name="notes" class="form-control"><?php echo $invoice['notes']; ?></textarea>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Update Invoice</button>
                    <a href="<?php echo BASE_URL; ?>?page=list_invoice" class="btn btn-secondary">Kembali</a>
                </div>
            </form>
        <?php else: ?>
            <div class="alert alert-danger">Invoice tidak ditemukan.</div>
        <?php endif; ?>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const container = document.querySelector('.items-container');
        const addButton = document.querySelector('#add-item');

        addButton.addEventListener('click', function() {
            const newRow = container.querySelector('.item-row').cloneNode(true);
            newRow.querySelectorAll('input').forEach(input => input.value = '');
            newRow.querySelector('.btn-remove-item').style.display = 'block';
            container.appendChild(newRow);
            updateRemoveButtons();
        });

        container.addEventListener('click', function(e) {
            if (e.target.classList.contains('btn-remove-item')) {
                if (container.querySelectorAll('.item-row').length > 1) {
                    e.target.closest('.item-row').remove();
                    updateRemoveButtons();
                }
            }
        });

        function updateRemoveButtons() {
            const rows = container.querySelectorAll('.item-row');
            rows.forEach((row, index) => {
                const btn = row.querySelector('.btn-remove-item');
                if (rows.length === 1) {
                    btn.style.display = 'none';
                } else {
                    btn.style.display = 'block';
                }
            });
        }
    });
</script>