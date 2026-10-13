<?php

namespace App\Http\Requests;

use App\Rules\AcceptableUsername;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSettingsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            // Identitatea publică. `name` (numele real) NU se editează aici:
            // nu apare nicăieri public și nu are de ce să fie schimbat des.
            'username' => [
                'required', 'string', 'min:3', 'max:32', 'alpha_dash',
                Rule::unique('users', 'username')->ignore($this->user()->id),
                new AcceptableUsername,
            ],
            'avatar' => ['nullable', 'string', 'max:8'],

            'profile' => ['nullable', 'string', 'in:real,umanist,arte,sport'],
            'exam_target' => ['nullable', 'string', 'exists:seasons,exam_target'],

            'timezone' => ['required', 'string', 'timezone'],

            // Participarea la clasamente e activă implicit, dar rămâne
            // controlabilă: elevul se poate retrage oricând. În clasamente apare
            // exclusiv `username`, niciodată numele real.
            'leaderboard_opt_in' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'username.alpha_dash' => 'Numele public poate conține doar litere, cifre, liniuțe și underscore.',
            'username.unique' => 'Acest nume public este deja folosit.',
        ];
    }
}
