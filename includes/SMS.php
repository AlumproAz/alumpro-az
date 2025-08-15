<?php
require_once __DIR__ . '/../vendor/autoload.php';
use Twilio\Rest\Client;

class SMS {
    private $client;
    private $from;
    
    public function __construct() {
        $this->client = new Client(
            $_ENV['TWILIO_ACCOUNT_SID'] ?? 'AC_YOUR_ACCOUNT_SID',
            $_ENV['TWILIO_AUTH_TOKEN'] ?? 'YOUR_AUTH_TOKEN'
        );
        $this->from = $_ENV['TWILIO_PHONE_NUMBER'] ?? '+1234567890';
    }
    
    /**
     * Send SMS message
     */
    public function send($to, $message) {
        try {
            // Format Azerbaijan phone number
            $to = $this->formatPhoneNumber($to);
            
            $message = $this->client->messages->create(
                $to,
                [
                    'from' => $this->from,
                    'body' => $message
                ]
            );
            
            // Log SMS
            $this->logSMS($to, $message->body, 'sent', $message->sid);
            
            return [
                'success' => true,
                'message_id' => $message->sid
            ];
        } catch (Exception $e) {
            // Log error
            $this->logSMS($to, $message, 'failed', null, $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Send OTP code
     */
    public function sendOTP($phone, $code) {
        $message = "Alumpro.Az təsdiq kodunuz: {$code}\nKod 5 dəqiqə ərzində etibarlıdır.";
        return $this->send($phone, $message);
    }
    
    /**
     * Send order confirmation
     */
    public function sendOrderConfirmation($phone, $orderNumber, $amount) {
        $message = "Sifarişiniz qəbul edildi!\n";
        $message .= "№: {$orderNumber}\n";
        $message .= "Məbləğ: {$amount} ₼\n";
        $message .= "Alumpro.Az";
        
        return $this->send($phone, $message);
    }
    
    /**
     * Send payment reminder
     */
    public function sendPaymentReminder($phone, $orderNumber, $amount) {
        $message = "Xatırlatma: Sifariş №{$orderNumber} üçün {$amount} ₼ ödəniş gözlənilir.\n";
        $message .= "Alumpro.Az";
        
        return $this->send($phone, $message);
    }
    
    /**
     * Format phone number to international format
     */
    private function formatPhoneNumber($phone) {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Add country code if not present
        if (!str_starts_with($phone, '994')) {
            // Remove leading 0 if present
            $phone = ltrim($phone, '0');
            $phone = '994' . $phone;
        }
        
        return '+' . $phone;
    }
    
    /**
     * Log SMS to database
     */
    private function logSMS($to, $message, $status, $messageId = null, $error = null) {
        $db = Database::getInstance();
        
        $db->insert('sms_logs', [
            'phone' => $to,
            'message' => $message,
            'status' => $status,
            'message_id' => $messageId,
            'error' => $error,
            'sent_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Get SMS balance
     */
    public function getBalance() {
        try {
            $account = $this->client->api->v2010->accounts($_ENV['TWILIO_ACCOUNT_SID'])->fetch();
            return $account->balance;
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Send bulk SMS
     */
    public function sendBulk($recipients, $message) {
        $results = [];
        
        foreach ($recipients as $recipient) {
            $result = $this->send($recipient['phone'], $message);
            $results[] = [
                'phone' => $recipient['phone'],
                'success' => $result['success'],
                'message_id' => $result['message_id'] ?? null,
                'error' => $result['error'] ?? null
            ];
            
            // Add delay to avoid rate limiting
            usleep(100000); // 0.1 second
        }
        
        return $results;
    }
}