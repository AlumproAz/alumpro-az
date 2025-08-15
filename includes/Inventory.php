<?php
class Inventory {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function addProduct($data) {
        try {
            $this->db->beginTransaction();
            
            // Generate product code
            $data['code'] = $this->generateProductCode($data['category_id']);
            
            // Insert product
            $productId = $this->db->insert('products', $data);
            
            // Add to inventory
            $inventoryData = [
                'product_id' => $productId,
                'quantity' => $data['initial_quantity'] ?? 0,
                'store_id' => $data['store_id'],
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $inventoryId = $this->db->insert('inventory', $inventoryData);
            
            // Log transaction
            $this->logTransaction([
                'inventory_id' => $inventoryId,
                'transaction_type' => 'in',
                'quantity' => $data['initial_quantity'] ?? 0,
                'notes' => 'İlkin stok',
                'created_by' => $_SESSION['user_id'] ?? 1
            ]);
            
            $this->db->commit();
            return ['success' => true, 'product_id' => $productId];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function addGlass($data) {
        try {
            $this->db->beginTransaction();
            
            // Generate glass code
            $data['code'] = $this->generateGlassCode();
            
            // Insert glass product
            $glassId = $this->db->insert('glass_products', $data);
            
            // Calculate area in square meters
            $areaSqm = ($data['height'] * $data['width'] * $data['quantity']) / 1000000;
            
            // Add to inventory
            $inventoryData = [
                'glass_id' => $glassId,
                'quantity' => $data['quantity'],
                'area_sqm' => $areaSqm,
                'store_id' => $data['store_id'],
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $inventoryId = $this->db->insert('inventory', $inventoryData);
            
            // Log transaction
            $this->logTransaction([
                'inventory_id' => $inventoryId,
                'transaction_type' => 'in',
                'quantity' => $data['quantity'],
                'notes' => 'Şüşə əlavə edildi - ' . $areaSqm . ' m²',
                'created_by' => $_SESSION['user_id'] ?? 1
            ]);
            
            $this->db->commit();
            return ['success' => true, 'glass_id' => $glassId];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function updateStock($inventoryId, $quantity, $type = 'in', $notes = '') {
        try {
            $this->db->beginTransaction();
            
            $inventory = $this->db->selectOne("SELECT * FROM inventory WHERE id = :id", ['id' => $inventoryId]);
            
            if (!$inventory) {
                throw new Exception('Inventory item not found');
            }
            
            if ($type === 'in') {
                $newQuantity = $inventory['quantity'] + $quantity;
            } else {
                $newQuantity = $inventory['quantity'] - $quantity;
                if ($newQuantity < 0) {
                    throw new Exception('Insufficient stock');
                }
            }
            
            // Update inventory
            $this->db->update('inventory', 
                ['quantity' => $newQuantity, 'updated_at' => date('Y-m-d H:i:s')],
                'id = :id',
                ['id' => $inventoryId]
            );
            
            // Log transaction
            $this->logTransaction([
                'inventory_id' => $inventoryId,
                'transaction_type' => $type,
                'quantity' => $quantity,
                'notes' => $notes,
                'created_by' => $_SESSION['user_id'] ?? 1
            ]);
            
            $this->db->commit();
            return ['success' => true, 'new_quantity' => $newQuantity];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function getInventoryByStore($storeId, $type = 'all') {
        $query = "
            SELECT 
                i.*,
                p.name as product_name,
                p.color as product_color,
                p.type as product_type,
                p.unit,
                g.name as glass_name,
                g.color as glass_color,
                g.type as glass_type,
                c.name as category_name
            FROM inventory i
            LEFT JOIN products p ON i.product_id = p.id
            LEFT JOIN glass_products g ON i.glass_id = g.id
            LEFT JOIN categories c ON (p.category_id = c.id OR g.category_id = c.id)
            WHERE i.store_id = :store_id
        ";
        
        if ($type === 'product') {
            $query .= " AND i.product_id IS NOT NULL";
        } elseif ($type === 'glass') {
            $query .= " AND i.glass_id IS NOT NULL";
        }
        
        $query .= " ORDER BY c.name, COALESCE(p.name, g.name)";
        
        return $this->db->select($query, ['store_id' => $storeId]);
    }
    
    public function getLowStockItems($storeId, $threshold = 10) {
        return $this->db->select("
            SELECT 
                i.*,
                COALESCE(p.name, g.name) as item_name,
                COALESCE(p.code, g.code) as item_code,
                c.name as category_name
            FROM inventory i
            LEFT JOIN products p ON i.product_id = p.id
            LEFT JOIN glass_products g ON i.glass_id = g.id
            LEFT JOIN categories c ON (p.category_id = c.id OR g.category_id = c.id)
            WHERE i.store_id = :store_id AND i.quantity <= :threshold
            ORDER BY i.quantity ASC
        ", ['store_id' => $storeId, 'threshold' => $threshold]);
    }
    
    public function getTransactionHistory($inventoryId = null, $limit = 100) {
        $query = "
            SELECT 
                it.*,
                u.full_name as created_by_name,
                i.product_id,
                i.glass_id,
                COALESCE(p.name, g.name) as item_name
            FROM inventory_transactions it
            JOIN inventory i ON it.inventory_id = i.id
            LEFT JOIN products p ON i.product_id = p.id
            LEFT JOIN glass_products g ON i.glass_id = g.id
            LEFT JOIN users u ON it.created_by = u.id
        ";
        
        $params = [];
        if ($inventoryId) {
            $query .= " WHERE it.inventory_id = :inventory_id";
            $params['inventory_id'] = $inventoryId;
        }
        
        $query .= " ORDER BY it.created_at DESC LIMIT " . intval($limit);
        
        return $this->db->select($query, $params);
    }
    
    public function searchInventory($searchTerm, $storeId = null) {
        $query = "
            SELECT 
                i.*,
                COALESCE(p.name, g.name) as item_name,
                COALESCE(p.code, g.code) as item_code,
                COALESCE(p.color, g.color) as item_color,
                c.name as category_name
            FROM inventory i
            LEFT JOIN products p ON i.product_id = p.id
            LEFT JOIN glass_products g ON i.glass_id = g.id
            LEFT JOIN categories c ON (p.category_id = c.id OR g.category_id = c.id)
            WHERE (
                p.name LIKE :search OR 
                p.code LIKE :search OR 
                g.name LIKE :search OR 
                g.code LIKE :search OR
                c.name LIKE :search
            )
        ";
        
        $params = ['search' => '%' . $searchTerm . '%'];
        
        if ($storeId) {
            $query .= " AND i.store_id = :store_id";
            $params['store_id'] = $storeId;
        }
        
        return $this->db->select($query, $params);
    }
    
    public function getInventoryStats($storeId) {
        $stats = [];
        
        // Total products
        $stats['total_products'] = $this->db->selectOne("
            SELECT COUNT(DISTINCT product_id) as count 
            FROM inventory 
            WHERE store_id = :store_id AND product_id IS NOT NULL
        ", ['store_id' => $storeId])['count'];
        
        // Total glass types
        $stats['total_glass'] = $this->db->selectOne("
            SELECT COUNT(DISTINCT glass_id) as count 
            FROM inventory 
            WHERE store_id = :store_id AND glass_id IS NOT NULL
        ", ['store_id' => $storeId])['count'];
        
        // Total inventory value
        $stats['total_value'] = $this->db->selectOne("
            SELECT 
                SUM(CASE 
                    WHEN i.product_id IS NOT NULL THEN i.quantity * p.purchase_price
                    WHEN i.glass_id IS NOT NULL THEN i.area_sqm * g.purchase_price
                    ELSE 0
                END) as total
            FROM inventory i
            LEFT JOIN products p ON i.product_id = p.id
            LEFT JOIN glass_products g ON i.glass_id = g.id
            WHERE i.store_id = :store_id
        ", ['store_id' => $storeId])['total'] ?? 0;
        
        // Low stock items count
        $stats['low_stock_count'] = count($this->getLowStockItems($storeId));
        
        return $stats;
    }
    
    private function generateProductCode($categoryId) {
        $category = $this->db->selectOne("SELECT name FROM categories WHERE id = :id", ['id' => $categoryId]);
        $prefix = strtoupper(substr($category['name'], 0, 3));
        $count = $this->db->selectOne("SELECT COUNT(*) as count FROM products WHERE category_id = :id", ['id' => $categoryId])['count'];
        return $prefix . '-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
    }
    
    private function generateGlassCode() {
        $count = $this->db->selectOne("SELECT COUNT(*) as count FROM glass_products")['count'];
        return 'GLS-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
    }
    
    private function logTransaction($data) {
        return $this->db->insert('inventory_transactions', $data);
    }
    
    public function transferInventory($fromStoreId, $toStoreId, $inventoryId, $quantity) {
        try {
            $this->db->beginTransaction();
            
            // Get source inventory
            $sourceInventory = $this->db->selectOne("
                SELECT * FROM inventory 
                WHERE id = :id AND store_id = :store_id
            ", ['id' => $inventoryId, 'store_id' => $fromStoreId]);
            
            if (!$sourceInventory) {
                throw new Exception('Source inventory not found');
            }
            
            if ($sourceInventory['quantity'] < $quantity) {
                throw new Exception('Insufficient quantity for transfer');
            }
            
            // Check if destination inventory exists
            $destInventory = $this->db->selectOne("
                SELECT * FROM inventory 
                WHERE store_id = :store_id 
                AND (product_id = :product_id OR glass_id = :glass_id)
            ", [
                'store_id' => $toStoreId,
                'product_id' => $sourceInventory['product_id'],
                'glass_id' => $sourceInventory['glass_id']
            ]);
            
            // Update source inventory
            $this->db->update('inventory',
                ['quantity' => $sourceInventory['quantity'] - $quantity],
                'id = :id',
                ['id' => $inventoryId]
            );
            
            // Update or create destination inventory
            if ($destInventory) {
                $this->db->update('inventory',
                    ['quantity' => $destInventory['quantity'] + $quantity],
                    'id = :id',
                    ['id' => $destInventory['id']]
                );
                $destInventoryId = $destInventory['id'];
            } else {
                $newInventory = [
                    'product_id' => $sourceInventory['product_id'],
                    'glass_id' => $sourceInventory['glass_id'],
                    'quantity' => $quantity,
                    'area_sqm' => $sourceInventory['area_sqm'] ? ($sourceInventory['area_sqm'] * $quantity / $sourceInventory['quantity']) : null,
                    'store_id' => $toStoreId
                ];
                $destInventoryId = $this->db->insert('inventory', $newInventory);
            }
            
            // Log transactions
            $this->logTransaction([
                'inventory_id' => $inventoryId,
                'transaction_type' => 'out',
                'quantity' => $quantity,
                'notes' => 'Transfer to store ' . $toStoreId,
                'created_by' => $_SESSION['user_id']
            ]);
            
            $this->logTransaction([
                'inventory_id' => $destInventoryId,
                'transaction_type' => 'in',
                'quantity' => $quantity,
                'notes' => 'Transfer from store ' . $fromStoreId,
                'created_by' => $_SESSION['user_id']
            ]);
            
            $this->db->commit();
            return ['success' => true];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}