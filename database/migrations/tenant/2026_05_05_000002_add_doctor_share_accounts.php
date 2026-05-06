<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Account;

return new class extends Migration
{
    /**
     * Seed the two accounting accounts required for doctor share settlement entries.
     * These are additive — no existing account is modified.
     *
     * 5350 | Doctor Share Expense  | expense   — DR at settlement time
     * 2250 | Doctor Share Payable  | liability — CR at settlement, DR when paid out
     */
    public function up(): void
    {
        Account::firstOrCreate(
            ['code' => '5350'],
            [
                'name'        => 'Doctor Share Expense',
                'type'        => 'expense',
                'description' => 'Doctor revenue share accrued at settlement',
                'is_system'   => false,
                'is_active'   => true,
            ]
        );

        Account::firstOrCreate(
            ['code' => '2250'],
            [
                'name'        => 'Doctor Share Payable',
                'type'        => 'liability',
                'description' => 'Amount owed to doctors pending disbursement',
                'is_system'   => false,
                'is_active'   => true,
            ]
        );
    }

    public function down(): void
    {
        Account::where('code', '5350')->delete();
        Account::where('code', '2250')->delete();
    }
};
