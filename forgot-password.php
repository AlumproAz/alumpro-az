<?php
$pageTitle = 'Şifrə Bərpası';
require_once 'config/config.php';
require_once 'includes/Database.php';
require_once 'includes/Auth.php';

$auth = new Auth();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Düzgün e-poçt ünvanı daxil edin';
    } else {
        $db = Database::getInstance();
        $user = $db->selectOne("SELECT * FROM users WHERE email = :email", ['email' => $email]);
        
        if ($user) {
            // Generate reset token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Save token to database
            $db->insert('password_resets', [
                'email' => $email,
                'token' => $token,
                'expires_at' => $expires
            ]);
            
            // Send reset email
            $resetLink = SITE_URL . '/reset-password.php?token=' . $token;
            $message = "Şifrənizi bərpa etmək üçün link: " . $resetLink;
            
            // In production, use proper email sending
            mail($email, 'Şifrə Bərpası - Alumpro.Az', $message);
            
            $success = 'Şifrə bərpa linki e-poçt ünvanınıza göndərildi';
        } else {
            $error = 'Bu e-poçt ünvanı sistemdə qeydiyyatda deyil';
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
                            <i class="bi bi-key text-primary" style="font-size: 4rem;"></i>
                            <h3 class="mt-3">Şifrə Bərpası</h3>
                            <p class="text-muted">E-poçt ünvanınızı daxil edin</p>
                        </div>
                        
                        <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                        <?php else: ?>
                        
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="email" class="form-label">E-poçt Ünvanı</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                    <input type="email" class="form-control" id="email" name="email" required autofocus>
                                </div>
                            </div>
                            
                            <div class="d-grid mb-3">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-send"></i> Bərpa Linki Göndər
                                </button>
                            </div>
                        </form>
                        
                        <?php endif; ?>
                        
                        <div class="text-center">
                            <a href="login.php" class="text-decoration-none">
                                <i class="bi bi-arrow-left"></i> Girişə qayıt
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>