<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

final class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        $autorizado = true;

        return $autorizado;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        $reglas = [
            'usuario' => ['required', 'string'],
            'clave' => ['required', 'string'],
            'recordarme' => ['nullable', 'boolean'],
        ];

        return $reglas;
    }
}
