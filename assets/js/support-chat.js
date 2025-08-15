// Support Chat JavaScript
class SupportChat {
    constructor() {
        this.chatId = null;
        this.isOpen = false;
        this.isMinimized = false;
        this.recognition = null;
        this.isRecording = false;
        this.typingTimeout = null;
        this.agentJoined = false;
        
        this.init();
    }
    
    init() {
        this.loadElements();
        this.bindEvents();
        this.initializeChat();
        this.setupSpeechRecognition();
        
        // Auto-open after 6 seconds
        setTimeout(() => {
            if (!this.isOpen) {
                this.openChat();
            }
        }, 6000);
    }
    
    loadElements() {
        this.elements = {
            button: document.getElementById('supportChatButton'),
            modal: document.getElementById('supportChatModal'),
            messages: document.getElementById('chatMessages'),
            input: document.getElementById('chatInput'),
            sendBtn: document.getElementById('chatSendBtn'),
            closeBtn: document.getElementById('chatCloseBtn'),
            minimizeBtn: document.getElementById('chatMinimizeBtn'),
            callBtn: document.getElementById('chatCallBtn'),
            whatsappBtn: document.getElementById('chatWhatsappBtn'),
            emailBtn: document.getElementById('chatEmailBtn'),
            attachBtn: document.getElementById('chatAttachBtn'),
            fileInput: document.getElementById('chatFileInput'),
            emojiBtn: document.getElementById('chatEmojiBtn'),
            emojiPicker: document.getElementById('emojiPicker'),
            voiceBtn: document.getElementById('chatVoiceBtn'),
            quickReplies: document.getElementById('quickReplies')
        };
    }
    
    bindEvents() {
        // Main buttons
        this.elements.button.addEventListener('click', () => this.toggleChat());
        this.elements.closeBtn.addEventListener('click', () => this.closeChat());
        this.elements.minimizeBtn.addEventListener('click', () => this.minimizeChat());
        
        // Send message
        this.elements.sendBtn.addEventListener('click', () => this.sendMessage());
        this.elements.input.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                this.sendMessage();
            } else {
                this.showTypingIndicator();
            }
        });
        
        // Action buttons
        this.elements.callBtn.addEventListener('click', () => this.makeCall());
        this.elements.whatsappBtn.addEventListener('click', () => this.openWhatsApp());
        this.elements.emailBtn.addEventListener('click', () => this.sendEmail());
        
        // File attachment
        this.elements.attachBtn.addEventListener('click', () => {
            this.elements.fileInput.click();
        });
        this.elements.fileInput.addEventListener('change', (e) => {
            this.handleFileUpload(e.target.files[0]);
        });
        
        // Emoji picker
        this.elements.emojiBtn.addEventListener('click', () => this.toggleEmojiPicker());
        document.querySelectorAll('.emoji').forEach(emoji => {
            emoji.addEventListener('click', (e) => {
                this.insertEmoji(e.target.textContent);
            });
        });
        
        // Voice recording
        this.elements.voiceBtn.addEventListener('click', () => this.toggleVoiceRecording());
        
        // Quick replies
        document.querySelectorAll('.quick-reply-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const message = e.currentTarget.dataset.message;
                this.elements.input.value = message;
                this.sendMessage();
            });
        });
        
        // Click outside to close emoji picker
        document.addEventListener('click', (e) => {
            if (!this.elements.emojiBtn.contains(e.target) && 
                !this.elements.emojiPicker.contains(e.target)) {
                this.elements.emojiPicker.style.display = 'none';
            }
        });
    }
    
    async initializeChat() {
        try {
            const response = await fetch('/api/chat/initialize.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    guest_id: this.getGuestId()
                })
            });
            
            const data = await response.json();
            this.chatId = data.chat_id;
            
            // Load chat history
            this.loadChatHistory();
            
            // Start polling for new messages
            this.startPolling();
            
        } catch (error) {
            console.error('Chat initialization failed:', error);
        }
    }
    
    async loadChatHistory() {
        try {
            const response = await fetch(`/api/chat/history.php?chat_id=${this.chatId}`);
            const messages = await response.json();
            
            this.elements.messages.innerHTML = '';
            messages.forEach(msg => {
                this.displayMessage(msg);
            });
            
            this.scrollToBottom();
            
        } catch (error) {
            console.error('Failed to load chat history:', error);
        }
    }
    
    async sendMessage() {
        const message = this.elements.input.value.trim();
        if (!message) return;
        
        // Display user message immediately
        this.displayMessage({
            sender_type: 'user',
            message: message,
            created_at: new Date().toISOString()
        });
        
        // Clear input
        this.elements.input.value = '';
        
        // Show typing indicator
        this.showTypingIndicator();
        
        try {
            const response = await fetch('/api/chat/send.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    chat_id: this.chatId,
                    message: message
                })
            });
            
            const data = await response.json();
            
            // Remove typing indicator
            this.hideTypingIndicator();
            
            // Display AI response if no agent is online
            if (!this.agentJoined && data.response) {
                setTimeout(() => {
                    this.displayMessage({
                        sender_type: 'auto',
                        message: data.response,
                        created_at: new Date().toISOString()
                    });
                }, 1000);
            }
            
        } catch (error) {
            console.error('Failed to send message:', error);
            this.hideTypingIndicator();
        }
    }
    
    displayMessage(message) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `chat-message ${message.sender_type}`;
        
        const time = new Date(message.created_at).toLocaleTimeString('az-AZ', {
            hour: '2-digit',
            minute: '2-digit'
        });
        
        let content = `
            <div class="message-content">
                <div class="message-text">${this.formatMessage(message.message)}</div>
        `;
        
        if (message.attachment) {
            content += `<div class="message-attachment">`;
            if (message.attachment_type === 'image') {
                content += `<img src="/${message.attachment}" alt="Attachment" onclick="window.open('/${message.attachment}', '_blank')">`;
            } else if (message.attachment_type === 'audio') {
                content += `<audio controls src="/${message.attachment}"></audio>`;
            }
            content += `</div>`;
        }
        
        content += `<div class="message-time">${time}</div></div>`;
        
        messageDiv.innerHTML = content;
        this.elements.messages.appendChild(messageDiv);
        this.scrollToBottom();
    }
    
    formatMessage(message) {
        // Format message with markdown-like syntax
        message = message
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.*?)\*/g, '<em>$1</em>')
            .replace(/\n/g, '<br>')
            .replace(/•/g, '&#8226;')
            .replace(/📋|📦|💰|📐|🪟|📞|📧|📍|🕐|💬|🔍|👤|📅|💰|📊|⏱|✨|🎉|🎧/g, match => `<span style="font-size: 1.2em">${match}</span>`);
        
        return message;
    }
    
    showTypingIndicator() {
        clearTimeout(this.typingTimeout);
        
        const existing = document.querySelector('.typing-indicator');
        if (!existing) {
            const typingDiv = document.createElement('div');
            typingDiv.className = 'chat-message support';
            typingDiv.innerHTML = `
                <div class="message-content typing-indicator">
                    <div class="typing-dots">
                        <span class="typing-dot"></span>
                        <span class="typing-dot"></span>
                        <span class="typing-dot"></span>
                    </div>
                </div>
            `;
            this.elements.messages.appendChild(typingDiv);
            this.scrollToBottom();
        }
        
        this.typingTimeout = setTimeout(() => {
            this.hideTypingIndicator();
        }, 3000);
    }
    
    hideTypingIndicator() {
        const typingIndicator = document.querySelector('.typing-indicator');
        if (typingIndicator) {
            typingIndicator.closest('.chat-message').remove();
        }
    }
    
    async handleFileUpload(file) {
        if (!file) return;
        
        const maxSize = 5 * 1024 * 1024; // 5MB
        if (file.size > maxSize) {
            alert('Fayl həcmi 5MB-dan çox ola bilməz!');
            return;
        }
        
        const formData = new FormData();
        formData.append('file', file);
        formData.append('chat_id', this.chatId);
        
        try {
            const response = await fetch('/api/chat/upload.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Display message with attachment
                this.displayMessage({
                    sender_type: 'user',
                    message: file.type.startsWith('image/') ? 'Şəkil göndərildi' : 'Fayl göndərildi',
                    attachment: data.file_path,
                    attachment_type: file.type.startsWith('image/') ? 'image' : 'file',
                    created_at: new Date().toISOString()
                });
            }
            
        } catch (error) {
            console.error('File upload failed:', error);
        }
    }
    
    setupSpeechRecognition() {
        if ('webkitSpeechRecognition' in window) {
            this.recognition = new webkitSpeechRecognition();
            this.recognition.lang = 'az-AZ';
            this.recognition.continuous = false;
            this.recognition.interimResults = false;
            
            this.recognition.onresult = (event) => {
                const transcript = event.results[0][0].transcript;
                this.elements.input.value = transcript;
                this.stopVoiceRecording();
            };
            
            this.recognition.onerror = () => {
                this.stopVoiceRecording();
            };
            
            this.recognition.onend = () => {
                this.stopVoiceRecording();
            };
        }
    }
    
    toggleVoiceRecording() {
        if (this.isRecording) {
            this.stopVoiceRecording();
        } else {
            this.startVoiceRecording();
        }
    }
    
    startVoiceRecording() {
        if (!this.recognition) {
            alert('Səs tanıma bu brauzerdə dəstəklənmir!');
            return;
        }
        
        this.isRecording = true;
        this.elements.voiceBtn.classList.add('recording');
        this.recognition.start();
    }
    
    stopVoiceRecording() {
        if (this.recognition) {
            this.recognition.stop();
        }
        this.isRecording = false;
        this.elements.voiceBtn.classList.remove('recording');
    }
    
    toggleEmojiPicker() {
        const picker = this.elements.emojiPicker;
        picker.style.display = picker.style.display === 'none' ? 'block' : 'none';
    }
    
    insertEmoji(emoji) {
        this.elements.input.value += emoji;
        this.elements.emojiPicker.style.display = 'none';
        this.elements.input.focus();
    }
    
    makeCall() {
        window.location.href = 'tel:+994123456789';
    }
    
    openWhatsApp() {
        const phone = '994123456789';
        const message = encodeURIComponent('Salam, Alumpro.Az saytından yazıram.');
        window.open(`https://wa.me/${phone}?text=${message}`, '_blank');
    }
    
    sendEmail() {
        window.location.href = 'mailto:info@alumpro.az?subject=Dəstək tələbi';
    }
    
    openChat() {
        this.isOpen = true;
        this.elements.modal.classList.add('show');
        this.elements.button.style.display = 'none';
        
        // Focus input
        setTimeout(() => {
            this.elements.input.focus();
        }, 300);
    }
    
    closeChat() {
        this.isOpen = false;
        this.elements.modal.classList.remove('show');
        this.elements.button.style.display = 'flex';
    }
    
    toggleChat() {
        if (this.isOpen) {
            this.closeChat();
        } else {
            this.openChat();
        }
    }
    
    minimizeChat() {
        this.isMinimized = !this.isMinimized;
        this.elements.modal.classList.toggle('minimized');
    }
    
    scrollToBottom() {
        this.elements.messages.scrollTop = this.elements.messages.scrollHeight;
    }
    
    startPolling() {
        setInterval(async () => {
            if (!this.isOpen || !this.chatId) return;
            
            try {
                const response = await fetch(`/api/chat/poll.php?chat_id=${this.chatId}&last_check=${this.lastMessageTime}`);
                const data = await response.json();
                
                if (data.new_messages) {
                    data.messages.forEach(msg => {
                        this.displayMessage(msg);
                    });
                }
                
                if (data.agent_joined && !this.agentJoined) {
                    this.agentJoined = true;
                    this.displayMessage({
                        sender_type: 'auto',
                        message: '🎧 Satıcı söhbətə qoşuldu. İndi birbaşa danışa bilərsiniz.',
                        created_at: new Date().toISOString()
                    });
                }
                
            } catch (error) {
                console.error('Polling failed:', error);
            }
        }, 3000);
    }
    
    getGuestId() {
        let guestId = localStorage.getItem('guest_id');
        if (!guestId) {
            guestId = 'guest_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            localStorage.setItem('guest_id', guestId);
        }
        return guestId;
    }
}

// Initialize chat when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.supportChat = new SupportChat();
});