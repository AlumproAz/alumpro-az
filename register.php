<?php
$pageTitle = 'Qeydiyyat';
require_once 'config/config.php';
require_once 'includes/Database.php';
require_once 'includes/Auth.php';

$auth = new Auth();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $fullName = $_POST['full_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    
    // Validation
    if (strlen($username) < 3) {
        $error = 'İstifadəçi adı minimum 3 simvol olmalıdır';
    } elseif (strlen($password) < 6) {
        $error = 'Şifrə minimum 6 simvol olmalıdır';
    } elseif ($password !== $confirmPassword) {
        $error = 'Şifrələr uyğun gəlmir';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Düzgün e-poçt ünvanı daxil edin';
    } elseif (!preg_match('/^\+994[0-9]{9}$/', $phone)) {
        $error = 'Telefon nömrəsi +994XXXXXXXXX formatında olmalıdır';
    } else {
        $result = $auth->register($username, $password, $fullName, $email, $phone, 'customer');
        
        if ($result['success']) {
            // Redirect to verification page
            header('Location: verify.php?user_id=' . $result['user_id']);
            exit;
        } else {
            $error = $result['message'];
        }
    }
}

require_once 'includes/header.php';
?>

<div class="auth-page">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card auth-card shadow">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <img src="assets/img/logo.png" alt="Logo" height="60" class="mb-3">
                            <h3 class="gradient-text">Qeydiyyat</h3>
                            <p class="text-muted">Yeni hesab yaradın</p>
                        </div>
                        
                        <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="" id="registerForm">
                            <div class="mb-3">
                                <label for="full_name" class="form-label">Ad və Soyad</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-person-badge"></i></span>
                                    <input type="text" class="form-control" id="full_name" name="full_name" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="username" class="form-label">İstifadəçi Adı</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                                    <input type="text" class="form-control" id="username" name="username" required minlength="3">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">E-poçt</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="phone" class="form-label">Telefon</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                                    <input type="tel" class="form-control" id="phone" name="phone" placeholder="+994501234567" required pattern="^\+994[0-9]{9}$">
                                </div>
                                <small class="text-muted">Format: +994XXXXXXXXX</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Şifrə</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                    <input type="password" class="form-control" id="password" name="password" required minlength="6">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Şifrəni Təsdiqləyin</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="6">
                                </div>
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="terms" required>
                                <label class="form-check-label" for="terms">
                                    <a href="terms.php" target="_blank">İstifadə şərtləri</a> ilə razıyam
                                </label>
                            </div>
                            
                            <div class="d-grid mb-3">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-person-plus"></i> Qeydiyyat
                                </button>
                            </div>
                        </form>
                        
                        <hr class="my-4">
                        
                        <div class="text-center">
                            <p class="mb-0">Artıq hesabınız var?</p>
                            <a href="login.php" class="btn btn-outline-primary mt-2">
                                <i class="bi bi-box-arrow-in-right"></i> Daxil Olun
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('registerForm').addEventListener('submit', function(e) {
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    
    if (password !== confirmPassword) {
        e.preventDefault();
        alert('Şifrələr uyğun gəlmir!');
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>