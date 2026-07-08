<?php

namespace Database\Seeders;

use App\Models\Shift;
use Illuminate\Database\Seeder;

class ShiftSeeder extends Seeder
{
    public function run(): void
    {
        $shifts = [
            ['name' => 'Morning', 'code' => 'MOR', 'start_time' => '08:00', 'end_time' => '16:00', 'break_duration' => 1, 'working_hours' => 7, 'grace_minutes' => 15, 'color' => '#F59E0B', 'is_overnight' => false],
            ['name' => 'Evening', 'code' => 'EVE', 'start_time' => '16:00', 'end_time' => '00:00', 'break_duration' => 1, 'working_hours' => 7, 'grace_minutes' => 15, 'color' => '#8B5CF6', 'is_overnight' => false],
            ['name' => 'Night', 'code' => 'NGT', 'start_time' => '00:00', 'end_time' => '08:00', 'break_duration' => 1, 'working_hours' => 7, 'grace_minutes' => 15, 'color' => '#1E40AF', 'is_overnight' => true],
            ['name' => 'General', 'code' => 'GEN', 'start_time' => '09:00', 'end_time' => '17:00', 'break_duration' => 1, 'working_hours' => 7, 'grace_minutes' => 15, 'color' => '#10B981', 'is_overnight' => false],
            // Combined / extended duty options
            ['name' => 'Morning + Evening', 'code' => 'MOR_EVE', 'start_time' => '08:00', 'end_time' => '00:00', 'break_duration' => 2, 'working_hours' => 14, 'grace_minutes' => 15, 'color' => '#F97316', 'is_overnight' => false],
            ['name' => 'Evening + Night', 'code' => 'EVE_NGT', 'start_time' => '16:00', 'end_time' => '08:00', 'break_duration' => 2, 'working_hours' => 14, 'grace_minutes' => 15, 'color' => '#7C3AED', 'is_overnight' => true],
            ['name' => '24 Hours', 'code' => 'H24', 'start_time' => '08:00', 'end_time' => '08:00', 'break_duration' => 2, 'working_hours' => 22, 'grace_minutes' => 15, 'color' => '#0EA5E9', 'is_overnight' => true],
        ];

        foreach ($shifts as $data) {
            Shift::firstOrCreate(['code' => $data['code']], $data);
        }
    }
}
