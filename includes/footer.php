<?php
$db = Database::getInstance();
$phone = $db->selectOne("SELECT setting_value FROM settings WHERE setting_key = 'phone'")['setting_value'];
$address = $db->selectOne("SELECT setting_value FROM settings WHERE setting_key = 'address'")['setting_value'];
$email = $db->selectOne("SELECT setting_value FROM settings WHERE setting_key = 'admin_email'")['setting_value'];
?>
    </main>
    
    <!-- Footer -->
    <footer class="footer bg-dark text-white py-5 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <h5>Əlaqə</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><i class="bi bi-geo-alt"></i> <?= $address ?></li>
                        <li class="mb-2"><i class="bi bi-telephone"></i> <?= $phone ?></li>
                        <li class="mb-2"><i class="bi bi-envelope"></i> <?= $email ?></li>
                    </ul>
                    <div class="social-links mt-3">
                        <a href="#" class="text-white me-2 fs-5"><i class="bi bi-facebook"></i></a>
                        <a href="#" class="text-white me-2 fs-5"><i class="bi bi-instagram"></i></a>
                        <a href="#" class="text-white me-2 fs-5"><i class="bi bi-whatsapp"></i></a>
                        <a href="#" class="text-white me-2 fs-5"><i class="bi bi-telegram"></i></a>
                    </div>
                </div>
                <div class="col-lg-4 mb-4">
                    <h5>Menyular</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="<?= SITE_URL ?>" class="text-light">Ana Səhifə</a></li>
                        <li class="mb-2"><a href="<?= SITE_URL ?>/about.php" class="text-light">Haqqımızda</a></li>
                        <li class="mb-2"><a href="<?= SITE_URL ?>/products.php" class="text-light">Məhsullar</a></li>
                        <li class="mb-2"><a href="<?= SITE_URL ?>/services.php" class="text-light">Xidmətlər</a></li>
                        <li class="mb-2"><a href="<?= SITE_URL ?>/contact.php" class="text-light">Əlaqə</a></li>
                    </ul>
                </div>
                <div class="col-lg-4 mb-4">
                    <h5>Abunə ol</h5>
                    <p>Yeniliklərdən xəbərdar olmaq üçün abunə olun</p>
                    <form class="mt-3">
                        <div class="input-group mb-3">
                            <input type="email" class="form-control" placeholder="E-poçt ünvanı" aria-label="E-poçt ünvanı" required>
                            <button class="btn btn-primary" type="submit">Abunə ol</button>
                        </div>
                    </form>
                    <p class="mt-3">
                        <a href="<?= SITE_URL ?>/download-app.php" class="btn btn-outline-light">
                            <i class="bi bi-phone"></i> Mobil Tətbiq
                        </a>
                    </p>
                </div>
            </div>
            <hr class="mt-4">
            <div class="row">
                <div class="col-md-6 text-center text-md-start">
                    <p class="mb-0">&copy; <?= date('Y') ?> <?= $db->selectOne("SELECT setting_value FROM settings WHERE setting_key = 'site_name'")['setting_value'] ?>. Bütün hüquqlar qorunur.</p>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <p class="mb-0">
                        <a href="<?= SITE_URL ?>/privacy-policy.php" class="text-light me-3">Gizlilik Siyasəti</a>
                        <a href="<?= SITE_URL ?>/terms.php" class="text-light">İstifadə Şərtləri</a>
                    </p>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Support Chat Button -->
    <?php include_once __DIR__ . '/support-chat.html'; ?>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Custom JS -->
    <script src="<?= SITE_URL ?>/assets/js/script.js"></script>
    <script src="<?= SITE_URL ?>/assets/js/support-chat.js"></script>
    
    <!-- PWA Service Worker Registration -->
    <script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function() {
            navigator.serviceWorker.register('<?= SITE_URL ?>/service-worker.js')
            .then(function(registration) {
                console.log('Service Worker registered with scope:', registration.scope);
            })
            .catch(function(error) {
                console.log('Service Worker registration failed:', error);
            });
        });
    }
    
    // OneSignal Push Notifications
    window.OneSignal = window.OneSignal || [];
    OneSignal.push(function() {
        OneSignal.init({
            appId: "<?= ONESIGNAL_APP_ID ?>",
            allowLocalhostAsSecureOrigin: true,
            notifyButton: {
                enable: false // Disable default button, we have our own
            },
            promptOptions: {
                siteName: "<?= SITE_NAME ?>",
                autoAcceptTitle: "Bildirişlərə icazə ver",
                actionMessage: "Yeni sifarişlər və endirimlərdən xəbərdar olmaq üçün bildirişlərə icazə verin.",
                exampleNotificationTitleDesktop: "Bu cür bildirişlər alacaqsınız",
                exampleNotificationMessageDesktop: "Yeni sifariş və ya endirimlər haqqında məlumat",
                exampleNotificationTitleMobile: "Bu cür bildirişlər alacaqsınız",
                exampleNotificationMessageMobile: "Yeni sifariş və ya endirimlər haqqında məlumat",
                acceptButton: "İcazə ver",
                cancelButton: "Xeyr",
                showCredit: false
            }
        });
        
        // Subscribe user automatically after permission granted
        OneSignal.on('subscriptionChange', function (isSubscribed) {
            if (isSubscribed) {
                OneSignal.getUserId().then(function(userId) {
                    if (userId) {
                        // Send user ID to server to store in database
                        fetch('<?= SITE_URL ?>/api/update-push-token.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                user_id: '<?= $auth->isLoggedIn() ? $currentUser['id'] : '' ?>',
                                push_token: userId
                            })
                        });
                    }
                });
            }
        });
    });
    </script>
    
    <!-- OneSignal SDK -->
    <script src="https://cdn.onesignal.com/sdks/OneSignalSDK.js" async=""></script>

    <?php if (isset($extraScripts)) echo $extraScripts; ?>
</body>
</html>