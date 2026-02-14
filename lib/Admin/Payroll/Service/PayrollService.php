<?php
declare(strict_types=1);

namespace OCA\TimeclockManager\Admin\Payroll\Service;

use OCA\TimeclockManager\Admin\Payroll\Db\PayrollMapper;

class PayrollService {
    private $mapper;

    public function __construct(PayrollMapper $mapper) {
        $this->mapper = $mapper;
    }

    public function getSettings(): array {
        $settings = [];
        foreach($this->mapper->getSettings() as $row) { 
            $settings[$row['setting_key']] = $row['setting_value']; 
        }
        return $settings;
    }

    public function saveSettings(array $params): void {
        $keys = ['pay_frequency', 'pay_start_date', 'pay_date_1', 'pay_date_2', 'pay_color'];
        foreach ($keys as $k) {
            if (isset($params[$k])) {
                $this->mapper->saveSetting($k, (string)$params[$k]);
            }
        }
    }
}