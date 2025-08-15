<?php
require_once __DIR__ . '/../vendor/autoload.php';
use Twilio\Rest\Client;

class WhatsApp {
    private $client;
    private $from;
    private $db;
    
    public function __construct() {
        $this->client = new Client(
            $_ENV['TWILIO_ACCOUNT_SID'] ?? 'AC_YOUR_ACCOUNT_SID',
            $_ENV['TWILIO_AUTH_TOKEN'] ?? 'YOUR_AUTH_TOKEN'
        );
        $this->from = 'whatsapp:' . ($_ENV['TWILIO_WHATSAPP_NUMBER'] ?? '+14155238886');
        $this->db = Database::getInstance();
    }
    
    /**
     * Send WhatsApp message
     */
    public function sendMessage($to, $message, $mediaUrl = null) {
        try {
            $to = $this->formatWhatsAppNumber($to);
            
            $params = [
                'from' => $this->from,
                'body' => $message
            ];
            
            if ($mediaUrl) {
                $params['mediaUrl'] = [$mediaUrl];
            }
            
            $message = $this->client->messages->create($to, $params);
            
            $this->logMessage($to, $message->body, 'sent', $message->sid);
            
            return [
                'success' => true,
                'message_id' => $message->sid
            ];
        } catch (Exception $e) {
            $this->logMessage($to, $message, 'failed', null, $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Send order status update
     */
    public function sendStatusUpdate($order, $newStatus) {
        $statusMessages = [
            'pending' => 'gözləmədədir',
            'in_production' => 'istehsalatdadır',
            'completed' => 'hazırdır! Təhvil ala bilərsiniz',
            'delivered' => 'çatdırılıb. Bizi seçdiyiniz üçün təşəkkür edirik!',
            'cancelled' => 'ləğv edilib'
        ];
        
        $message = "🔔 *Sifariş Yeniləməsi*\n\n";
        $message .= "Hörmətli {$order['customer_name']},\n";
        $message .= "Sifariş №*{$order['order_number']}* {$statusMessages[$newStatus]}.\n\n";
        
        if ($newStatus === 'completed') {
            $message .= "📍 Təhvil alma ünvanı: {$order['store_address']}\n";
            $message .= "📞 Əlaqə: {$order['store_phone']}\n\n";
        }
        
        $message .= "_Alumpro.Az - Keyfiyyətli həllər_";
        
        return $this->sendMessage($order['customer_phone'], $message);
    }
    
    /**
     * Send payment reminder
     */
    public function sendPaymentReminder($customer, $amount) {
        $message = "💳 *Ödəniş Xatırlatması*\n\n";
        $message .= "Hörmətli {$customer['full_name']},\n";
        $message .= "Sizin *" . number_format($amount, 2) . " ₼* məbləğində ödənişiniz gözlənilir.\n\n";
        $message .= "Ödəniş üsulları:\n";
        $message .= "• Nağd (mağazada)\n";
        $message .= "• Kart (mağazada)\n";
        $message .= "• Bank köçürməsi\n\n";
        $message .= "_Suallarınız üçün: +994 12 345 67 89_";
        
        return $this->sendMessage($customer['phone'], $message);
    }
    
    /**
     * Send promotional message
     */
    public function sendPromotion($phone, $title, $description, $discount) {
        $message = "🎉 *{$title}*\n\n";
        $message .= "{$description}\n\n";
        $message .= "🏷️ Endirim: *{$discount}%*\n";
        $message .= "⏰ Kampaniya müddəti məhdudur!\n\n";
        $message .= "Ətraflı məlumat üçün:\n";
        $message .= "🌐 www.alumpro.az\n";
        $message .= "📞 +994 12 345 67 89";
        
        return $this->sendMessage($phone, $message);
    }
    
    /**
     * Send document
     */
    public function sendDocument($phone, $documentUrl, $caption) {
        return $this->sendMessage($phone, $caption, $documentUrl);
    }
    
    /**
     * Send catalog
     */
    public function sendCatalog($phone) {
        $message = "📚 *Alumpro.Az Kataloq*\n\n";
        $message .= "Məhsullarımız:\n";
        $message .= "• Alüminium profillər\n";
        $message .= "• Şüşə məhsullar\n";
        $message .= "• Aksesuarlar\n\n";
        $message .= "Kataloq PDF: ";
        
        $catalogUrl = 'https://alumpro.az/catalogs/2025-catalog.pdf';
        
        return $this->sendMessage($phone, $message, $catalogUrl);
    }
    
    /**
     * Format phone number for WhatsApp
     */
    private function formatWhatsAppNumber($phone) {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        if (!str_starts_with($phone, '994')) {
            $phone = ltrim($phone, '0');
            $phone = '994' . $phone;
        }
        
        return 'whatsapp:+' . $phone;
    }
    
    /**
     * Log WhatsApp message
     */
    private function logMessage($to, $message, $status, $messageId = null, $error = null) {
        $this->db->insert('whatsapp_logs', [
            'phone' => str_replace('whatsapp:', '', $to),
            'message' => $message,
            'status' => $status,
            'message_id' => $messageId,
            'error' => $error,
            'sent_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Handle incoming webhook
     */
    public function handleWebhook($data) {
        // Process incoming WhatsApp messages
        if (isset($data['Body']) && isset($data['From'])) {
            $from = str_replace('whatsapp:', '', $data['From']);
            $message = $data['Body'];
            
            // Log incoming message
            $this->db->insert('whatsapp_incoming', [
                'from_phone' => $from,
                'message' => $message,
                'received_at' => date('Y-m-d H:i:s')
            ]);
            
            // Auto-reply logic
            $this->processAutoReply($from, $message);
        }
    }
    
    /**
     * Process auto-reply
     */
    private function processAutoReply($from, $message) {
        $message = mb_strtolower($message);
        
        $replies = [
            'salam' => "Salam! Alumpro.Az-a xoş gəlmisiniz. Sizə necə kömək edə bilərik?",
            'qiymət' => "Qiymət məlumatı üçün kataloqmuza baxa bilərsiniz: www.alumpro.az/catalog",
            'sifariş' => "Sifariş vermək üçün: www.alumpro.az və ya +994 12 345 67 89",
            'ünvan' => "Ünvanımız: Bakı şəhəri, Nərimanov rayonu, Atatürk prospekti 55",
        ];
        
        foreach ($replies as $keyword => $reply) {
            if (str_contains($message, $keyword)) {
                $this->sendMessage($from, $reply);
                break;
            }
        }
    }
}