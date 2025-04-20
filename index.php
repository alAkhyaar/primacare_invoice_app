<?php
session_start();
require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/functions.php';

$page = isset($_GET['page']) ? $_GET['page'] : 'home';

// Cek login
if (!isset($_SESSION['user_id']) && $page !== 'login') {
    header("Location: " . BASE_URL . "?page=login");
    exit;
}

// Jika mengakses halaman login tapi sudah login
if (isset($_SESSION['user_id']) && $page === 'login') {
    header("Location: " . BASE_URL);
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aplikasi Invoice</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</head>

<body>
    <?php if ($page !== 'login'): ?>
        <?php include 'includes/navbar.php'; ?>
    <?php endif; ?>

    <div class="container">
        <?php
        switch ($page) {
            case 'add_invoice':
                include 'pages/add_invoice.php';
                break;
            case 'edit_invoice':
                include 'pages/edit_invoice.php';
                break;
            case 'view_invoice':
                include 'pages/view_invoice.php';
                break;
            case 'login':
                include 'pages/login.php';
                break;
            case 'logout':
                include 'pages/logout.php';
                break;
            default:
                include 'pages/home.php';
                break;
        }
        ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>