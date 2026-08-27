<?php

namespace App\Services;

use App\Models\Doctor;
use Illuminate\Support\Carbon;

class DoctorAvailability
{
    public function isAvailableAt(Doctor $doctor, Carbon $datetime): bool
    {
        return $this->failureReason($doctor, $datetime) === null;
    }

    public function failureReason(Doctor $doctor, Carbon $datetime): ?string
    {
        $availableDays = $this->availableDays($doctor);

        if ($availableDays === []) {
            return 'The selected doctor has no available days scheduled.';
        }

        $weekday = $datetime->format('l');

        if (! in_array($weekday, $availableDays, true)) {
            return 'The selected doctor is not available on this day.';
        }

        $appointmentMinutes = $this->timeToMinutes($datetime->format('H:i'));
        $shiftStartMinutes = $this->timeToMinutes((string) $doctor->shift_start);
        $shiftEndMinutes = $this->timeToMinutes((string) $doctor->shift_end);

        if ($appointmentMinutes < $shiftStartMinutes || $appointmentMinutes > $shiftEndMinutes) {
            return "The selected time is outside the doctor's scheduled hours.";
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function availableDays(Doctor $doctor): array
    {
        $days = $doctor->available_days;

        if (! is_array($days)) {
            return [];
        }

        return array_values(array_filter($days, fn ($day) => is_string($day) && $day !== ''));
    }

    private function timeToMinutes(string $time): int
    {
        $formatted = Carbon::parse($time)->format('H:i');
        [$hours, $minutes] = array_map('intval', explode(':', $formatted));

        return ($hours * 60) + $minutes;
    }
}
