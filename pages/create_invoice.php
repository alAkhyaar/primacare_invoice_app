<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Proses penyimpanan invoice
    $customer_name = cleanInput($_POST['customer_name']);
    $customer_gender = cleanInput($_POST['gender']);
    $customer_address = cleanInput($_POST['customer_address']);
    $invoice_date = cleanInput($_POST['invoice_date']);
    $due_date = cleanInput($_POST['due_date']);
    $descriptions = $_POST['description'];
    $quantities = $_POST['quantity'];
    $unit_prices = $_POST['unit_price'];
    $notes = cleanInput($_POST['notes']);

    try {
        $db->beginTransaction();

        // Insert customer
        $stmt = $db->prepare("INSERT INTO customers (name, gender, address) VALUES (?, ?, ?)");
        $stmt->execute([$customer_name, $customer_gender, $customer_address]);
        $customer_id = $db->lastInsertId();

        // Generate invoice number
        $stmt = $db->query("SELECT COUNT(*) FROM invoices");
        $count = $stmt->fetchColumn();
        $invoice_number = 'PFT' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);

        // Hitung total
        $total_amount = 0;
        foreach ($quantities as $key => $qty) {
            $total_amount += $qty * $unit_prices[$key];
        }

        // Insert invoice
        $stmt = $db->prepare("INSERT INTO invoices (invoice_number, customer_id, invoice_date, due_date, notes, total_amount) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$invoice_number, $customer_id, $invoice_date, $due_date, $notes, $total_amount]);
        $invoice_id = $db->lastInsertId();

        // Insert invoice items
        $stmt = $db->prepare("INSERT INTO invoice_items (invoice_id, description, quantity, unit_price, total) VALUES (?, ?, ?, ?, ?)");
        foreach ($descriptions as $key => $description) {
            $qty = $quantities[$key];
            $price = $unit_prices[$key];
            $total = $qty * $price;
            $stmt->execute([$invoice_id, cleanInput($description), $qty, $price, $total]);
        }

        $db->commit();
        echo "<div class='alert alert-success'>Invoice berhasil dibuat!</div>";
        header("Location: " . BASE_URL . "?page=list_invoice");
        exit;
    } catch (Exception $e) {
        $db->rollBack();
        echo "<div class='alert alert-danger'>Terjadi kesalahan: " . $e->getMessage() . "</div>";
    }
}
?>

<div class="card">
    <div class="card-header">
        <h3>Buat Invoice Baru</h3>
    </div>
    <div class="card-body">
        <form method="POST" action="">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label>Nama Pelanggan</label>
                        <input type="text" name="customer_name" class="form-control" required>
                    </div>
                    <div class="form-group mb-3">
                        <label>Jenis Kelamin</label>
                        <select name="gender" class="form-control" required>
                            <option value="">Pilih Jenis Kelamin</option>
                            <option value="L">Laki-laki</option>
                            <option value="P">Perempuan</option>
                        </select>
                    </div>
                    <div class="form-group mb-3">
                        <label>Alamat</label>
                        <textarea name="customer_address" class="form-control" required></textarea>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label>Tanggal Invoice</label>
                        <input type="date" name="invoice_date" class="form-control" required>
                    </div>
                    <div class="form-group mb-3">
                        <label>Tanggal Jatuh Tempo</label>
                        <input type="date" name="due_date" class="form-control" required>
                    </div>
                </div>
            </div>

            <div class="items-container">
                <div class="item-row row mt-3">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label>Deskripsi</label>
                            <input type="text" name="description[]" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group mb-3">
                            <label>Quantity</label>
                            <input type="number" name="quantity[]" class="form-control quantity" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group mb-3">
                            <label>Harga Satuan</label>
                            <input type="number" name="unit_price[]" class="form-control unit-price" required>
                        </div>
                    </div>
                    <div class="col-md-1">
                        <div class="form-group mb-3">
                            <label>&nbsp;</label>
                            <button type="button" class="btn btn-danger btn-remove-item" style="display:none;">X</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-md-12">
                    <button type="button" class="btn btn-success" id="add-item">+ Tambah Item</button>
                </div>
            </div>

            <div class="form-group mt-3">
                <label>Catatan</label>
                <textarea name="notes" class="form-control"></textarea>
            </div>

            <button type="submit" class="btn btn-primary mt-3">Buat Invoice</button>
        </form>
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