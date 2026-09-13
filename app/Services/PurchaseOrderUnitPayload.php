<?php

namespace App\Services;

use Illuminate\Support\Collection;

class PurchaseOrderUnitPayload
{
    /**
     * @param  array<string, array{base_unit_id: int|null}>  $medicines
     * @param  list<array{id: int, abbreviation: string, name: string, base_unit_id: int}>  $allUnits
     */
    public function __construct(
        public readonly array $medicines,
        public readonly array $allUnits,
    ) {}

    public static function make(Collection $medicines, Collection $units): self
    {
        $medicineMap = $medicines->mapWithKeys(fn ($medicine) => [
            (string) $medicine->id => [
                'base_unit_id' => self::intId($medicine->base_unit_id),
            ],
        ])->all();

        $allUnits = $units->map(fn ($unit) => [
            'id' => self::intId($unit->id),
            'abbreviation' => $unit->abbreviation,
            'name' => $unit->name,
            'base_unit_id' => self::intId($unit->base_unit_id ?? $unit->id),
        ])->values()->all();

        return new self($medicineMap, $allUnits);
    }

    /**
     * @return list<array{id: int, abbreviation: string, name: string, base_unit_id: int}>
     */
    public function unitsFor(string|int $medicineId): array
    {
        $medicine = $this->medicines[(string) $medicineId] ?? null;

        if ($medicine === null || $medicine['base_unit_id'] === null) {
            return [];
        }

        $baseUnitId = $medicine['base_unit_id'];

        return array_values(array_filter(
            $this->allUnits,
            fn (array $unit) => $unit['base_unit_id'] === $baseUnitId || $unit['id'] === $baseUnitId
        ));
    }

    private static function intId(mixed $id): ?int
    {
        if ($id === null || $id === '') {
            return null;
        }

        return (int) $id;
    }
}
