<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidPackageBarcode implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $length = strlen($value);

        if($length < 22 || $length > 29)
        {
            $fail('The barcode lenght must be between 22 or 29 characters long');
            return;
        }

        if(substr($value, -5, 1) !== "-")
        {
            $fail('The barcode format is invalid');
            return;
        }

        if(substr($value, -8, 1) !== "-")
        {
            $fail('The barcode format is invalid');
            return;
        }

        if(substr($value, 4, 1) !== "/" && substr($value, 4, 1) !== "-")
        {
            $fail('The barcode format is invalid');
            return;
        }
    }
}
