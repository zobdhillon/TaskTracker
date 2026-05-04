<?php

namespace App\Enums;

enum TaskFrequency: string
{
    case Daily = 'daily';
    case Weekdays = 'weekdays';
    case Weekly = 'weekly';
    case Monthly = 'monthly';

    public function buildConfig(array $data): ?array
    {
        return match ($this) {
            self::Weekly => isset($data['days']) ? ['days' => $data['days']] : null,
            self::Monthly => isset($data['day_of_month']) ? ['day_of_month' => (int) $data['day_of_month']] : null,
            default => null
        };
    }
}
