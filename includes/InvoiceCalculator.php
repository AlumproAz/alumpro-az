<?php
/**
 * Invoice Calculation Service
 * Implements the calculation algorithm based on the provided sample
 */
class InvoiceCalculator {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Calculate invoice totals for glass and profile items
     */
    public function calculateInvoice($items) {
        $results = [
            'subtotal' => 0,
            'labor_cost' => 0,
            'installation_cost' => 0,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'grand_total' => 0,
            'item_details' => []
        ];
        
        foreach ($items as $item) {
            $itemTotal = 0;
            $itemDetail = [
                'id' => $item['id'],
                'type' => $item['type'], // 'profile' or 'glass'
                'name' => $item['name'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'total' => 0
            ];
            
            if ($item['type'] === 'profile') {
                // Profile calculation: quantity (meters) × unit price
                $itemTotal = $item['quantity'] * $item['unit_price'];
                $itemDetail['unit'] = 'metr';
                
            } elseif ($item['type'] === 'glass') {
                // Glass calculation: area (height × width × quantity) × unit price per m²
                $height = $item['height'] / 1000; // Convert mm to meters
                $width = $item['width'] / 1000;   // Convert mm to meters
                $area = $height * $width * $item['quantity'];
                
                $itemTotal = $area * $item['unit_price'];
                $itemDetail['height'] = $item['height'];
                $itemDetail['width'] = $item['width'];
                $itemDetail['area'] = $area;
                $itemDetail['unit'] = 'm²';
                
            } elseif ($item['type'] === 'accessory') {
                // Accessory calculation: quantity × unit price
                $itemTotal = $item['quantity'] * $item['unit_price'];
                $itemDetail['unit'] = 'ədəd';
            }
            
            $itemDetail['total'] = $itemTotal;
            $results['item_details'][] = $itemDetail;
            $results['subtotal'] += $itemTotal;
        }
        
        // Calculate labor cost (15% of subtotal for complex items)
        $results['labor_cost'] = $this->calculateLaborCost($items, $results['subtotal']);
        
        // Calculate installation cost (if requested)
        $results['installation_cost'] = $this->calculateInstallationCost($items);
        
        // Calculate discount
        $results['discount_amount'] = $this->calculateDiscount($results['subtotal'], $items);
        
        // Calculate tax (18% VAT on subtotal + labor + installation - discount)
        $taxableAmount = $results['subtotal'] + $results['labor_cost'] + $results['installation_cost'] - $results['discount_amount'];
        $results['tax_amount'] = $taxableAmount * 0.18;
        
        // Calculate grand total
        $results['grand_total'] = $results['subtotal'] + $results['labor_cost'] + $results['installation_cost'] + $results['tax_amount'] - $results['discount_amount'];
        
        return $results;
    }
    
    /**
     * Calculate labor cost based on item complexity
     */
    private function calculateLaborCost($items, $subtotal) {
        $laborRate = 0;
        
        foreach ($items as $item) {
            if ($item['type'] === 'glass') {
                $laborRate = max($laborRate, 0.12); // 12% for glass work
            } elseif ($item['type'] === 'profile') {
                $laborRate = max($laborRate, 0.08); // 8% for profile work
            }
        }
        
        return $subtotal * $laborRate;
    }
    
    /**
     * Calculate installation cost
     */
    private function calculateInstallationCost($items) {
        $installationCost = 0;
        
        foreach ($items as $item) {
            if (isset($item['requires_installation']) && $item['requires_installation']) {
                if ($item['type'] === 'glass') {
                    // Glass installation: 50 AZN per m²
                    $height = $item['height'] / 1000;
                    $width = $item['width'] / 1000;
                    $area = $height * $width * $item['quantity'];
                    $installationCost += $area * 50;
                    
                } elseif ($item['type'] === 'profile') {
                    // Profile installation: 25 AZN per meter
                    $installationCost += $item['quantity'] * 25;
                }
            }
        }
        
        return $installationCost;
    }
    
    /**
     * Calculate discount based on order value and customer level
     */
    private function calculateDiscount($subtotal, $items) {
        $discount = 0;
        
        // Volume discount
        if ($subtotal >= 5000) {
            $discount = $subtotal * 0.10; // 10% for orders over 5000 AZN
        } elseif ($subtotal >= 2000) {
            $discount = $subtotal * 0.05; // 5% for orders over 2000 AZN
        } elseif ($subtotal >= 1000) {
            $discount = $subtotal * 0.03; // 3% for orders over 1000 AZN
        }
        
        return $discount;
    }
    
    /**
     * Calculate material cost for glass cutting
     */
    public function calculateGlassCuttingCost($height, $width, $thickness, $glassType) {
        // Convert mm to meters
        $heightM = $height / 1000;
        $widthM = $width / 1000;
        $area = $heightM * $widthM;
        
        // Base prices per m² by glass type
        $glassPrices = [
            'clear' => 45,      // Clear glass
            'tinted' => 55,     // Tinted glass
            'laminated' => 85,  // Laminated glass
            'tempered' => 95,   // Tempered glass
            'double' => 120     // Double glazing
        ];
        
        $basePrice = $glassPrices[$glassType] ?? 45;
        
        // Thickness multiplier
        $thicknessMultiplier = 1;
        if ($thickness >= 10) {
            $thicknessMultiplier = 1.5;
        } elseif ($thickness >= 8) {
            $thicknessMultiplier = 1.3;
        } elseif ($thickness >= 6) {
            $thicknessMultiplier = 1.2;
        }
        
        // Cutting complexity (smaller pieces cost more per m²)
        $complexityMultiplier = 1;
        if ($area < 0.5) {
            $complexityMultiplier = 1.5; // Small pieces
        } elseif ($area < 1) {
            $complexityMultiplier = 1.2; // Medium pieces
        }
        
        $totalPrice = $area * $basePrice * $thicknessMultiplier * $complexityMultiplier;
        
        // Minimum charge for any glass piece
        return max($totalPrice, 20);
    }
    
    /**
     * Calculate profile cutting and processing cost
     */
    public function calculateProfileCost($length, $profileType, $color) {
        // Base prices per meter by profile type
        $profilePrices = [
            'standard' => 25,   // Standard profile
            'premium' => 35,    // Premium profile
            'custom' => 45      // Custom profile
        ];
        
        $basePrice = $profilePrices[$profileType] ?? 25;
        
        // Color multiplier
        $colorMultiplier = 1;
        if ($color === 'custom') {
            $colorMultiplier = 1.4;
        } elseif ($color === 'anodized') {
            $colorMultiplier = 1.2;
        }
        
        $totalPrice = $length * $basePrice * $colorMultiplier;
        
        // Minimum charge for any profile
        return max($totalPrice, 15);
    }
    
    /**
     * Generate detailed invoice breakdown
     */
    public function generateInvoiceBreakdown($calculation) {
        $breakdown = [
            'sections' => [
                [
                    'title' => 'Məhsullar',
                    'items' => $calculation['item_details'],
                    'total' => $calculation['subtotal']
                ]
            ],
            'summary' => []
        ];
        
        // Add subtotal
        $breakdown['summary'][] = [
            'label' => 'Ara cəm',
            'amount' => $calculation['subtotal'],
            'type' => 'subtotal'
        ];
        
        // Add labor cost if any
        if ($calculation['labor_cost'] > 0) {
            $breakdown['summary'][] = [
                'label' => 'İşçilik',
                'amount' => $calculation['labor_cost'],
                'type' => 'labor'
            ];
        }
        
        // Add installation cost if any
        if ($calculation['installation_cost'] > 0) {
            $breakdown['summary'][] = [
                'label' => 'Quraşdırma',
                'amount' => $calculation['installation_cost'],
                'type' => 'installation'
            ];
        }
        
        // Add discount if any
        if ($calculation['discount_amount'] > 0) {
            $breakdown['summary'][] = [
                'label' => 'Endirim',
                'amount' => -$calculation['discount_amount'],
                'type' => 'discount'
            ];
        }
        
        // Add tax
        $breakdown['summary'][] = [
            'label' => 'ƏDV (18%)',
            'amount' => $calculation['tax_amount'],
            'type' => 'tax'
        ];
        
        // Add grand total
        $breakdown['summary'][] = [
            'label' => 'Yekun məbləğ',
            'amount' => $calculation['grand_total'],
            'type' => 'total'
        ];
        
        return $breakdown;
    }
}
?>