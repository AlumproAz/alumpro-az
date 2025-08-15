<?php
class SupportChat {
    private $db;
    private $userId;
    private $chatId;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->userId = $_SESSION['user_id'] ?? null;
    }
    
    public function initializeChat($guestId = null) {
        if ($this->userId) {
            $chat = $this->db->selectOne("
                SELECT * FROM support_chats 
                WHERE user_id = :user_id AND status = 'active'
                ORDER BY created_at DESC LIMIT 1
            ", ['user_id' => $this->userId]);
        } else {
            $chat = $this->db->selectOne("
                SELECT * FROM support_chats 
                WHERE guest_id = :guest_id AND status = 'active'
                ORDER BY created_at DESC LIMIT 1
            ", ['guest_id' => $guestId]);
        }
        
        if (!$chat) {
            $chatId = $this->db->insert('support_chats', [
                'user_id' => $this->userId,
                'guest_id' => $guestId,
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            $this->chatId = $chatId;
            
            // Send welcome message
            $this->sendAutoMessage($this->getWelcomeMessage());
        } else {
            $this->chatId = $chat['id'];
        }
        
        return $this->chatId;
    }
    
    public function sendMessage($message, $attachments = []) {
        $messageData = [
            'chat_id' => $this->chatId,
            'sender_type' => $this->userId ? 'user' : 'guest',
            'sender_id' => $this->userId,
            'message' => $message,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        // Handle attachments
        if (!empty($attachments)) {
            $attachmentPath = $this->handleAttachments($attachments);
            $messageData['attachment'] = $attachmentPath;
            $messageData['attachment_type'] = $attachments['type'];
        }
        
        $messageId = $this->db->insert('support_messages', $messageData);
        
        // Get AI response
        $response = $this->generateAIResponse($message);
        
        // Check if sales agent is online
        $agentOnline = $this->checkAgentAvailability();
        
        if (!$agentOnline) {
            $this->sendAutoMessage($response);
        } else {
            // Notify agent
            $this->notifyAgent($message);
        }
        
        return [
            'success' => true,
            'message_id' => $messageId,
            'response' => $response
        ];
    }
    
    public function generateAIResponse($message) {
        $message = mb_strtolower($message);
        
        // Get keywords and responses from database
        $keywords = $this->db->select("SELECT * FROM support_keywords ORDER BY priority DESC");
        
        // Check for order inquiry
        if (preg_match('/sifari[şs]|order/', $message)) {
            if (preg_match('/\b([A-Z]{3}-\d{8}-\d{4})\b/', strtoupper($message), $matches)) {
                return $this->getOrderStatus($matches[1]);
            }
            return "Sifariş nömrənizi daxil edin (məsələn: ORD-20250812-1234)";
        }
        
        // Check for price inquiry
        if (preg_match('/qiym[əe]t|price|n[əe] q[əe]d[əe]r/', $message)) {
            return $this->getPriceInformation($message);
        }
        
        // Check for product inquiry
        if (preg_match('/m[əe]hsul|profil|[şs]ü[şs][əe]|alüminium/', $message)) {
            return $this->getProductInformation($message);
        }
        
        // Check for delivery inquiry
        if (preg_match('/[çc]atd[ıi]r[ıi]lma|delivery|n[əe] vaxt/', $message)) {
            return "Çatdırılma müddəti sifarişin həcmindən asılı olaraq 3-7 iş günü təşkil edir. Təcili sifarişlər üçün əlavə ödəniş tətbiq olunur.";
        }
        
        // Check for contact inquiry
        if (preg_match('/[əe]laq[əe]|telefon|ünvan|address/', $message)) {
            return $this->getContactInformation();
        }
        
        // Check keywords
        foreach ($keywords as $keyword) {
            if (strpos($message, mb_strtolower($keyword['keyword'])) !== false) {
                return $keyword['response'];
            }
        }
        
        // Default intelligent responses based on context
        $responses = [
            'greeting' => [
                'trigger' => ['salam', 'hello', 'hi', 'səlam', 'sabah'],
                'response' => "Salam! Alumpro.Az dəstək xidmətinə xoş gəlmisiniz! Sizə necə kömək edə bilərəm?"
            ],
            'thanks' => [
                'trigger' => ['təşəkkür', 'sağ ol', 'thanks', 'təşəkkürlər'],
                'response' => "Rica edirəm! Başqa sualınız varsa, məmnuniyyətlə cavablandıraram."
            ],
            'help' => [
                'trigger' => ['kömək', 'help', 'yardım'],
                'response' => "Əlbəttə, sizə kömək etməyə hazıram! Aşağıdakı mövzulardan birini seçə bilərsiniz:\n• Məhsullar və qiymətlər\n• Sifariş statusu\n• Çatdırılma\n• Quraşdırma xidməti\n• Ödəniş üsulları"
            ]
        ];
        
        foreach ($responses as $type => $data) {
            foreach ($data['trigger'] as $trigger) {
                if (strpos($message, $trigger) !== false) {
                    return $data['response'];
                }
            }
        }
        
        // Fallback response
        return "Sualınızı daha dəqiq ifadə edə bilərsinizmi? Məsələn:\n• 'Qiymətlər haqqında məlumat'\n• 'Sifariş statusu'\n• 'Məhsullar'\n• 'Çatdırılma'";
    }
    
    private function getOrderStatus($orderNumber) {
        $order = $this->db->selectOne("
            SELECT o.*, c.full_name as customer_name
            FROM orders o
            JOIN customers c ON o.customer_id = c.id
            WHERE o.order_number = :order_number
        ", ['order_number' => $orderNumber]);
        
        if (!$order) {
            return "Bu nömrə ilə sifariş tapılmadı. Zəhmət olmasa nömrəni yoxlayın.";
        }
        
        $statusMessages = [
            'pending' => 'gözləmədədir və tezliklə istehsalata göndəriləcək',
            'in_production' => 'istehsalatdadır və hazırlanır',
            'completed' => 'tamamlanıb və çatdırılmaya hazırdır',
            'delivered' => 'çatdırılıb'
        ];
        
        $status = $statusMessages[$order['status']] ?? 'yoxlanılır';
        
        $response = "🔍 **Sifariş Məlumatı:**\n\n";
        $response .= "📋 Sifariş №: {$order['order_number']}\n";
        $response .= "👤 Müştəri: {$order['customer_name']}\n";
        $response .= "📅 Tarix: " . date('d.m.Y', strtotime($order['order_date'])) . "\n";
        $response .= "💰 Məbləğ: " . number_format($order['grand_total'], 2) . " ₼\n";
        $response .= "📊 Status: Sifarişiniz {$status}\n\n";
        
        if ($order['status'] == 'in_production') {
            $response .= "⏱ Təxmini hazır olma müddəti: 3-5 iş günü\n";
        }
        
        return $response;
    }
    
    private function getPriceInformation($message) {
        $response = "💰 **Qiymət Məlumatı:**\n\n";
        
        if (strpos($message, 'profil') !== false) {
            $response .= "📐 **Alüminium Profillər:**\n";
            $response .= "• Standart profil: 15-25 ₼/metr\n";
            $response .= "• Premium profil: 30-45 ₼/metr\n";
            $response .= "• Xüsusi dizayn: 50+ ₼/metr\n\n";
        }
        
        if (strpos($message, 'şüşə') !== false || strpos($message, 'süşə') !== false) {
            $response .= "🪟 **Şüşə Qapaqlar:**\n";
            $response .= "• Adi şüşə: 25-35 ₼/m²\n";
            $response .= "• Buzlu şüşə: 40-50 ₼/m²\n";
            $response .= "• Rəngli şüşə: 45-60 ₼/m²\n\n";
        }
        
        $response .= "📞 Dəqiq qiymət üçün: +994 12 345 67 89\n";
        $response .= "💡 Pulsuz ölçü və qiymət hesablanması xidməti mövcuddur!";
        
        return $response;
    }
    
    private function getProductInformation($message) {
        $products = $this->db->select("
            SELECT p.*, c.name as category_name
            FROM products p
            JOIN categories c ON p.category_id = c.id
            LIMIT 5
        ");
        
        $response = "📦 **Məhsullarımız:**\n\n";
        
        foreach ($products as $product) {
            $response .= "• {$product['name']} - {$product['color']}\n";
        }
        
        $response .= "\n✨ **Xidmətlərimiz:**\n";
        $response .= "• Professional quraşdırma\n";
        $response .= "• Pulsuz ölçü xidməti\n";
        $response .= "• 1 il zəmanət\n";
        $response .= "• Çatdırılma xidməti\n\n";
        $response .= "📞 Ətraflı məlumat üçün bizimlə əlaqə saxlayın!";
        
        return $response;
    }
    
    private function getContactInformation() {
        $settings = $this->db->select("SELECT * FROM settings WHERE setting_key IN ('phone', 'address', 'email')");
        $contact = [];
        foreach ($settings as $setting) {
            $contact[$setting['setting_key']] = $setting['setting_value'];
        }
        
        $response = "📍 **Əlaqə Məlumatları:**\n\n";
        $response .= "📞 Telefon: {$contact['phone']}\n";
        $response .= "📧 E-mail: {$contact['email']}\n";
        $response .= "📍 Ünvan: {$contact['address']}\n\n";
        $response .= "🕐 **İş Saatları:**\n";
        $response .= "Bazar ertəsi - Cümə: 09:00 - 18:00\n";
        $response .= "Şənbə: 10:00 - 16:00\n";
        $response .= "Bazar: İstirahət günü\n\n";
        $response .= "💬 WhatsApp: {$contact['phone']}";
        
        return $response;
    }
    
    private function getWelcomeMessage() {
        $hour = date('H');
        $greeting = $hour < 12 ? 'Sabahınız xeyir' : ($hour < 18 ? 'Gün aydın' : 'Axşamınız xeyir');
        
        $message = "🎉 {$greeting}! Alumpro.Az-a xoş gəlmisiniz!\n\n";
        $message .= "Mən sizin virtual köməkçinizəm. Size aşağıdakı mövzularda kömək edə bilərəm:\n\n";
        $message .= "📦 Məhsullar və qiymətlər\n";
        $message .= "📋 Sifariş statusu\n";
        $message .= "🚚 Çatdırılma məlumatı\n";
        $message .= "🔧 Quraşdırma xidməti\n";
        $message .= "💳 Ödəniş üsulları\n\n";
        $message .= "Necə kömək edə bilərəm?";
        
        return $message;
    }
    
    private function sendAutoMessage($message) {
        return $this->db->insert('support_messages', [
            'chat_id' => $this->chatId,
            'sender_type' => 'auto',
            'message' => $message,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    private function checkAgentAvailability() {
        $onlineAgents = $this->db->select("
            SELECT * FROM users 
            WHERE role = 'sales' 
            AND is_online = 1 
            AND last_activity >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
        ");
        
        return count($onlineAgents) > 0;
    }
    
    private function notifyAgent($message) {
        // Send notification to available agents
        $this->db->insert('notifications', [
            'user_id' => null, // Will be sent to all sales agents
            'title' => 'Yeni dəstək mesajı',
            'message' => substr($message, 0, 100),
            'type' => 'chat',
            'related_id' => $this->chatId,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    private function handleAttachments($attachments) {
        // Handle file upload
        $uploadDir = '../uploads/chat/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $fileName = uniqid() . '_' . $attachments['name'];
        $filePath = $uploadDir . $fileName;
        
        if (move_uploaded_file($attachments['tmp_name'], $filePath)) {
            // Analyze image/audio if needed
            if ($attachments['type'] == 'image') {
                $this->analyzeImage($filePath);
            } elseif ($attachments['type'] == 'audio') {
                $this->analyzeAudio($filePath);
            }
            
            return 'uploads/chat/' . $fileName;
        }
        
        return null;
    }
    
    private function analyzeImage($imagePath) {
        // Image analysis logic (can integrate with AI APIs)
        // For now, just acknowledge receipt
        $this->sendAutoMessage("Şəklinizi aldım. Analiz edirəm...");
    }
    
    private function analyzeAudio($audioPath) {
        // Audio analysis logic (can integrate with speech-to-text APIs)
        // For now, just acknowledge receipt
        $this->sendAutoMessage("Səs mesajınızı aldım. Dinləyirəm...");
    }
    
    public function getChatHistory($limit = 50) {
        return $this->db->select("
            SELECT m.*, u.full_name as sender_name
            FROM support_messages m
            LEFT JOIN users u ON m.sender_id = u.id
            WHERE m.chat_id = :chat_id
            ORDER BY m.created_at ASC
            LIMIT :limit
        ", ['chat_id' => $this->chatId, 'limit' => $limit]);
    }
    
    public function joinAsAgent($agentId) {
        // Mark agent as joined
        $this->db->update('support_chats',
            ['agent_id' => $agentId, 'agent_joined_at' => date('Y-m-d H:i:s')],
            'id = :id',
            ['id' => $this->chatId]
        );
        
        // Send notification to customer
        $agentName = $this->db->selectOne("SELECT full_name FROM users WHERE id = :id", ['id' => $agentId])['full_name'];
        $this->sendAutoMessage("🎧 {$agentName} söhbətə qoşuldu. İndi birbaşa danışa bilərsiniz.");
        
        return ['success' => true];
    }
}