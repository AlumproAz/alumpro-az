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
    
    <!-- Mobile Navigation -->
    <nav class="mobile-nav d-md-none fixed-bottom bg-dark py-2">
        <div class="container">
            <div class="row text-center">
                <div class="col">
                    <a href="<?= SITE_URL ?>" class="text-white d-block">
                        <i class="bi bi-house fs-5"></i>
                        <div class="small">Ana Səhifə</div>
                    </a>
                </div>
                <div class="col">
                    <a href="<?= SITE_URL ?>/products.php" class="text-white d-block">
                        <i class="bi bi-box-seam fs-5"></i>
                        <div class="small">Məhsullar</div>
                    </a>
                </div>
                <div class="col">
                    <a href="<?= SITE_URL ?>/services.php" class="text-white d-block">
                        <i class="bi bi-gear fs-5"></i>
                        <div class="small">Xidmətlər</div>
                    </a>
                </div>
                <div class="col">
                    <a href="<?= SITE_URL ?>/contact.php" class="text-white d-block">
                        <i class="bi bi-envelope fs-5"></i>
                        <div class="small">Əlaqə</div>
                    </a>
                </div>
                <div class="col">
                    <a href="#" class="text-white d-block" id="openSupportChat">
                        <i class="bi bi-chat-dots fs-5"></i>
                        <div class="small">Dəstək</div>
                    </a>
                </div>
            </div>
        </div>
    </nav>
    
    <!-- Support Chat Modal -->
    <div class="modal fade" id="supportChatModal" tabindex="-1" aria-labelledby="supportChatModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="supportChatModalLabel"><i class="bi bi-headset"></i> Dəstək Xidməti</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div id="chat-messages" class="p-3" style="height: 300px; overflow-y: auto;">
                        <div class="d-flex mb-3">
                            <div class="support-avatar me-2">
                                <img src="<?= SITE_URL ?>/assets/img/support-avatar.png" alt="Support" width="40" height="40" class="rounded-circle">
                            </div>
                            <div class="support-message bg-light p-3 rounded">
                                <p class="mb-0">Salam! Sizə necə kömək edə bilərəm?</p>
                                <small class="text-muted">14:25</small>
                            </div>
                        </div>
                    </div>
                    <div class="support-keywords p-3 border-top">
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <button type="button" class="btn btn-sm btn-outline-secondary keyword-btn">Məhsullar</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary keyword-btn">Qiymətlər</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary keyword-btn">Sifarişlər</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary keyword-btn">Çatdırılma</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary keyword-btn">Ödəniş</button>
                        </div>
                    </div>
                    <div class="chat-input p-3 border-top">
                        <form id="chatForm">
                            <div class="input-group">
                                <button type="button" class="btn btn-outline-secondary" id="voiceInput">
                                    <i class="bi bi-mic"></i>
                                </button>
                                <input type="text" class="form-control" id="messageInput" placeholder="Mesajınızı yazın...">
                                <button type="button" class="btn btn-outline-secondary" id="attachmentBtn">
                                    <i class="bi bi-paperclip"></i>
                                </button>
                                <button type="button" class="btn btn-outline-secondary" id="emojiBtn">
                                    <i class="bi bi-emoji-smile"></i>
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-send"></i>
                                </button>
                            </div>
                            <input type="file" id="attachmentInput" class="d-none">
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Custom JS -->
    <script src="<?= SITE_URL ?>/assets/js/script.js"></script>
    
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
    
    // Support chat functionality
    $(document).ready(function() {
        $('#openSupportChat').click(function(e) {
            e.preventDefault();
            $('#supportChatModal').modal('show');
        });
        
        $('.keyword-btn').click(function() {
            $('#messageInput').val($(this).text());
        });
        
        $('#chatForm').submit(function(e) {
            e.preventDefault();
            
            const message = $('#messageInput').val().trim();
            if (!message) return;
            
            // Add user message to chat
            const now = new Date();
            const timeStr = now.getHours() + ':' + (now.getMinutes() < 10 ? '0' : '') + now.getMinutes();
            
            $('#chat-messages').append(`
                <div class="d-flex justify-content-end mb-3">
                    <div class="user-message bg-primary text-white p-3 rounded">
                        <p class="mb-0">${message}</p>
                        <small class="text-light">${timeStr}</small>
                    </div>
                </div>
            `);
            
            $('#messageInput').val('');
            
            // Scroll to bottom
            const chatMessages = document.getElementById('chat-messages');
            chatMessages.scrollTop = chatMessages.scrollHeight;
            
            // Send message to server via AJAX (in a real implementation)
            // For now, simulate auto-response after 1 second
            setTimeout(function() {
                // Simulate automatic response
                $('#chat-messages').append(`
                    <div class="d-flex mb-3">
                        <div class="support-avatar me-2">
                            <img src="<?= SITE_URL ?>/assets/img/support-avatar.png" alt="Support" width="40" height="40" class="rounded-circle">
                        </div>
                        <div class="support-message bg-light p-3 rounded">
                            <p class="mb-0">Sorğunuzu qəbul etdik. Tezliklə sizə cavab verəcəyik.</p>
                            <small class="text-muted">${timeStr}</small>
                        </div>
                    </div>
                `);
                
                // Scroll to bottom again
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }, 1000);
        });
        
        // Voice input functionality
        $('#voiceInput').click(function() {
            if ('webkitSpeechRecognition' in window) {
                const recognition = new webkitSpeechRecognition();
                recognition.lang = 'az-AZ';
                recognition.start();
                
                $(this).addClass('btn-danger').html('<i class="bi bi-mic-fill"></i>');
                
                recognition.onresult = function(event) {
                    const transcript = event.results[0][0].transcript;
                    $('#messageInput').val(transcript);
                    $('#voiceInput').removeClass('btn-danger').html('<i class="bi bi-mic"></i>');
                };
                
                recognition.onerror = function() {
                    $('#voiceInput').removeClass('btn-danger').html('<i class="bi bi-mic"></i>');
                };
                
                recognition.onend = function() {
                    $('#voiceInput').removeClass('btn-danger').html('<i class="bi bi-mic"></i>');
                };
            } else {
                alert('Səs tanıma funksiyası bu brauzer tərəfindən dəstəklənmir.');
            }
        });
        
        // Attachment handling
        $('#attachmentBtn').click(function() {
            $('#attachmentInput').click();
        });
        
        $('#attachmentInput').change(function() {
            if (this.files && this.files[0]) {
                // In a real implementation, you would upload the file
                alert('Fayl seçildi: ' + this.files[0].name);
            }
        });
    });
    </script>

    <?php if (isset($extraScripts)) echo $extraScripts; ?>
</body>
</html>