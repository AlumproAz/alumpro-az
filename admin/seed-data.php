<?php
// Sample data insertion script for Alumpro.Az
require_once '../config/config.php';
require_once '../includes/Database.php';

$db = Database::getInstance();

try {
    $db->beginTransaction();
    
    // Insert sample stores
    $stores = [
        ['name' => 'Nəsimi Mağazası', 'address' => 'Bakı, Nəsimi r., H.Əliyev pr. 123', 'phone' => '+994123456789'],
        ['name' => 'Yasamal Mağazası', 'address' => 'Bakı, Yasamal r., Nobel pr. 456', 'phone' => '+994129876543']
    ];
    
    foreach ($stores as $store) {
        $existingStore = $db->selectOne("SELECT id FROM stores WHERE name = :name", ['name' => $store['name']]);
        if (!$existingStore) {
            $db->insert('stores', $store);
            echo "Store '{$store['name']}' added.\n";
        }
    }
    
    // Insert admin user
    $adminExists = $db->selectOne("SELECT id FROM users WHERE username = 'admin'");
    if (!$adminExists) {
        $adminData = [
            'username' => 'admin',
            'password' => password_hash('admin123', PASSWORD_DEFAULT),
            'full_name' => 'Administrator',
            'email' => 'admin@alumpro.az',
            'phone' => '+994501234567',
            'role' => 'admin',
            'verified' => 1,
            'store_id' => null
        ];
        $db->insert('users', $adminData);
        echo "Admin user created (username: admin, password: admin123).\n";
    }
    
    // Insert sample sales users
    $stores = $db->select("SELECT id, name FROM stores");
    foreach ($stores as $store) {
        $username = 'sales' . $store['id'];
        $salesExists = $db->selectOne("SELECT id FROM users WHERE username = :username", ['username' => $username]);
        if (!$salesExists) {
            $salesData = [
                'username' => $username,
                'password' => password_hash('sales123', PASSWORD_DEFAULT),
                'full_name' => $store['name'] . ' Satışçısı',
                'email' => $username . '@alumpro.az',
                'phone' => '+99450' . rand(1000000, 9999999),
                'role' => 'sales',
                'store_id' => $store['id'],
                'verified' => 1
            ];
            $db->insert('users', $salesData);
            echo "Sales user created (username: {$username}, password: sales123).\n";
        }
    }
    
    // Insert sample categories
    $categories = [
        ['name' => 'Alüminium Profillər', 'type' => 'profile', 'parent_id' => null],
        ['name' => 'Şüşə Məhsulları', 'type' => 'glass', 'parent_id' => null],
        ['name' => 'Aksesuarlar', 'type' => 'accessory', 'parent_id' => null]
    ];
    
    foreach ($categories as $category) {
        $existing = $db->selectOne("SELECT id FROM categories WHERE name = :name", ['name' => $category['name']]);
        if (!$existing) {
            $db->insert('categories', $category);
            echo "Category '{$category['name']}' added.\n";
        }
    }
    
    // Insert sample products
    $profileCatId = $db->selectOne("SELECT id FROM categories WHERE name = 'Alüminium Profillər'")['id'];
    $glassCatId = $db->selectOne("SELECT id FROM categories WHERE name = 'Şüşə Məhsulları'")['id'];
    $accessoryCatId = $db->selectOne("SELECT id FROM categories WHERE name = 'Aksesuarlar'")['id'];
    
    $products = [
        [
            'code' => 'ALU001',
            'name' => 'Standart Alüminium Profil',
            'category_id' => $profileCatId,
            'type' => 'frame',
            'color' => 'Ağ',
            'unit' => 'meter',
            'size' => '50x30',
            'purchase_price' => 15.00,
            'sale_price' => 25.00
        ],
        [
            'code' => 'ALU002',
            'name' => 'Premium Alüminium Profil',
            'category_id' => $profileCatId,
            'type' => 'frame',
            'color' => 'Qəhvəyi',
            'unit' => 'meter',
            'size' => '60x40',
            'purchase_price' => 25.00,
            'sale_price' => 40.00
        ],
        [
            'code' => 'ACC001',
            'name' => 'Qapı Tutacağı',
            'category_id' => $accessoryCatId,
            'type' => 'handle',
            'color' => 'Gümüş',
            'unit' => 'piece',
            'size' => 'Standart',
            'purchase_price' => 5.00,
            'sale_price' => 12.00
        ]
    ];
    
    foreach ($products as $product) {
        $existing = $db->selectOne("SELECT id FROM products WHERE code = :code", ['code' => $product['code']]);
        if (!$existing) {
            $db->insert('products', $product);
            echo "Product '{$product['name']}' added.\n";
        }
    }
    
    // Insert sample glass products
    $glassProducts = [
        [
            'code' => 'GL001',
            'name' => 'Şəffaf Şüşə',
            'category_id' => $glassCatId,
            'type' => 'clear',
            'color' => 'Şəffaf',
            'height' => 200.00,
            'width' => 100.00,
            'thickness' => 4.00,
            'purchase_price' => 20.00,
            'sale_price' => 35.00
        ],
        [
            'code' => 'GL002',
            'name' => 'Buzlu Şüşə',
            'category_id' => $glassCatId,
            'type' => 'frosted',
            'color' => 'Ağ',
            'height' => 200.00,
            'width' => 100.00,
            'thickness' => 4.00,
            'purchase_price' => 25.00,
            'sale_price' => 45.00
        ]
    ];
    
    foreach ($glassProducts as $glass) {
        $existing = $db->selectOne("SELECT id FROM glass_products WHERE code = :code", ['code' => $glass['code']]);
        if (!$existing) {
            $db->insert('glass_products', $glass);
            echo "Glass product '{$glass['name']}' added.\n";
        }
    }
    
    // Insert sample inventory for both stores
    $stores = $db->select("SELECT id FROM stores");
    $products = $db->select("SELECT id FROM products");
    $glassProducts = $db->select("SELECT id FROM glass_products");
    
    foreach ($stores as $store) {
        foreach ($products as $product) {
            $existing = $db->selectOne("SELECT id FROM inventory WHERE product_id = :product_id AND store_id = :store_id", [
                'product_id' => $product['id'],
                'store_id' => $store['id']
            ]);
            if (!$existing) {
                $db->insert('inventory', [
                    'product_id' => $product['id'],
                    'glass_id' => null,
                    'quantity' => rand(10, 100),
                    'store_id' => $store['id']
                ]);
            }
        }
        
        foreach ($glassProducts as $glass) {
            $existing = $db->selectOne("SELECT id FROM inventory WHERE glass_id = :glass_id AND store_id = :store_id", [
                'glass_id' => $glass['id'],
                'store_id' => $store['id']
            ]);
            if (!$existing) {
                $quantity = rand(5, 50);
                $db->insert('inventory', [
                    'product_id' => null,
                    'glass_id' => $glass['id'],
                    'quantity' => $quantity,
                    'area_sqm' => $quantity * 2.0, // 2 square meters per piece
                    'store_id' => $store['id']
                ]);
            }
        }
    }
    echo "Sample inventory added for all stores.\n";
    
    // Insert sample customers
    $customers = [
        [
            'full_name' => 'Əli Məmmədov',
            'phone' => '+994501234567',
            'email' => 'ali@example.com',
            'address' => 'Bakı, Nəsimi r.'
        ],
        [
            'full_name' => 'Leyla Həsənova',
            'phone' => '+994551234567',
            'email' => 'leyla@example.com',
            'address' => 'Bakı, Yasamal r.'
        ],
        [
            'full_name' => 'Rəşad Quliyev',
            'phone' => '+994701234567',
            'email' => 'rashad@example.com',
            'address' => 'Bakı, Sabunçu r.'
        ]
    ];
    
    foreach ($customers as $customer) {
        $existing = $db->selectOne("SELECT id FROM customers WHERE phone = :phone", ['phone' => $customer['phone']]);
        if (!$existing) {
            $db->insert('customers', $customer);
            echo "Customer '{$customer['full_name']}' added.\n";
        }
    }
    
    $db->commit();
    echo "\nSample data insertion completed successfully!\n";
    echo "\nLogin credentials:\n";
    echo "Admin: username=admin, password=admin123\n";
    echo "Sales Store 1: username=sales1, password=sales123\n";
    echo "Sales Store 2: username=sales2, password=sales123\n";
    
} catch (Exception $e) {
    $db->rollback();
    echo "Error: " . $e->getMessage() . "\n";
}
?>