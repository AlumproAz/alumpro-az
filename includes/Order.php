<?php
class Order {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function create($data) {
        try {
            $this->db->beginTransaction();
            
            // Generate order number
            $orderNumber = $this->generateOrderNumber();
            
            // Create order
            $orderData = [
                'order_number' => $orderNumber,
                'customer_id' => $data['customer_id'],
                'store_id' => $data['store_id'],
                'salesperson_id' => $data['salesperson_id'],
                'total_amount' => $data['total_amount'],
                'discount_amount' => $data['discount_amount'] ?? 0,
                'tax_amount' => $data['tax_amount'] ?? 0,
                'shipping_cost' => $data['shipping_cost'] ?? 0,
                'installation_cost' => $data['installation_cost'] ?? 0,
                'grand_total' => $data['grand_total'],
                'status' => 'pending',
                'payment_status' => 'pending',
                'notes' => $data['notes'] ?? null,
                'order_date' => date('Y-m-d H:i:s')
            ];
            
            $orderId = $this->db->insert('orders', $orderData);
            
            // Add order items
            if (isset($data['items'])) {
                foreach ($data['items'] as $item) {
                    $this->addOrderItem($orderId, $item);
                }
            }
            
            // Update inventory
            $this->updateInventory($orderId, $data['items']);
            
            // Send notifications
            $this->sendOrderNotifications($orderId);
            
            $this->db->commit();
            
            return ['success' => true, 'order_id' => $orderId, 'order_number' => $orderNumber];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function addOrderItem($orderId, $item) {
        $itemData = [
            'order_id' => $orderId,
            'profile_type_id' => $item['profile_type_id'],
            'height' => $item['height'],
            'width' => $item['width'],
            'quantity' => $item['quantity'],
            'unit_price' => $item['unit_price'],
            'total_price' => $item['unit_price'] * $item['quantity'],
            'glass_type_id' => $item['glass_type_id'] ?? null,
            'glass_height' => $item['glass_height'] ?? null,
            'glass_width' => $item['glass_width'] ?? null,
            'notes' => $item['notes'] ?? null
        ];
        
        return $this->db->insert('order_items', $itemData);
    }
    
    public function updateStatus($orderId, $status) {
        $validStatuses = ['pending', 'in_production', 'completed', 'delivered', 'cancelled'];
        
        if (!in_array($status, $validStatuses)) {
            return ['success' => false, 'message' => 'Invalid status'];
        }
        
        $updated = $this->db->update('orders',
            ['status' => $status, 'updated_at' => date('Y-m-d H:i:s')],
            'id = :id',
            ['id' => $orderId]
        );
        
        if ($updated) {
            // Log status change
            $this->logStatusChange($orderId, $status);
            
            // Send notification
            $this->sendStatusNotification($orderId, $status);
            
            return ['success' => true];
        }
        
        return ['success' => false, 'message' => 'Failed to update status'];
    }
    
    public function getById($orderId) {
        $order = $this->db->selectOne("
            SELECT o.*, 
                   c.full_name as customer_name, 
                   c.phone as customer_phone,
                   c.email as customer_email,
                   s.name as store_name,
                   u.full_name as salesperson_name
            FROM orders o
            JOIN customers c ON o.customer_id = c.id
            JOIN stores s ON o.store_id = s.id
            JOIN users u ON o.salesperson_id = u.id
            WHERE o.id = :id
        ", ['id' => $orderId]);
        
        if ($order) {
            $order['items'] = $this->getOrderItems($orderId);
            $order['accessories'] = $this->getOrderAccessories($orderId);
            $order['status_history'] = $this->getStatusHistory($orderId);
        }
        
        return $order;
    }
    
    public function getOrderItems($orderId) {
        return $this->db->select("
            SELECT oi.*, 
                   p.name as profile_name, 
                   p.color as profile_color,
                   g.name as glass_name, 
                   g.color as glass_color
            FROM order_items oi
            JOIN products p ON oi.profile_type_id = p.id
            LEFT JOIN glass_products g ON oi.glass_type_id = g.id
            WHERE oi.order_id = :order_id
        ", ['order_id' => $orderId]);
    }
    
    public function getOrderAccessories($orderId) {
        return $this->db->select("
            SELECT oa.*, p.name as accessory_name
            FROM order_accessories oa
            JOIN products p ON oa.accessory_id = p.id
            WHERE oa.order_id = :order_id
        ", ['order_id' => $orderId]);
    }
    
    public function getStatusHistory($orderId) {
        return $this->db->select("
            SELECT * FROM order_status_history
            WHERE order_id = :order_id
            ORDER BY created_at DESC
        ", ['order_id' => $orderId]);
    }
    
    public function search($filters = []) {
        $query = "
            SELECT o.*, 
                   c.full_name as customer_name,
                   s.name as store_name
            FROM orders o
            JOIN customers c ON o.customer_id = c.id
            JOIN stores s ON o.store_id = s.id
            WHERE 1=1
        ";
        
        $params = [];
        
        if (!empty($filters['order_number'])) {
            $query .= " AND o.order_number LIKE :order_number";
            $params['order_number'] = '%' . $filters['order_number'] . '%';
        }
        
        if (!empty($filters['customer_id'])) {
            $query .= " AND o.customer_id = :customer_id";
            $params['customer_id'] = $filters['customer_id'];
        }
        
        if (!empty($filters['status'])) {
            $query .= " AND o.status = :status";
            $params['status'] = $filters['status'];
        }
        
        if (!empty($filters['date_from'])) {
            $query .= " AND DATE(o.order_date) >= :date_from";
            $params['date_from'] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $query .= " AND DATE(o.order_date) <= :date_to";
            $params['date_to'] = $filters['date_to'];
        }
        
        if (!empty($filters['store_id'])) {
            $query .= " AND o.store_id = :store_id";
            $params['store_id'] = $filters['store_id'];
        }
        
        $query .= " ORDER BY o.order_date DESC";
        
        if (!empty($filters['limit'])) {
            $query .= " LIMIT " . intval($filters['limit']);
        }
        
        return $this->db->select($query, $params);
    }
    
    public function calculateTotals($items) {
        $subtotal = 0;
        
        foreach ($items as $item) {
            $subtotal += $item['quantity'] * $item['unit_price'];
        }
        
        return [
            'subtotal' => $subtotal,
            'tax' => $subtotal * 0.18, // 18% VAT
            'total' => $subtotal * 1.18
        ];
    }
    
    public function generateInvoice($orderId) {
        $order = $this->getById($orderId);
        
        if (!$order) {
            return false;
        }
        
        // Generate PDF invoice
        require_once '../vendor/autoload.php';
        
        $html = $this->generateInvoiceHTML($order);
        
        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        $output = $dompdf->output();
        $fileName = 'invoice_' . $order['order_number'] . '.pdf';
        $filePath = '../uploads/invoices/' . $fileName;
        
        file_put_contents($filePath, $output);
        
        return $filePath;
    }
    
    private function generateOrderNumber() {
        $prefix = 'ORD';
        $date = date('Ymd');
        $random = rand(1000, 9999);
        return $prefix . '-' . $date . '-' . $random;
    }
    
    private function updateInventory($orderId, $items) {
        foreach ($items as $item) {
            // Update product inventory
            if (isset($item['profile_type_id'])) {
                $this->db->query("
                    UPDATE inventory 
                    SET quantity = quantity - :quantity 
                    WHERE product_id = :product_id
                ", [
                    'quantity' => $item['quantity'],
                    'product_id' => $item['profile_type_id']
                ]);
            }
            
            // Update glass inventory
            if (isset($item['glass_type_id'])) {
                $area = ($item['glass_height'] * $item['glass_width'] * $item['quantity']) / 1000000;
                $this->db->query("
                    UPDATE inventory 
                    SET area_sqm = area_sqm - :area 
                    WHERE glass_id = :glass_id
                ", [
                    'area' => $area,
                    'glass_id' => $item['glass_type_id']
                ]);
            }
        }
    }
    
    private function sendOrderNotifications($orderId) {
        $order = $this->getById($orderId);
        
        // Notify customer
        if ($order['customer_email']) {
            // Send email notification
            $this->sendOrderEmail($order);
        }
        
        if ($order['customer_phone']) {
            // Send SMS/WhatsApp notification
            $this->sendOrderSMS($order);
        }
        
        // Notify salesperson
        $this->notifySalesperson($order);
    }
    
    private function sendStatusNotification($orderId, $status) {
        $order = $this->getById($orderId);
        
        $statusMessages = [
            'in_production' => 'Sifarişiniz istehsalatdadır',
            'completed' => 'Sifarişiniz hazırdır',
            'delivered' => 'Sifarişiniz çatdırılıb'
        ];
        
        if (isset($statusMessages[$status])) {
            $message = "Sifariş №{$order['order_number']}: " . $statusMessages[$status];
            
            // Send notification
            $this->db->insert('notifications', [
                'user_id' => $order['customer_user_id'] ?? null,
                'title' => 'Sifariş Statusu',
                'message' => $message,
                'type' => 'order',
                'related_id' => $orderId
            ]);
        }
    }
    
    private function logStatusChange($orderId, $status) {
        $this->db->insert('order_status_history', [
            'order_id' => $orderId,
            'status' => $status,
            'changed_by' => $_SESSION['user_id'] ?? null,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    private function generateInvoiceHTML($order) {
        // Generate HTML for invoice
        $html = '<html><body>';
        $html .= '<h1>Invoice #' . $order['order_number'] . '</h1>';
        // Add more invoice details
        $html .= '</body></html>';
        return $html;
    }
    
    private function sendOrderEmail($order) {
        // Implement email sending
    }
    
    private function sendOrderSMS($order) {
        // Implement SMS sending
    }
    
    private function notifySalesperson($order) {
        // Implement salesperson notification
    }
}