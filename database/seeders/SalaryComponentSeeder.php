<?php

namespace Database\Seeders;

use App\Models\SalaryComponent;
use Illuminate\Database\Seeder;

class SalaryComponentSeeder extends Seeder
{
    public function run(): void
    {
        $components = [
            // Allowances
            ['name' => 'House Rent Allowance', 'code' => 'HRA', 'type' => 'allowance', 'calculation' => 'percentage', 'default_amount' => 45, 'percentage_of' => 'basic_salary', 'is_taxable' => true, 'sort_order' => 1],
            ['name' => 'Medical Allowance', 'code' => 'MED', 'type' => 'allowance', 'calculation' => 'fixed', 'default_amount' => 3000, 'is_taxable' => false, 'sort_order' => 2],
            ['name' => 'Conveyance Allowance', 'code' => 'CONV', 'type' => 'allowance', 'calculation' => 'fixed', 'default_amount' => 2500, 'is_taxable' => false, 'sort_order' => 3],
            ['name' => 'Utility Allowance', 'code' => 'UTIL', 'type' => 'allowance', 'calculation' => 'percentage', 'default_amount' => 10, 'percentage_of' => 'basic_salary', 'is_taxable' => true, 'sort_order' => 4],

            // Deductions
            ['name' => 'EOBI', 'code' => 'EOBI', 'type' => 'deduction', 'calculation' => 'percentage', 'default_amount' => 1, 'percentage_of' => 'basic_salary', 'is_taxable' => false, 'sort_order' => 1],
            ['name' => 'Provident Fund', 'code' => 'PF', 'type' => 'deduction', 'calculation' => 'percentage', 'default_amount' => 8.33, 'percentage_of' => 'basic_salary', 'is_taxable' => false, 'sort_order' => 2],
            ['name' => 'Professional Tax', 'code' => 'PTAX', 'type' => 'deduction', 'calculation' => 'fixed', 'default_amount' => 200, 'is_taxable' => false, 'sort_order' => 3],
        ];

        foreach ($components as $data) {
            SalaryComponent::firstOrCreate(['code' => $data['code']], $data);
        }
    }
}
