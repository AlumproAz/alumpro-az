<?php
$pageTitle = 'Giriş';
require_once 'config/config.php';
require_once 'includes/Database.php';
require_once 'includes/Auth.php';

$auth = new Auth();
$error = '';
$success = '';

// If already logged in, redirect to appropriate dashboard
if ($auth->isLoggedIn()) {
    $user = $auth->getCurrentUser();
    switch ($user['role']) {
        case 'admin':
            header('Location: admin/index.php');
            break;
        case 'sales':
            header('Location: sales/index.php');
            break;
        case 'customer':
            header('Location: customer/index.php');
            break;
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $result = $auth->login($username, $password);
    
    if ($result['success']) {
        switch ($result['user']['role']) {
            case 'admin':
                header('Location: admin/index.php');
                break;
            case 'sales':
                header('Location: sales/index.php');
                break;
            case 'customer':
                header('Location: customer/index.php');
                break;
            default:
                header('Location: home.php');
        }
        exit;
    } else {
        $error = $result['message'];
        if (isset($result['user_id'])) {
            // Account not verified, redirect to verification
            header('Location: verify.php?user_id=' . $result['user_id']);
            exit;
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
                            <h3 class="gradient-text">Daxil Olun</h3>
                            <p class="text-muted">Hesabınıza daxil olmaq üçün məlumatlarınızı daxil edin</p>
                        </div>
                        
                        <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="username" class="form-label">İstifadəçi Adı</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                                    <input type="text" class="form-control" id="username" name="username" required autofocus>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Şifrə</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword()">
                                        <i class="bi bi-eye" id="toggleIcon"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="remember" name="remember">
                                <label class="form-check-label" for="remember">
                                    Məni xatırla
                                </label>
                            </div>
                            
                            <div class="d-grid mb-3">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-box-arrow-in-right"></i> Daxil Ol
                                </button>
                            </div>
                            
                            <div class="text-center">
                                <a href="forgot-password.php" class="text-decoration-none">Şifrəni unutmusunuz?</a>
                            </div>
                        </form>
                        
                        <hr class="my-4">
                        
                        <div class="text-center">
                            <p class="mb-0">Hesabınız yoxdur?</p>
                            <a href="register.php" class="btn btn-outline-primary mt-2">
                                <i class="bi bi-person-plus"></i> Qeydiyyatdan Keçin
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function togglePassword() {
    const passwordInput = document.getElementById('password');
    const toggleIcon = document.getElementById('toggleIcon');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.className = 'bi bi-eye-slash';
    } else {
        passwordInput.type = 'password';
        toggleIcon.className = 'bi bi-eye';
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>