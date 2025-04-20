<?php
// Jika form disubmit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $db->beginTransaction();

        // Insert data customer
        $stmt = $db->prepare("
            INSERT INTO customers (name, gender, address) 
            VALUES (?, ?, ?)
        ");
        $stmt->execute([
            $_POST['customer_name'],
            $_POST['gender'],
            $_POST['address']
        ]);
        $customer_id = $db->lastInsertId();

        // Generate nomor invoice (format: PFTxxxx)
        $stmt = $db->prepare("
            SELECT MAX(CAST(SUBSTRING(invoice_number, 4) AS UNSIGNED)) as last_number 
            FROM invoices 
            WHERE invoice_number LIKE 'PFT%'
        ");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $last_number = $result['last_number'] ?? 0;
        $invoice_number = 'PFT' . sprintf('%04d', $last_number + 1);

        // Insert data invoice
        $stmt = $db->prepare("
            INSERT INTO invoices (
                invoice_number, customer_id, invoice_date, 
                due_date, total_amount, notes, status
            ) VALUES (?, ?, ?, ?, ?, ?, 'UNPAID')
        ");
        $stmt->execute([
            $invoice_number,
            $customer_id,
            $_POST['invoice_date'],
            $_POST['due_date'],
            $_POST['total_amount'],
            $_POST['notes']
        ]);
        $invoice_id = $db->lastInsertId();

        // Insert items invoice
        $stmt = $db->prepare("
            INSERT INTO invoice_items (
                invoice_id, description, quantity, 
                unit_price, total
            ) VALUES (?, ?, ?, ?, ?)
        ");

        foreach ($_POST['items'] as $item) {
            $total = $item['quantity'] * $item['unit_price'];
            $stmt->execute([
                $invoice_id,
                $item['description'],
                $item['quantity'],
                $item['unit_price'],
                $total
            ]);
        }

        $db->commit();
        header("Location: " . BASE_URL . "/?page=view_invoice&id=" . $invoice_id);
        exit;
    } catch (Exception $e) {
        $db->rollBack();
        $error = "Terjadi kesalahan saat menyimpan data";
    }
}
?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col-md-6">
            <h2>Buat Invoice Baru</h2>
        </div>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST" id="invoiceForm">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Data Pasien</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Nama Pasien</label>
                            <input type="text" name="customer_name" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Jenis Kelamin</label>
                            <select name="gender" class="form-select" required>
                                <option value="">Pilih Jenis Kelamin</option>
                                <option value="L">Laki-laki</option>
                                <option value="P">Perempuan</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="mb-3">
                            <label class="form-label">Alamat</label>
                            <textarea name="address" class="form-control" rows="2" required></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Detail Invoice</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Tanggal Invoice</label>
                            <input type="date" name="invoice_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Jatuh Tempo</label>
                            <input type="date" name="due_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                </div>

                <div id="itemsContainer">
                    <div class="item-row mb-3">
                        <div class="row">
                            <div class="col-md-5">
                                <input type="text" name="items[0][description]" class="form-control" placeholder="Deskripsi" required>
                            </div>
                            <div class="col-md-2">
                                <input type="number" name="items[0][quantity]" class="form-control quantity" placeholder="Jumlah" required>
                            </div>
                            <div class="col-md-3">
                                <input type="number" name="items[0][unit_price]" class="form-control price" placeholder="Harga Satuan" required>
                            </div>
                            <div class="col-md-2">
                                <button type="button" class="btn btn-danger btn-remove-item" style="display:none;">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <button type="button" class="btn btn-success mb-3" id="addItem">
                    <i class="fas fa-plus"></i> Tambah Item
                </button>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Keterangan</label>
                            <textarea name="notes" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Total</label>
                            <input type="number" name="total_amount" id="totalAmount" class="form-control" readonly>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-end">
            <button type="submit" class="btn btn-primary">Simpan Invoice</button>
        </div>
    </form>
</div>

<script>
    let itemCount = 1;

    document.getElementById('addItem').addEventListener('click', function() {
        const container = document.getElementById('itemsContainer');
        const newRow = document.createElement('div');
        newRow.className = 'item-row mb-3';
        newRow.innerHTML = `
        <div class="row">
            <div class="col-md-5">
                <input type="text" name="items[${itemCount}][description]" class="form-control" placeholder="Deskripsi" required>
            </div>
            <div class="col-md-2">
                <input type="number" name="items[${itemCount}][quantity]" class="form-control quantity" placeholder="Jumlah" required>
            </div>
            <div class="col-md-3">
                <input type="number" name="items[${itemCount}][unit_price]" class="form-control price" placeholder="Harga Satuan" required>
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-danger btn-remove-item">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    `;
        container.appendChild(newRow);
        itemCount++;
        updateRemoveButtons();
        attachEventListeners();
    });

    document.addEventListener('click', function(e) {
        if (e.target.closest('.btn-remove-item')) {
            e.target.closest('.item-row').remove();
            updateRemoveButtons();
            calculateTotal();
        }
    });

    function updateRemoveButtons() {
        const rows = document.querySelectorAll('.item-row');
        rows.forEach((row, index) => {
            const btn = row.querySelector('.btn-remove-item');
            if (rows.length === 1) {
                btn.style.display = 'none';
            } else {
                btn.style.display = 'block';
            }
        });
    }

    function attachEventListeners() {
        document.querySelectorAll('.quantity, .price').forEach(input => {
            input.addEventListener('input', calculateTotal);
        });
    }

    function calculateTotal() {
        let total = 0;
        document.querySelectorAll('.item-row').forEach(row => {
            const quantity = parseFloat(row.querySelector('.quantity').value) || 0;
            const price = parseFloat(row.querySelector('.price').value) || 0;
            total += quantity * price;
        });
        document.getElementById('totalAmount').value = total;
    }

    attachEventListeners();
</script>