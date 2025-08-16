<?php
$pageTitle = 'Dəstək Xidməti';
require_once 'config/config.php';
require_once 'includes/Database.php';
require_once 'includes/Auth.php';
require_once 'includes/SupportChat.php';

session_start();

$db = Database::getInstance();
$auth = new Auth();
$support = new SupportChat();

// Generate session ID for guests
$sessionId = $_SESSION['chat_session_id'] ?? uniqid('guest_', true);
$_SESSION['chat_session_id'] = $sessionId;

$user = $auth->isLoggedIn() ? $auth->getCurrentUser() : null;
$userId = $user ? $user['id'] : null;

// Get or create chat session
$chatId = $support->getChatSession($userId, $sessionId);

// Get chat history
$messages = $support->getChatHistory($chatId);

// Get support keywords for quick replies
$keywords = $db->select("SELECT keyword, response FROM support_keywords ORDER BY keyword");
?>

<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - <?= SITE_NAME ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
    <link href="assets/css/support-chat.css" rel="stylesheet">
    
    <style>
    body {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    
    .chat-container {
        max-width: 800px;
        margin: 50px auto;
        background: white;
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        overflow: hidden;
    }
    
    .chat-header {
        background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        color: white;
        padding: 20px;
        text-align: center;
    }
    
    .chat-status {
        background: #f8f9fa;
        padding: 10px 20px;
        border-bottom: 1px solid #dee2e6;
        font-size: 14px;
        color: #6c757d;
    }
    
    .chat-messages {
        height: 400px;
        overflow-y: auto;
        padding: 20px;
        background: #f8f9fc;
    }
    
    .message {
        margin-bottom: 15px;
        display: flex;
        align-items: flex-start;
    }
    
    .message.user {
        justify-content: flex-end;
    }
    
    .message-content {
        max-width: 70%;
        padding: 12px 16px;
        border-radius: 18px;
        font-size: 14px;
        line-height: 1.4;
    }
    
    .message.support .message-content {
        background: #e9ecef;
        color: #495057;
        border-bottom-left-radius: 4px;
    }
    
    .message.user .message-content {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-bottom-right-radius: 4px;
    }
    
    .message.auto .message-content {
        background: #d1ecf1;
        color: #0c5460;
        border-left: 3px solid #bee5eb;
        border-radius: 8px;
    }
    
    .message-time {
        font-size: 11px;
        opacity: 0.7;
        margin-top: 4px;
    }
    
    .message-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        margin-right: 10px;
        background: linear-gradient(135deg, #11998e, #38ef7d);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 14px;
        font-weight: bold;
    }
    
    .message.user .message-avatar {
        margin-right: 0;
        margin-left: 10px;
        background: linear-gradient(135deg, #667eea, #764ba2);
    }
    
    .chat-input {
        padding: 20px;
        border-top: 1px solid #dee2e6;
        background: white;
    }
    
    .quick-replies {
        padding: 10px 20px;
        background: #f8f9fa;
        border-top: 1px solid #dee2e6;
    }
    
    .quick-reply-btn {
        background: #e9ecef;
        border: none;
        padding: 8px 12px;
        margin: 4px;
        border-radius: 20px;
        font-size: 12px;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .quick-reply-btn:hover {
        background: #6c757d;
        color: white;
    }
    
    .voice-btn {
        background: none;
        border: none;
        color: #6c757d;
        font-size: 18px;
        padding: 8px;
        border-radius: 50%;
        transition: all 0.3s ease;
    }
    
    .voice-btn:hover {
        background: #e9ecef;
        color: #495057;
    }
    
    .voice-btn.recording {
        color: #dc3545;
        animation: pulse 1s infinite;
    }
    
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.1); }
        100% { transform: scale(1); }
    }
    
    .typing-indicator {
        display: none;
        padding: 10px 20px;
        font-style: italic;
        color: #6c757d;
        font-size: 14px;
    }
    
    .attachment-preview {
        max-width: 200px;
        max-height: 150px;
        border-radius: 8px;
        margin-top: 8px;
    }
    
    .emoji-picker {
        position: absolute;
        bottom: 100%;
        right: 0;
        background: white;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        padding: 10px;
        display: none;
        z-index: 1000;
    }
    
    .emoji-picker button {
        background: none;
        border: none;
        font-size: 20px;
        padding: 4px;
        margin: 2px;
        border-radius: 4px;
        cursor: pointer;
    }
    
    .emoji-picker button:hover {
        background: #f8f9fa;
    }
    </style>
</head>
<body>
    <div class="container">
        <div class="chat-container">
            <!-- Chat Header -->
            <div class="chat-header">
                <h4 class="mb-1">
                    <i class="bi bi-chat-dots"></i> Dəstək Xidməti
                </h4>
                <p class="mb-0">Sizə necə kömək edə bilərəm?</p>
            </div>
            
            <!-- Chat Status -->
            <div class="chat-status">
                <div class="d-flex justify-content-between align-items-center">
                    <span>
                        <i class="bi bi-circle-fill text-success"></i>
                        Onlayn • Orta cavab müddəti: 2 dəqiqə
                    </span>
                    <?php if ($user): ?>
                    <span>
                        <i class="bi bi-person"></i>
                        <?= htmlspecialchars($user['full_name']) ?>
                    </span>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Chat Messages -->
            <div class="chat-messages" id="chatMessages">
                <!-- Welcome message -->
                <div class="message support">
                    <div class="message-avatar">
                        <i class="bi bi-robot"></i>
                    </div>
                    <div>
                        <div class="message-content">
                            Salam<?= $user ? ', ' . htmlspecialchars($user['full_name']) : '' ?>! 👋<br>
                            Alumpro.Az dəstək xidmətinə xoş gəlmisiniz. Sizə necə kömək edə bilərəm?
                        </div>
                        <div class="message-time">Avtomatik mesaj</div>
                    </div>
                </div>
                
                <!-- Load previous messages -->
                <?php foreach ($messages as $message): ?>
                <div class="message <?= $message['sender_type'] ?>">
                    <?php if ($message['sender_type'] !== 'user'): ?>
                    <div class="message-avatar">
                        <?= $message['sender_type'] === 'auto' ? '<i class="bi bi-robot"></i>' : '<i class="bi bi-headset"></i>' ?>
                    </div>
                    <?php endif; ?>
                    
                    <div>
                        <div class="message-content">
                            <?= nl2br(htmlspecialchars($message['message'])) ?>
                            
                            <?php if ($message['attachment']): ?>
                            <div class="mt-2">
                                <?php if ($message['attachment_type'] === 'image'): ?>
                                <img src="<?= $message['attachment'] ?>" class="attachment-preview" alt="Əlavə">
                                <?php elseif ($message['attachment_type'] === 'audio'): ?>
                                <audio controls>
                                    <source src="<?= $message['attachment'] ?>" type="audio/mpeg">
                                </audio>
                                <?php else: ?>
                                <a href="<?= $message['attachment'] ?>" target="_blank">
                                    <i class="bi bi-file-earmark"></i> Fayl
                                </a>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="message-time">
                            <?= date('H:i', strtotime($message['created_at'])) ?>
                        </div>
                    </div>
                    
                    <?php if ($message['sender_type'] === 'user'): ?>
                    <div class="message-avatar">
                        <i class="bi bi-person"></i>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Typing Indicator -->
            <div class="typing-indicator" id="typingIndicator">
                <i class="bi bi-three-dots"></i> Yazılır...
            </div>
            
            <!-- Quick Replies -->
            <div class="quick-replies">
                <small class="text-muted">Tez cavablar:</small><br>
                <?php foreach (array_slice($keywords, 0, 6) as $keyword): ?>
                <button class="quick-reply-btn" onclick="sendQuickReply('<?= htmlspecialchars($keyword['keyword']) ?>')">
                    <?= htmlspecialchars($keyword['keyword']) ?>
                </button>
                <?php endforeach; ?>
            </div>
            
            <!-- Chat Input -->
            <div class="chat-input">
                <div class="input-group">
                    <input type="text" class="form-control" id="messageInput" placeholder="Mesajınızı yazın..." maxlength="500">
                    
                    <div class="input-group-append d-flex">
                        <!-- File Upload -->
                        <label class="btn btn-outline-secondary" for="fileInput" title="Fayl əlavə et">
                            <i class="bi bi-paperclip"></i>
                        </label>
                        <input type="file" id="fileInput" style="display: none;" accept="image/*,audio/*,.pdf,.doc,.docx">
                        
                        <!-- Emoji Picker -->
                        <div class="position-relative">
                            <button type="button" class="btn btn-outline-secondary" onclick="toggleEmojiPicker()" title="Emoji əlavə et">
                                <i class="bi bi-emoji-smile"></i>
                            </button>
                            <div class="emoji-picker" id="emojiPicker">
                                <button onclick="addEmoji('😊')">😊</button>
                                <button onclick="addEmoji('👍')">👍</button>
                                <button onclick="addEmoji('❤️')">❤️</button>
                                <button onclick="addEmoji('🔧')">🔧</button>
                                <button onclick="addEmoji('📱')">📱</button>
                                <button onclick="addEmoji('✅')">✅</button>
                                <button onclick="addEmoji('❌')">❌</button>
                                <button onclick="addEmoji('⭐')">⭐</button>
                            </div>
                        </div>
                        
                        <!-- Voice Recording -->
                        <button type="button" class="voice-btn" id="voiceBtn" onclick="toggleVoiceRecording()" title="Səsli mesaj">
                            <i class="bi bi-mic"></i>
                        </button>
                        
                        <!-- Send Button -->
                        <button type="button" class="btn btn-primary" onclick="sendMessage()">
                            <i class="bi bi-send"></i>
                        </button>
                    </div>
                </div>
                
                <div class="mt-2">
                    <small class="text-muted">
                        Enter - göndər • Shift+Enter - yeni sətir • 500 simvol limiti
                    </small>
                </div>
            </div>
        </div>
        
        <!-- Back to site -->
        <div class="text-center mt-3">
            <a href="home.php" class="btn btn-outline-light">
                <i class="bi bi-arrow-left"></i> Ana səhifəyə qayıt
            </a>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    let isRecording = false;
    let mediaRecorder = null;
    let audioChunks = [];
    let chatId = <?= $chatId ?>;
    let lastMessageId = <?= count($messages) > 0 ? end($messages)['id'] : 0 ?>;
    let pollInterval = null;
    
    // Initialize
    document.addEventListener('DOMContentLoaded', function() {
        scrollToBottom();
        startPolling();
        
        // Enter key handling
        document.getElementById('messageInput').addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });
        
        // File upload handling
        document.getElementById('fileInput').addEventListener('change', handleFileUpload);
        
        // Auto-resize chat container on mobile
        adjustChatHeight();
        window.addEventListener('resize', adjustChatHeight);
    });
    
    function adjustChatHeight() {
        if (window.innerWidth <= 768) {
            const chatMessages = document.getElementById('chatMessages');
            chatMessages.style.height = (window.innerHeight - 300) + 'px';
        }
    }
    
    function scrollToBottom() {
        const chatMessages = document.getElementById('chatMessages');
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }
    
    function sendMessage() {
        const input = document.getElementById('messageInput');
        const message = input.value.trim();
        
        if (!message) return;
        
        // Add message to UI immediately
        addMessageToUI('user', message);
        input.value = '';
        
        // Send to server
        fetch('api/chat/send.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                chat_id: chatId,
                message: message
            })
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                console.error('Failed to send message:', data.error);
            }
        })
        .catch(error => {
            console.error('Error sending message:', error);
        });
    }
    
    function sendQuickReply(keyword) {
        const input = document.getElementById('messageInput');
        input.value = keyword;
        sendMessage();
    }
    
    function addMessageToUI(type, content, time = null, attachment = null) {
        const chatMessages = document.getElementById('chatMessages');
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${type}`;
        
        const avatarIcon = type === 'user' ? 'bi-person' : (type === 'auto' ? 'bi-robot' : 'bi-headset');
        const displayTime = time || new Date().toLocaleTimeString('az-AZ', {hour: '2-digit', minute: '2-digit'});
        
        messageDiv.innerHTML = `
            ${type !== 'user' ? `<div class="message-avatar"><i class="bi ${avatarIcon}"></i></div>` : ''}
            <div>
                <div class="message-content">
                    ${content.replace(/\n/g, '<br>')}
                    ${attachment ? `<div class="mt-2">${attachment}</div>` : ''}
                </div>
                <div class="message-time">${displayTime}</div>
            </div>
            ${type === 'user' ? `<div class="message-avatar"><i class="bi ${avatarIcon}"></i></div>` : ''}
        `;
        
        chatMessages.appendChild(messageDiv);
        scrollToBottom();
    }
    
    function startPolling() {
        pollInterval = setInterval(pollForMessages, 3000);
    }
    
    function pollForMessages() {
        fetch(`api/chat/poll.php?chat_id=${chatId}&last_id=${lastMessageId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.messages.length > 0) {
                    data.messages.forEach(message => {
                        if (message.sender_type !== 'user') {
                            addMessageToUI(
                                message.sender_type,
                                message.message,
                                new Date(message.created_at).toLocaleTimeString('az-AZ', {hour: '2-digit', minute: '2-digit'}),
                                message.attachment ? generateAttachmentHTML(message) : null
                            );
                        }
                        lastMessageId = Math.max(lastMessageId, message.id);
                    });
                }
            })
            .catch(error => {
                console.error('Polling error:', error);
            });
    }
    
    function generateAttachmentHTML(message) {
        if (message.attachment_type === 'image') {
            return `<img src="${message.attachment}" class="attachment-preview" alt="Əlavə">`;
        } else if (message.attachment_type === 'audio') {
            return `<audio controls><source src="${message.attachment}" type="audio/mpeg"></audio>`;
        } else {
            return `<a href="${message.attachment}" target="_blank"><i class="bi bi-file-earmark"></i> Fayl</a>`;
        }
    }
    
    function toggleEmojiPicker() {
        const emojiPicker = document.getElementById('emojiPicker');
        emojiPicker.style.display = emojiPicker.style.display === 'block' ? 'none' : 'block';
    }
    
    function addEmoji(emoji) {
        const input = document.getElementById('messageInput');
        input.value += emoji;
        toggleEmojiPicker();
        input.focus();
    }
    
    function handleFileUpload(event) {
        const file = event.target.files[0];
        if (!file) return;
        
        const formData = new FormData();
        formData.append('file', file);
        formData.append('chat_id', chatId);
        
        fetch('api/chat/upload.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const attachmentHTML = generateAttachmentHTML({
                    attachment: data.file_url,
                    attachment_type: data.file_type
                });
                addMessageToUI('user', `Fayl göndərildi: ${file.name}`, null, attachmentHTML);
            } else {
                alert('Fayl yükləmə xətası: ' + data.error);
            }
        })
        .catch(error => {
            console.error('File upload error:', error);
            alert('Fayl yükləmə xətası!');
        });
        
        // Clear the input
        event.target.value = '';
    }
    
    async function toggleVoiceRecording() {
        const voiceBtn = document.getElementById('voiceBtn');
        
        if (!isRecording) {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                mediaRecorder = new MediaRecorder(stream);
                audioChunks = [];
                
                mediaRecorder.ondataavailable = (event) => {
                    audioChunks.push(event.data);
                };
                
                mediaRecorder.onstop = () => {
                    const audioBlob = new Blob(audioChunks, { type: 'audio/mpeg' });
                    uploadAudio(audioBlob);
                    stream.getTracks().forEach(track => track.stop());
                };
                
                mediaRecorder.start();
                isRecording = true;
                voiceBtn.classList.add('recording');
                voiceBtn.innerHTML = '<i class="bi bi-stop"></i>';
                
                addMessageToUI('user', 'Səs yazılır... 🎤');
                
            } catch (error) {
                console.error('Mikrofon xətası:', error);
                alert('Mikrofona giriş icazəsi lazımdır!');
            }
        } else {
            mediaRecorder.stop();
            isRecording = false;
            voiceBtn.classList.remove('recording');
            voiceBtn.innerHTML = '<i class="bi bi-mic"></i>';
        }
    }
    
    function uploadAudio(audioBlob) {
        const formData = new FormData();
        formData.append('audio', audioBlob, 'voice-message.mp3');
        formData.append('chat_id', chatId);
        
        fetch('api/chat/upload.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                addMessageToUI('user', 'Səsli mesaj göndərildi 🎵', null, 
                    `<audio controls><source src="${data.file_url}" type="audio/mpeg"></audio>`);
            } else {
                alert('Səsli mesaj xətası: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Audio upload error:', error);
            alert('Səsli mesaj xətası!');
        });
    }
    
    // Voice recognition (if supported)
    if ('webkitSpeechRecognition' in window || 'SpeechRecognition' in window) {
        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        const recognition = new SpeechRecognition();
        
        recognition.lang = 'az-AZ';
        recognition.continuous = false;
        recognition.interimResults = false;
        
        recognition.onresult = function(event) {
            const transcript = event.results[0][0].transcript;
            document.getElementById('messageInput').value = transcript;
        };
        
        recognition.onerror = function(event) {
            console.error('Speech recognition error:', event.error);
        };
        
        // Add voice input button functionality
        document.getElementById('voiceBtn').addEventListener('dblclick', function() {
            if (!isRecording) {
                recognition.start();
                addMessageToUI('support', '🎤 Danışın... (səs tanıma aktiv)');
            }
        });
    }
    
    // Clean up on page unload
    window.addEventListener('beforeunload', function() {
        if (pollInterval) {
            clearInterval(pollInterval);
        }
    });
    
    // Close emoji picker when clicking outside
    document.addEventListener('click', function(event) {
        const emojiPicker = document.getElementById('emojiPicker');
        if (!event.target.closest('.position-relative') && emojiPicker.style.display === 'block') {
            emojiPicker.style.display = 'none';
        }
    });
    </script>
</body>
</html>