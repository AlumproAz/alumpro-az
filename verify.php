<?php
$pageTitle = 'Hesab Təsdiqi';
require_once 'config/config.php';
require_once 'includes/Database.php';
require_once 'includes/Auth.php';

$auth = new Auth();
$error = '';
$success = '';
$userId = $_GET['user_id'] ?? 0;

if (!$userId) {
    header('Location: register.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = $_POST['code'] ?? '';
    
    $result = $auth->verifyCode($userId, $code);
    
    if ($result['success']) {
        $success = 'Hesabınız uğurla təsdiqləndi! İndi daxil ola bilərsiniz.';
        // Redirect to login after 3 seconds
        header('refresh:3;url=login.php');
    } else {
        $error = $result['message'];
    }
}

// Resend code
if (isset($_POST['resend'])) {
    $db = Database::getInstance();
    $user = $db->selectOne("SELECT * FROM users WHERE id = :id", ['id' => $userId]);
    
    if ($user) {
        $newCode = rand(100000, 999999);
        $db->update('users', ['verification_code' => $newCode], 'id = :id', ['id' => $userId]);
        $auth->sendVerificationCode($user['phone'], $newCode);
        $success = 'Yeni kod göndərildi!';
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
                            <div class="mb-3">
                                <i class="bi bi-shield-check text-primary" style="font-size: 4rem;"></i>
                            </div>
                            <h3 class="gradient-text">Hesab Təsdiqi</h3>
                            <p class="text-muted">Telefon nömrənizə göndərilən 6 rəqəmli kodu daxil edin</p>
                        </div>
                        
                        <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bi bi-check-circle"></i> <?= htmlspecialchars($success) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="mb-4">
                                <label for="code" class="form-label">Təsdiq Kodu</label>
                                <input type="text" class="form-control form-control-lg text-center" id="code" name="code" 
                                       maxlength="6" pattern="[0-9]{6}" required autofocus 
                                       placeholder="000000" style="letter-spacing: 10px; font-size: 24px;">
                            </div>
                            
                            <div class="d-grid mb-3">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-check-circle"></i> Təsdiqlə
                                </button>
                            </div>
                        </form>
                        
                        <form method="POST" action="">
                            <div class="text-center">
                                <p class="mb-2">Kod almadınız?</p>
                                <button type="submit" name="resend" class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-clockwise"></i> Yenidən Göndər
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-focus next input when typing
document.getElementById('code').addEventListener('input', function(e) {
    if (this.value.length === 6) {
        // Auto-submit when 6 digits are entered
        this.closest('form').submit();
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>