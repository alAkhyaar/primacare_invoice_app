<?php
if (isset($_GET['id'])) {
    $invoice_id = cleanInput($_GET['id']);

    // Ambil data invoice
    $stmt = $db->prepare("
        SELECT i.*, c.name as customer_name, c.gender, c.address as customer_address 
        FROM invoices i 
        JOIN customers c ON i.customer_id = c.id 
        WHERE i.id = ?
    ");
    $stmt->execute([$invoice_id]);
    $invoice = $stmt->fetch(PDO::FETCH_ASSOC);

    // Ambil items invoice
    $stmt = $db->prepare("SELECT * FROM invoice_items WHERE invoice_id = ?");
    $stmt->execute([$invoice_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<div class="invoice-container">
    <div class="print-buttons mb-3">
        <button class="btn btn-primary me-2" onclick="generatePDF()">
            <i class="fas fa-file-pdf"></i> Cetak PDF
        </button>
        <button class="btn btn-success" onclick="generateJPG()">
            <i class="fas fa-file-image"></i> Cetak JPG
        </button>
    </div>
    <div class="row invoice-header">
        <div class="col-md-6">
            <img src="<?php echo BASE_URL; ?>/assets/images/primacare-logo.png" alt="Primacare" class="logo">
        </div>
        <div class="col-md-6 text-end">
            <h1 class="invoice-title">INVOICE</h1>
        </div>
    </div>

    <div class="row invoice-info">
        <div class="col-md-6">
            <div class="billed-to">
                <h5 class="mb-3">BILLED TO:</h5>
                <p class="mb-0">
                    <strong>
                        <?php
                        $title = $invoice['gender'] == 'L' ? 'Tn.' : 'Ny.';
                        echo $title . ' ' . $invoice['customer_name'];
                        ?>
                    </strong><br>
                    <?php echo nl2br($invoice['customer_address']); ?>
                </p>
            </div>
        </div>
        <div class="col-md-6 text-end">
            <div class="invoice-details">
                <p><strong>Invoice No:</strong> <?php echo $invoice['invoice_number']; ?><br>
                    <strong>Invoice Date:</strong> <?php echo date('d F Y', strtotime($invoice['invoice_date'])); ?><br>
                    <strong>Due Date:</strong> <?php echo date('d F Y', strtotime($invoice['due_date'])); ?>
                </p>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-12">
            <table class="table">
                <thead>
                    <tr>
                        <th>Descriptions</th>
                        <th>Quantity</th>
                        <th>Unit Price</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?php echo $item['description']; ?></td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td>Rp <?php echo number_format($item['unit_price'], 0, ',', '.'); ?></td>
                            <td>Rp <?php echo number_format($item['total'], 0, ',', '.'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="3" class="text-end">Total</th>
                        <th class="total-cell">
                            Rp <?php echo number_format($invoice['total_amount'], 0, ',', '.'); ?>
                            <?php if ($invoice['status'] == 'PAID'): ?>
                                <div class="paid-stamp">LUNAS</div>
                            <?php endif; ?>
                        </th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <?php if (!empty($invoice['notes'])): ?>
        <div class="notes-section">
            <strong>Keterangan:</strong><br>
            <?php echo nl2br($invoice['notes']); ?>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-6">
            <!-- Kolom kosong di kiri -->
        </div>
        <div class="col-md-6 text-center">
            <div class="signature-container">
                <p>Fisioterapis</p>
                <img src="<?php echo BASE_URL; ?>/assets/images/ttd-amirah.png" alt="Tanda Tangan" class="signature-img">
                <div>
                    <strong>Amirah Zahidah, Ftr.</strong>
                </div>
            </div>
        </div>
    </div>

    <div class="payment-info">
        <h5>PAYMENT INFORMATION</h5>
        <p>Bank Syariah Indonesia<br>
            4421012000 a/n Amirah Zahidah</p>
    </div>
</div>

<!-- Tambahkan library yang diperlukan -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script src="https://html2canvas.hertzen.com/dist/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>

<script>
    function generatePDF() {
        const printButtons = document.querySelector('.print-buttons');
        printButtons.style.display = 'none';

        const element = document.querySelector('.invoice-container');
        const opt = {
            margin: 0,
            filename: 'invoice-<?php echo $invoice['invoice_number']; ?>.pdf',
            image: {
                type: 'jpeg',
                quality: 1
            },
            html2canvas: {
                scale: 4,
                useCORS: true,
                letterRendering: true,
                scrollY: 0,
                windowWidth: element.offsetWidth,
                windowHeight: element.offsetHeight
            },
            jsPDF: {
                format: [
                    element.offsetWidth * 0.9,
                    element.offsetHeight * 0.9
                ],
                orientation: 'portrait',
                compress: true,
                putOnlyUsedFonts: true,
                precision: 16
            }
        };

        const scrollPos = window.scrollY;
        element.scrollIntoView(true);

        html2pdf().set(opt)
            .from(element)
            .save()
            .then(function() {
                printButtons.style.display = 'block';
                window.scrollTo(0, scrollPos);
            });
    }

    function generateJPG() {
        // Sembunyikan tombol cetak sementara
        const printButtons = document.querySelector('.print-buttons');
        printButtons.style.display = 'none';

        const element = document.querySelector('.invoice-container');

        html2canvas(element, {
            scale: 2,
            logging: true,
            useCORS: true
        }).then(canvas => {
            // Tampilkan kembali tombol cetak
            printButtons.style.display = 'block';

            // Konversi ke JPG dan download
            canvas.toBlob(function(blob) {
                saveAs(blob, 'invoice-<?php echo $invoice['invoice_number']; ?>.jpg');
            }, 'image/jpeg', 0.95);
        });
    }
</script>