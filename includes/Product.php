<?php
class Product {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function create($data) {
        try {
            // Generate product code
            $data['code'] = $this->generateProductCode($data['category_id']);
            
            // Insert product
            $productId = $this->db->insert('products', [
                'code' => $data['code'],
                'name' => $data['name'],
                'category_id' => $data['category_id'],
                'type' => $data['type'],
                'color' => $data['color'],
                'unit' => $data['unit'],
                'size' => $data['size'] ?? null,
                'purchase_price' => $data['purchase_price'],
                'sale_price' => $data['sale_price'],
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            // Add to inventory if initial quantity provided
            if (isset($data['initial_quantity']) && $data['initial_quantity'] > 0) {
                $this->db->insert('inventory', [
                    'product_id' => $productId,
                    'quantity' => $data['initial_quantity'],
                    'store_id' => $data['store_id'] ?? 1
                ]);
            }
            
            return ['success' => true, 'product_id' => $productId];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function update($productId, $data) {
        try {
            $updateData = [
                'name' => $data['name'],
                'category_id' => $data['category_id'],
                'type' => $data['type'],
                'color' => $data['color'],
                'unit' => $data['unit'],
                'size' => $data['size'] ?? null,
                'purchase_price' => $data['purchase_price'],
                'sale_price' => $data['sale_price'],
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $this->db->update('products', $updateData, 'id = :id', ['id' => $productId]);
            
            return ['success' => true];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function delete($productId) {
        try {
            // Check if product is used in any orders
            $orderCount = $this->db->selectOne("
                SELECT COUNT(*) as count 
                FROM order_items 
                WHERE profile_type_id = :id
            ", ['id' => $productId])['count'];
            
            if ($orderCount > 0) {
                return ['success' => false, 'message' => 'Bu məhsul sifarişlərdə istifadə olunub, silinə bilməz'];
            }
            
            // Delete from inventory first
            $this->db->delete('inventory', 'product_id = :id', ['id' => $productId]);
            
            // Delete product
            $this->db->delete('products', 'id = :id', ['id' => $productId]);
            
            return ['success' => true];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function getById($productId) {
        return $this->db->selectOne("
            SELECT p.*, c.name as category_name
            FROM products p
            JOIN categories c ON p.category_id = c.id
            WHERE p.id = :id
        ", ['id' => $productId]);
    }
    
    public function getAll($filters = []) {
        $query = "
            SELECT p.*, c.name as category_name
            FROM products p
            JOIN categories c ON p.category_id = c.id
            WHERE 1=1
        ";
        
        $params = [];
        
        if (!empty($filters['category_id'])) {
            $query .= " AND p.category_id = :category_id";
            $params['category_id'] = $filters['category_id'];
        }
        
        if (!empty($filters['search'])) {
            $query .= " AND (p.name LIKE :search OR p.code LIKE :search OR p.color LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }
        
        if (!empty($filters['type'])) {
            $query .= " AND p.type = :type";
            $params['type'] = $filters['type'];
        }
        
        $query .= " ORDER BY p.name";
        
        return $this->db->select($query, $params);
    }
    
    public function getInventory($productId, $storeId = null) {
        $query = "
            SELECT i.*, s.name as store_name
            FROM inventory i
            JOIN stores s ON i.store_id = s.id
            WHERE i.product_id = :product_id
        ";
        
        $params = ['product_id' => $productId];
        
        if ($storeId) {
            $query .= " AND i.store_id = :store_id";
            $params['store_id'] = $storeId;
        }
        
        return $this->db->select($query, $params);
    }
    
    public function updateInventory($productId, $storeId, $quantity, $type = 'add') {
        try {
            $inventory = $this->db->selectOne("
                SELECT * FROM inventory 
                WHERE product_id = :product_id AND store_id = :store_id
            ", ['product_id' => $productId, 'store_id' => $storeId]);
            
            if ($inventory) {
                $newQuantity = $type === 'add' ? 
                    $inventory['quantity'] + $quantity : 
                    $inventory['quantity'] - $quantity;
                
                if ($newQuantity < 0) {
                    return ['success' => false, 'message' => 'Kifayət qədər məhsul yoxdur'];
                }
                
                $this->db->update('inventory',
                    ['quantity' => $newQuantity],
                    'id = :id',
                    ['id' => $inventory['id']]
                );
            } else {
                // Create new inventory record
                if ($type === 'subtract') {
                    return ['success' => false, 'message' => 'Məhsul anbarda yoxdur'];
                }
                
                $this->db->insert('inventory', [
                    'product_id' => $productId,
                    'store_id' => $storeId,
                    'quantity' => $quantity
                ]);
            }
            
            // Log transaction
            $this->logInventoryTransaction($productId, $storeId, $quantity, $type);
            
            return ['success' => true];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function getCategories() {
        return $this->db->select("
            SELECT * FROM categories 
            WHERE type = 'profile' 
            ORDER BY name
        ");
    }
    
    public function getPriceHistory($productId) {
        return $this->db->select("
            SELECT * FROM product_price_history 
            WHERE product_id = :product_id 
            ORDER BY created_at DESC
        ", ['product_id' => $productId]);
    }
    
    public function updatePrice($productId, $newPrice, $type = 'sale') {
        try {
            $product = $this->getById($productId);
            
            if (!$product) {
                return ['success' => false, 'message' => 'Məhsul tapılmadı'];
            }
            
            // Save old price to history
            $this->db->insert('product_price_history', [
                'product_id' => $productId,
                'old_price' => $product[$type . '_price'],
                'new_price' => $newPrice,
                'price_type' => $type,
                'changed_by' => $_SESSION['user_id'] ?? null,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            // Update product price
            $this->db->update('products',
                [$type . '_price' => $newPrice],
                'id = :id',
                ['id' => $productId]
            );
            
            return ['success' => true];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function getTopSelling($limit = 10, $dateFrom = null, $dateTo = null) {
        $query = "
            SELECT 
                p.*,
                COUNT(oi.id) as order_count,
                SUM(oi.quantity) as total_quantity,
                SUM(oi.total_price) as total_revenue
            FROM products p
            JOIN order_items oi ON p.id = oi.profile_type_id
            JOIN orders o ON oi.order_id = o.id
            WHERE o.status != 'cancelled'
        ";
        
        $params = [];
        
        if ($dateFrom) {
            $query .= " AND DATE(o.order_date) >= :date_from";
            $params['date_from'] = $dateFrom;
        }
        
        if ($dateTo) {
            $query .= " AND DATE(o.order_date) <= :date_to";
            $params['date_to'] = $dateTo;
        }
        
        $query .= " GROUP BY p.id ORDER BY total_quantity DESC LIMIT " . intval($limit);
        
        return $this->db->select($query, $params);
    }
    
    private function generateProductCode($categoryId) {
        $category = $this->db->selectOne("
            SELECT name FROM categories WHERE id = :id
        ", ['id' => $categoryId]);
        
        $prefix = strtoupper(substr($category['name'], 0, 3));
        $count = $this->db->selectOne("
            SELECT COUNT(*) as count FROM products WHERE category_id = :id
        ", ['id' => $categoryId])['count'];
        
        return $prefix . '-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
    }
    
    private function logInventoryTransaction($productId, $storeId, $quantity, $type) {
        $inventory = $this->db->selectOne("
            SELECT id FROM inventory 
            WHERE product_id = :product_id AND store_id = :store_id
        ", ['product_id' => $productId, 'store_id' => $storeId]);
        
        if ($inventory) {
            $this->db->insert('inventory_transactions', [
                'inventory_id' => $inventory['id'],
                'transaction_type' => $type === 'add' ? 'in' : 'out',
                'quantity' => $quantity,
                'created_by' => $_SESSION['user_id'] ?? null,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }
    }
}