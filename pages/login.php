<?php
// Redirect jika sudah login
if (isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL);
    exit;
}

// Proses login
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = cleanInput($_POST['username']);
    $password = $_POST['password'];

    $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Debugging
    if ($user) {
        echo "User ditemukan<br>";
        echo "Password yang diinput: " . $password . "<br>";
        echo "Hash di database: " . $user['password'] . "<br>";
        echo "Hasil verify: " . (password_verify($password, $user['password']) ? 'true' : 'false') . "<br>";
    } else {
        echo "User tidak ditemukan<br>";
    }

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        header("Location: " . BASE_URL);
        exit;
    } else {
        $error = "Username atau password salah!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Aplikasi Invoice</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-4">
                <div class="text-center mb-4">
                    <img src="<?php echo BASE_URL; ?>/assets/images/primacare-logo.png" alt="Primacare" class="login-logo">
                    <p class="text-muted">by Amirah Zahidah</p>
                </div>

                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title text-center mb-4">Login</h5>

                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="mb-3">
                                <label class="form-label">Username</label>
                                <input type="text" name="username" class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">Login</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>