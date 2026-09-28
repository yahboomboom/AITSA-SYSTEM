<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;

class Recaptcha implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $secret = config('services.recaptcha.secret_key');

        if (! $secret || ! is_string($value) || $value === '') {
            $fail('Please complete the "I\'m not a robot" check.');
            return;
        }

        $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => $secret,
            'response' => $value,
        ]);

        if (! $response->successful() || ! $response->json('success')) {
            $fail('Please complete the "I\'m not a robot" check.');
        }
    }
}
