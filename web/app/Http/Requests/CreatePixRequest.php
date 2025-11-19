<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreatePixRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Autorização será feita pelo middleware auth:sanctum
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payer_name' => ['nullable', 'string', 'max:255'],
            'payer_cpf' => ['nullable', 'string', 'regex:/^\d{11}$/'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'amount.required' => 'O valor do PIX é obrigatório.',
            'amount.numeric' => 'O valor do PIX deve ser um número.',
            'amount.min' => 'O valor do PIX deve ser maior que zero.',
            'payer_name.max' => 'O nome do pagador não pode ter mais de 255 caracteres.',
            'payer_cpf.regex' => 'O CPF deve conter exatamente 11 dígitos numéricos.',
        ];
    }
}
