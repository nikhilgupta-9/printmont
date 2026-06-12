<?php
// controllers/ReportController.php
require_once 'models/Report.php';

class ReportController
{
    private $report;
    
    public function __construct()
    {
        $this->report = new Report(); // No parameters needed
    }
    
    public function getSalesStatistics($date_from = null, $date_to = null)
    {
        return $this->report->getSalesStatistics($date_from, $date_to);
    }
    
    public function getPaymentMethodStatistics($date_from = null, $date_to = null)
    {
        return $this->report->getPaymentMethodStatistics($date_from, $date_to);
    }
    
    public function getMonthlySalesData()
    {
        return $this->report->getMonthlySalesData();
    }
}
?>