<?php
$pageTitle = 'Yeni Şifrə';
require_once 'config/config.php';
require_once 'includes/Database.php';

$db = Database::getInstance();
$error = '';
$success = '';
$validToken = false;

$token = $_GET['token'] ?? '';

if ($token) {
    // Check if token is valid
    $reset = $db->selectOne("
        SELECT * FROM password_resets 
        WHERE token = :token AND expires_at > NOW()
    ", ['token' => $token]);
    
    if ($reset) {
        $validToken = true;
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $password = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            
            if (strlen($password) < 6) {
                $error = 'Şifrə minimum 6 simvol olmalıdır';
            } elseif ($password !== $confirmPassword) {
                $error = 'Şifrələr uyğun gəlmir';
            } else {
                // Update password
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $db->update('users',
                    ['password' => $hashedPassword],
                    'email = :email',
                    ['email' => $reset['email']]
                );
                
                // Delete reset token
                $db->delete('password_resets', 'token = :token', ['token' => $token]);
                
                $success = 'Şifrəniz uğurla yeniləndi';
            }
        }
    } else {
        $error = 'Link etibarsızdır və ya müddəti bitib';
    }
} else {
    $error = 'Token tapılmadı';
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
                            <i class="bi bi-shield-lock text-primary" style="font-size: 4rem;"></i>
                            <h3 class="mt-3">Yeni Şifrə</h3>
                        </div>
                        
                        <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                        <div class="alert alert-success">
                            <?= htmlspecialchars($success) ?>
                            <div class="mt-3">
                                <a href="login.php" class="btn btn-primary">Daxil Ol</a>
                            </div>
                        </div>
                        <?php elseif ($validToken): ?>
                        
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="password" class="form-label">Yeni Şifrə</label>
                                <input type="password" class="form-control" id="password" name="password" required minlength="6">
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Şifrəni Təsdiqlə</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="6">
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-check-circle"></i> Şifrəni Yenilə
                                </button>
                            </div>
                        </form>
                        
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>