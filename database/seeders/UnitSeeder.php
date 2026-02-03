<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Unit;

class UnitSeeder extends Seeder
{
    public function run()
    {
        // Base units (conversion_factor = 1)
        $piece = Unit::create([
            'name' => 'Piece',
            'abbreviation' => 'pc',
            'base_unit_id' => null,
            'conversion_factor' => 1,
            'type' => 'solid',
            'is_active' => true
        ]);

        $milliliter = Unit::create([
            'name' => 'Milliliter',
            'abbreviation' => 'ml',
            'base_unit_id' => null,
            'conversion_factor' => 1,
            'type' => 'liquid',
            'is_active' => true
        ]);

        $gram = Unit::create([
            'name' => 'Gram',
            'abbreviation' => 'g',
            'base_unit_id' => null,
            'conversion_factor' => 1,
            'type' => 'solid',
            'is_active' => true
        ]);

        // Packaging units for pieces (tablets, capsules, etc.)
        Unit::create([
            'name' => 'Strip',
            'abbreviation' => 'strip',
            'base_unit_id' => $piece->id,
            'conversion_factor' => 10,
            'type' => 'packaging',
            'is_active' => true
        ]);

        Unit::create([
            'name' => 'Box',
            'abbreviation' => 'box',
            'base_unit_id' => $piece->id,
            'conversion_factor' => 100,
            'type' => 'packaging',
            'is_active' => true
        ]);

        Unit::create([
            'name' => 'Bottle',
            'abbreviation' => 'bottle',
            'base_unit_id' => $piece->id,
            'conversion_factor' => 30,
            'type' => 'packaging',
            'is_active' => true
        ]);

        // Liquid packaging units
        Unit::create([
            'name' => 'Bottle 60ml',
            'abbreviation' => 'btl-60ml',
            'base_unit_id' => $milliliter->id,
            'conversion_factor' => 60,
            'type' => 'packaging',
            'is_active' => true
        ]);

        Unit::create([
            'name' => 'Bottle 100ml',
            'abbreviation' => 'btl-100ml',
            'base_unit_id' => $milliliter->id,
            'conversion_factor' => 100,
            'type' => 'packaging',
            'is_active' => true
        ]);

        Unit::create([
            'name' => 'Bottle 200ml',
            'abbreviation' => 'btl-200ml',
            'base_unit_id' => $milliliter->id,
            'conversion_factor' => 200,
            'type' => 'packaging',
            'is_active' => true
        ]);

        Unit::create([
            'name' => 'Vial 5ml',
            'abbreviation' => 'vial-5ml',
            'base_unit_id' => $milliliter->id,
            'conversion_factor' => 5,
            'type' => 'packaging',
            'is_active' => true
        ]);

        Unit::create([
            'name' => 'Vial 10ml',
            'abbreviation' => 'vial-10ml',
            'base_unit_id' => $milliliter->id,
            'conversion_factor' => 10,
            'type' => 'packaging',
            'is_active' => true
        ]);

        // Topical/cream packaging
        Unit::create([
            'name' => 'Tube 15g',
            'abbreviation' => 'tube-15g',
            'base_unit_id' => $gram->id,
            'conversion_factor' => 15,
            'type' => 'packaging',
            'is_active' => true
        ]);

        Unit::create([
            'name' => 'Tube 30g',
            'abbreviation' => 'tube-30g',
            'base_unit_id' => $gram->id,
            'conversion_factor' => 30,
            'type' => 'packaging',
            'is_active' => true
        ]);

        // Injectable units
        Unit::create([
            'name' => 'Ampoule 2ml',
            'abbreviation' => 'amp-2ml',
            'base_unit_id' => $milliliter->id,
            'conversion_factor' => 2,
            'type' => 'packaging',
            'is_active' => true
        ]);

        Unit::create([
            'name' => 'Ampoule 5ml',
            'abbreviation' => 'amp-5ml',
            'base_unit_id' => $milliliter->id,
            'conversion_factor' => 5,
            'type' => 'packaging',
            'is_active' => true
        ]);
    }
}