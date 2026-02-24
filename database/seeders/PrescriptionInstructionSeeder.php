<?php

namespace Database\Seeders;

use App\Models\PrescriptionInstruction;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PrescriptionInstructionSeeder extends Seeder
{
    public function run(): void
    {
        // Truncate table before seeding
        DB::table('prescription_instructions')->truncate();

        $instructions = [
            // FREQUENCY
            ['instruction' => 'روزانہ ایک بار استعمال کریں', 'category' => 'frequency', 'is_active' => true],
            ['instruction' => 'روزانہ دو بار استعمال کریں', 'category' => 'frequency', 'is_active' => true],
            ['instruction' => 'روزانہ تین بار استعمال کریں', 'category' => 'frequency', 'is_active' => true],
            ['instruction' => 'ہر 6 گھنٹے بعد استعمال کریں', 'category' => 'frequency', 'is_active' => true],
            ['instruction' => 'ہر 8 گھنٹے بعد استعمال کریں', 'category' => 'frequency', 'is_active' => true],
            ['instruction' => 'ہر 12 گھنٹے بعد استعمال کریں', 'category' => 'frequency', 'is_active' => true],
            ['instruction' => 'ہفتے میں ایک بار استعمال کریں', 'category' => 'frequency', 'is_active' => true],
            ['instruction' => 'ہر ماہ ایک بار استعمال کریں', 'category' => 'frequency', 'is_active' => true],

            // MEAL
            ['instruction' => 'کھانے سے پہلے استعمال کریں', 'category' => 'meal', 'is_active' => true],
            ['instruction' => 'کھانے کے بعد استعمال کریں', 'category' => 'meal', 'is_active' => true],
            ['instruction' => 'خالی پیٹ استعمال کریں', 'category' => 'meal', 'is_active' => true],
            ['instruction' => 'پانی کے ساتھ استعمال کریں', 'category' => 'meal', 'is_active' => true],
            ['instruction' => 'دودھ کے ساتھ استعمال کریں', 'category' => 'meal', 'is_active' => true],

            // TIME
            ['instruction' => 'صبح استعمال کریں', 'category' => 'time', 'is_active' => true],
            ['instruction' => 'دوپہر میں استعمال کریں', 'category' => 'time', 'is_active' => true],
            ['instruction' => 'رات کو سونے سے پہلے استعمال کریں', 'category' => 'time', 'is_active' => true],
            ['instruction' => 'صبح و شام استعمال کریں', 'category' => 'time', 'is_active' => true],

            // DURATION
            ['instruction' => 'تین دن تک استعمال کریں', 'category' => 'duration', 'is_active' => true],
            ['instruction' => 'پانچ دن تک استعمال کریں', 'category' => 'duration', 'is_active' => true],
            ['instruction' => 'سات دن تک استعمال کریں', 'category' => 'duration', 'is_active' => true],
            ['instruction' => 'دس دن تک استعمال کریں', 'category' => 'duration', 'is_active' => true],
            ['instruction' => 'چودہ دن تک استعمال کریں', 'category' => 'duration', 'is_active' => true],
            ['instruction' => 'ایک ماہ تک استعمال کریں', 'category' => 'duration', 'is_active' => true],
            ['instruction' => 'علامات ختم ہونے تک استعمال کریں', 'category' => 'duration', 'is_active' => true],

            // CONDITIONAL
            ['instruction' => 'درد کی صورت میں استعمال کریں', 'category' => 'conditional', 'is_active' => true],
            ['instruction' => 'بخار کی صورت میں استعمال کریں', 'category' => 'conditional', 'is_active' => true],
            ['instruction' => 'سانس میں تکلیف کی صورت میں استعمال کریں', 'category' => 'conditional', 'is_active' => true],
            ['instruction' => 'ضرورت کے مطابق استعمال کریں', 'category' => 'conditional', 'is_active' => true],

            // INJECTION
            ['instruction' => 'ڈاکٹر کے کلینک میں لگوائیں', 'category' => 'injection', 'is_active' => true],
            ['instruction' => 'جلد کے نیچے لگائیں', 'category' => 'injection', 'is_active' => true],
            ['instruction' => 'پٹھے میں لگائیں', 'category' => 'injection', 'is_active' => true],
            ['instruction' => 'نس میں لگائیں', 'category' => 'injection', 'is_active' => true],
        ];

        foreach ($instructions as $instruction) {
            PrescriptionInstruction::create($instruction);
        }
    }
}
