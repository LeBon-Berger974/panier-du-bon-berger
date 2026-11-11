<?php

namespace App\Rules;

use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Throwable;

class DeliveryDayRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! filled($value)) {
            return;
        }

        try {
            $dayOfWeek = CarbonImmutable::parse($value)->dayOfWeek;
        } catch (Throwable) {
            return;
        }

        if (! in_array($dayOfWeek, [CarbonImmutable::WEDNESDAY, CarbonImmutable::FRIDAY], true)) {
            $fail('Choisissez un mercredi ou un vendredi.');
        }
    }
}
