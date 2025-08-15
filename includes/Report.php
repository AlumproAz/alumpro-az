            AND DATE(o.order_date) BETWEEN :date_from AND :date_to
            AND o.status != 'cancelled'
            GROUP BY u.id
            ORDER BY total_sales DESC
        ", [
            'date_from' => $dateFrom,
            'date_to' => $dateTo
        ]);
    }
    
    public function getFinancialReport($month, $year) {
        $startDate = "$year-$month-01";
        $endDate = date('Y-m-t', strtotime($startDate));
        
        return [
            'revenue' => $this->getRevenue($startDate, $endDate),
            'expenses' => $this->getExpenses($startDate, $endDate),
            'profit' => $this->getProfit($startDate, $endDate),
            'payments' => $this->getPayments($startDate, $endDate),
            'outstanding' => $this->getOutstandingPayments()
        ];
    }
    
    public function getRevenue($dateFrom, $dateTo) {
        return $this->db->selectOne("
            SELECT 
                COALESCE(SUM(grand_total), 0) as total_revenue,
                COALESCE(SUM(discount_amount), 0) as total_discount,
                COALESCE(SUM(tax_amount), 0) as total_tax
            FROM orders
            WHERE DATE(order_date) BETWEEN :date_from AND :date_to
            AND status NOT IN ('cancelled')
        ", [
            'date_from' => $dateFrom,
            'date_to' => $dateTo
        ]);
    }
    
    public function getExpenses($dateFrom, $dateTo) {
        return $this->db->select("
            SELECT 
                expense_type,
                SUM(amount) as total_amount,
                COUNT(*) as count
            FROM expenses
            WHERE DATE(expense_date) BETWEEN :date_from AND :date_to
            GROUP BY expense_type
        ", [
            'date_from' => $dateFrom,
            'date_to' => $dateTo
        ]);
    }
    
    public function getProfit($dateFrom, $dateTo) {
        $revenue = $this->getRevenue($dateFrom, $dateTo);
        $expenses = $this->db->selectOne("
            SELECT COALESCE(SUM(amount), 0) as total
            FROM expenses
            WHERE DATE(expense_date) BETWEEN :date_from AND :date_to
        ", [
            'date_from' => $dateFrom,
            'date_to' => $dateTo
        ]);
        
        return $revenue['total_revenue'] - $expenses['total'];
    }
    
    public function getPayments($dateFrom, $dateTo) {
        return $this->db->select("
            SELECT 
                payment_method,
                COUNT(*) as count,
                SUM(amount) as total_amount
            FROM payments
            WHERE DATE(payment_date) BETWEEN :date_from AND :date_to
            GROUP BY payment_method
        ", [
            'date_from' => $dateFrom,
            'date_to' => $dateTo
        ]);
    }
    
    public function getOutstandingPayments() {
        return $this->db->selectOne("
            SELECT 
                COUNT(*) as count,
                COALESCE(SUM(grand_total - paid_amount), 0) as total_outstanding
            FROM orders
            WHERE payment_status IN ('pending', 'partial')
            AND status != 'cancelled'
        ");
    }
    
    public function exportToExcel($reportType, $data) {
        require_once '../vendor/autoload.php';
        
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Set headers based on report type
        switch ($reportType) {
            case 'sales':
                $headers = ['Tarix', 'Sifariş Sayı', 'Cəmi Satış', 'Ortalama', 'Endirim'];
                break;
            case 'inventory':
                $headers = ['Məhsul', 'Kod', 'Kateqoriya', 'Miqdar', 'Alış Qiyməti', 'Satış Qiyməti', 'Cəmi Dəyər'];
                break;
            case 'customer':
                $headers = ['Müştəri', 'Telefon', 'Email', 'Sifariş Sayı', 'Cəmi Xərclənən', 'Son Sifariş'];
                break;
            default:
                $headers = array_keys($data[0] ?? []);
        }
        
        // Write headers
        foreach ($headers as $col => $header) {
            $sheet->setCellValueByColumnAndRow($col + 1, 1, $header);
        }
        
        // Write data
        $row = 2;
        foreach ($data as $item) {
            $col = 1;
            foreach ($item as $value) {
                $sheet->setCellValueByColumnAndRow($col, $row, $value);
                $col++;
            }
            $row++;
        }
        
        // Style the header row
        $sheet->getStyle('A1:' . $sheet->getHighestColumn() . '1')
            ->getFont()->setBold(true);
        
        // Auto-size columns
        foreach (range('A', $sheet->getHighestColumn()) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        // Save file
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $fileName = 'report_' . $reportType . '_' . date('Y-m-d') . '.xlsx';
        $filePath = '../uploads/reports/' . $fileName;
        $writer->save($filePath);
        
        return $filePath;
    }
    
    public function generatePDFReport($reportType, $data, $params = []) {
        require_once '../vendor/autoload.php';
        
        $html = $this->generateReportHTML($reportType, $data, $params);
        
        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        
        $fileName = 'report_' . $reportType . '_' . date('Y-m-d') . '.pdf';
        $filePath = '../uploads/reports/' . $fileName;
        file_put_contents($filePath, $dompdf->output());
        
        return $filePath;
    }
    
    private function generateReportHTML($reportType, $data, $params) {
        $html = '<!DOCTYPE html><html><head>';
        $html .= '<meta charset="UTF-8">';
        $html .= '<style>
            body { font-family: Arial, sans-serif; }
            table { width: 100%; border-collapse: collapse; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #1a936f; color: white; }
            .header { text-align: center; margin-bottom: 20px; }
            .footer { margin-top: 20px; text-align: center; font-size: 12px; }
        </style>';
        $html .= '</head><body>';
        
        // Header
        $html .= '<div class="header">';
        $html .= '<h1>Alumpro.Az - ' . ucfirst($reportType) . ' Hesabatı</h1>';
        $html .= '<p>Tarix: ' . date('d.m.Y H:i') . '</p>';
        if (isset($params['date_from']) && isset($params['date_to'])) {
            $html .= '<p>Dövr: ' . $params['date_from'] . ' - ' . $params['date_to'] . '</p>';
        }
        $html .= '</div>';
        
        // Table
        $html .= '<table>';
        
        // Headers
        if (!empty($data)) {
            $html .= '<tr>';
            foreach (array_keys($data[0]) as $key) {
                $html .= '<th>' . htmlspecialchars($key) . '</th>';
            }
            $html .= '</tr>';
            
            // Data rows
            foreach ($data as $row) {
                $html .= '<tr>';
                foreach ($row as $value) {
                    $html .= '<td>' . htmlspecialchars($value) . '</td>';
                }
                $html .= '</tr>';
            }
        }
        
        $html .= '</table>';
        
        // Footer
        $html .= '<div class="footer">';
        $html .= '<p>© ' . date('Y') . ' Alumpro.Az - Bütün hüquqlar qorunur</p>';
        $html .= '</div>';
        
        $html .= '</body></html>';
        
        return $html;
    }
}