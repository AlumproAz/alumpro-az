<?php
class Notification {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function send($userId, $title, $message, $type = 'info', $relatedId = null) {
        try {
            $notificationId = $this->db->insert('notifications', [
                'user_id' => $userId,
                'title' => $title,
                'message' => $message,
                'type' => $type,
                'related_id' => $relatedId,
                'is_read' => 0,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            // Send push notification if user has enabled it
            $this->sendPushNotification($userId, $title, $message);
            
            // Send email notification if enabled
            $this->sendEmailNotification($userId, $title, $message);
            
            return ['success' => true, 'notification_id' => $notificationId];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function sendToRole($role, $title, $message, $type = 'info') {
        $users = $this->db->select("
            SELECT id FROM users 
            WHERE role = :role AND is_active = 1
        ", ['role' => $role]);
        
        foreach ($users as $user) {
            $this->send($user['id'], $title, $message, $type);
        }
        
        return ['success' => true, 'count' => count($users)];
    }
    
    public function sendBroadcast($title, $message, $type = 'info') {
        $users = $this->db->select("SELECT id FROM users WHERE is_active = 1");
        
        foreach ($users as $user) {
            $this->send($user['id'], $title, $message, $type);
        }
        
        return ['success' => true, 'count' => count($users)];
    }
    
    public function markAsRead($notificationId) {
        return $this->db->update('notifications',
            ['is_read' => 1, 'read_at' => date('Y-m-d H:i:s')],
            'id = :id',
            ['id' => $notificationId]
        );
    }
    
    public function markAllAsRead($userId) {
        return $this->db->update('notifications',
            ['is_read' => 1, 'read_at' => date('Y-m-d H:i:s')],
            'user_id = :user_id AND is_read = 0',
            ['user_id' => $userId]
        );
    }
    
    public function getUserNotifications($userId, $limit = 20, $unreadOnly = false) {
        $query = "
            SELECT * FROM notifications 
            WHERE user_id = :user_id
        ";
        
        if ($unreadOnly) {
            $query .= " AND is_read = 0";
        }
        
        $query .= " ORDER BY created_at DESC LIMIT " . intval($limit);
        
        return $this->db->select($query, ['user_id' => $userId]);
    }
    
    public function getUnreadCount($userId) {
        return $this->db->selectOne("
            SELECT COUNT(*) as count 
            FROM notifications 
            WHERE user_id = :user_id AND is_read = 0
        ", ['user_id' => $userId])['count'];
    }
    
    public function delete($notificationId) {
        return $this->db->delete('notifications', 'id = :id', ['id' => $notificationId]);
    }
    
    public function deleteOld($days = 30) {
        return $this->db->delete('notifications', 
            'created_at < DATE_SUB(NOW(), INTERVAL :days DAY) AND is_read = 1',
            ['days' => $days]
        );
    }
    
    private function sendPushNotification($userId, $title, $message) {
        $user = $this->db->selectOne("
            SELECT push_token, push_enabled 
            FROM users 
            WHERE id = :id
        ", ['id' => $userId]);
        
        if ($user && $user['push_enabled'] && $user['push_token']) {
            // Send via OneSignal or Firebase
            $this->sendOneSignalNotification($user['push_token'], $title, $message);
        }
    }
    
    private function sendOneSignalNotification($token, $title, $message) {
        $content = [
            'en' => $message,
            'az' => $message
        ];
        
        $fields = [
            'app_id' => ONESIGNAL_APP_ID,
            'include_player_ids' => [$token],
            'contents' => $content,
            'headings' => ['en' => $title, 'az' => $title],
            'url' => SITE_URL
        ];
        
        $fields = json_encode($fields);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://onesignal.com/api/v1/notifications');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json; charset=utf-8',
            'Authorization: Basic ' . ONESIGNAL_REST_API_KEY
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return $response;
    }
    
    private function sendEmailNotification($userId, $title, $message) {
        $user = $this->db->selectOne("
            SELECT email, email_notifications 
            FROM users 
            WHERE id = :id
        ", ['id' => $userId]);
        
        if ($user && $user['email_notifications'] && $user['email']) {
            require_once '../vendor/autoload.php';
            
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            
            try {
                // Server settings
                $mail->isSMTP();
                $mail->Host = SMTP_HOST;
                $mail->SMTPAuth = true;
                $mail->Username = SMTP_USERNAME;
                $mail->Password = SMTP_PASSWORD;
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = SMTP_PORT;
                
                // Recipients
                $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
                $mail->addAddress($user['email']);
                
                // Content
                $mail->isHTML(true);
                $mail->CharSet = 'UTF-8';
                $mail->Subject = $title;
                $mail->Body = $this->getEmailTemplate($title, $message);
                
                $mail->send();
                
            } catch (Exception $e) {
                // Log error
                error_log('Email notification failed: ' . $mail->ErrorInfo);
            }
        }
    }
    
    private function getEmailTemplate($title, $message) {
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #1a936f, #1a5493); color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f8f9fa; }
                .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>Alumpro.Az</h1>
                    <h2>' . htmlspecialchars($title) . '</h2>
                </div>
                <div class="content">
                    <p>' . nl2br(htmlspecialchars($message)) . '</p>
                    <p style="margin-top: 30px;">
                        <a href="' . SITE_URL . '" style="background: #1a936f; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">
                            Sayta keçid
                        </a>
                    </p>
                </div>
                <div class="footer">
                    <p>© ' . date('Y') . ' Alumpro.Az - Bütün hüquqlar qorunur</p>
                </div>
            </div>
        </body>
        </html>';
    }
}