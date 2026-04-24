<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\FiscalYear;
use Illuminate\Database\Seeder;

class ChartOfAccountsSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            // ASSETS (1xxx)
            ['code' => '1000', 'name' => 'Current Assets', 'type' => 'asset', 'is_system' => true],
            ['code' => '1100', 'name' => 'Cash in Hand', 'type' => 'asset', 'is_system' => true],
            ['code' => '1110', 'name' => 'Bank Account', 'type' => 'asset', 'is_system' => true],
            ['code' => '1200', 'name' => 'Accounts Receivable', 'type' => 'asset', 'is_system' => true],
            ['code' => '1210', 'name' => 'Insurance Receivable', 'type' => 'asset', 'is_system' => true],
            ['code' => '1300', 'name' => 'Inventory (Pharmacy)', 'type' => 'asset', 'is_system' => true],
            ['code' => '1400', 'name' => 'Input Tax Receivable', 'type' => 'asset', 'is_system' => true],

            // LIABILITIES (2xxx)
            ['code' => '2000', 'name' => 'Current Liabilities', 'type' => 'liability', 'is_system' => true],
            ['code' => '2100', 'name' => 'Tax Payable', 'type' => 'liability', 'is_system' => true],
            ['code' => '2200', 'name' => 'Accounts Payable', 'type' => 'liability', 'is_system' => true],
            ['code' => '2300', 'name' => 'Advance from Patients', 'type' => 'liability', 'is_system' => true],

            // EQUITY (3xxx)
            ['code' => '3000', 'name' => 'Owner\'s Equity', 'type' => 'equity', 'is_system' => true],
            ['code' => '3100', 'name' => 'Retained Earnings', 'type' => 'equity', 'is_system' => true],

            // REVENUE (4xxx)
            ['code' => '4000', 'name' => 'Revenue', 'type' => 'revenue', 'is_system' => true],
            ['code' => '4100', 'name' => 'OPD Revenue', 'type' => 'revenue', 'is_system' => true],
            ['code' => '4200', 'name' => 'IPD Revenue', 'type' => 'revenue', 'is_system' => true],
            ['code' => '4300', 'name' => 'Investigation Revenue', 'type' => 'revenue', 'is_system' => true],
            ['code' => '4400', 'name' => 'Pharmacy Revenue', 'type' => 'revenue', 'is_system' => true],
            ['code' => '4500', 'name' => 'Emergency Revenue', 'type' => 'revenue', 'is_system' => true],

            // EXPENSES (5xxx)
            ['code' => '5000', 'name' => 'Operating Expenses', 'type' => 'expense', 'is_system' => true],
            ['code' => '5100', 'name' => 'Cost of Goods Sold (Pharmacy)', 'type' => 'expense', 'is_system' => true],
            ['code' => '5200', 'name' => 'Discounts Given', 'type' => 'expense', 'is_system' => true],
            ['code' => '5300', 'name' => 'Salaries & Wages', 'type' => 'expense', 'is_system' => false],
            ['code' => '5400', 'name' => 'Utilities', 'type' => 'expense', 'is_system' => false],
            ['code' => '5500', 'name' => 'Rent', 'type' => 'expense', 'is_system' => false],
            ['code' => '5600', 'name' => 'Medical Supplies', 'type' => 'expense', 'is_system' => false],
            ['code' => '5700', 'name' => 'Maintenance & Repairs', 'type' => 'expense', 'is_system' => false],
            ['code' => '5800', 'name' => 'Miscellaneous Expenses', 'type' => 'expense', 'is_system' => false],
        ];

        foreach ($accounts as $data) {
            Account::firstOrCreate(['code' => $data['code']], $data);
        }

        // Create default fiscal year
        FiscalYear::firstOrCreate(
            ['name' => 'FY ' . date('Y') . '-' . (date('Y') + 1)],
            [
                'start_date' => date('Y') . '-07-01',
                'end_date' => (date('Y') + 1) . '-06-30',
                'is_active' => true,
            ]
        );
    }
}
