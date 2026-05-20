<?php

namespace App\Http\Requests\FrontEnd;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rules\Password as PasswordRule;

class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token' => 'required|string',
            'email' => 'required|email',
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ];
    }

    /**
     * Keep token in the URL when validation fails (do not use back() to POST URL).
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            redirect()
                ->route('frontend.password.reset', [
                    'token' => $this->input('token'),
                    'email' => $this->input('email'),
                ])
                ->withInput($this->only(['email', 'token']))
                ->withErrors($validator)
        );
    }
}
