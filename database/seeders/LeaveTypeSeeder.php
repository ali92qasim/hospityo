<?php

namespace Database\Seeders;

use App\Models\LeaveType;
use Illuminate\Database\Seeder;

class LeaveTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'Annual Leave', 'code' => 'AL', 'default_days' => 14, 'is_paid' => true, 'is_carry_forward' => true, 'max_carry_forward_days' => 7, 'requires_document' => false, 'description' => 'Yearly paid leave entitlement'],
            ['name' => 'Sick Leave', 'code' => 'SL', 'default_days' => 10, 'is_paid' => true, 'is_carry_forward' => false, 'max_carry_forward_days' => 0, 'requires_document' => true, 'description' => 'Medical leave — requires medical certificate for 3+ days'],
            ['name' => 'Casual Leave', 'code' => 'CL', 'default_days' => 10, 'is_paid' => true, 'is_carry_forward' => false, 'max_carry_forward_days' => 0, 'requires_document' => false, 'description' => 'Short notice leave for personal matters'],
            ['name' => 'Maternity Leave', 'code' => 'ML', 'default_days' => 90, 'is_paid' => true, 'is_carry_forward' => false, 'max_carry_forward_days' => 0, 'requires_document' => true, 'description' => 'Maternity leave as per labor law'],
            ['name' => 'Paternity Leave', 'code' => 'PL', 'default_days' => 7, 'is_paid' => true, 'is_carry_forward' => false, 'max_carry_forward_days' => 0, 'requires_document' => false, 'description' => 'Leave for new fathers'],
            ['name' => 'Unpaid Leave', 'code' => 'UL', 'default_days' => 30, 'is_paid' => false, 'is_carry_forward' => false, 'max_carry_forward_days' => 0, 'requires_document' => false, 'description' => 'Leave without pay'],
            ['name' => 'Hajj Leave', 'code' => 'HL', 'default_days' => 21, 'is_paid' => true, 'is_carry_forward' => false, 'max_carry_forward_days' => 0, 'requires_document' => true, 'description' => 'Once in service for Hajj pilgrimage'],
        ];

        foreach ($types as $data) {
            LeaveType::firstOrCreate(['code' => $data['code']], $data);
        }
    }
}
