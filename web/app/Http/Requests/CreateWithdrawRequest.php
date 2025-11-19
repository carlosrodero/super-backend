<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateWithdrawRequest extends FormRequest
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
            'bank_account' => ['required', 'array'],
            'bank_account.bank_code' => ['required', 'string'],
            'bank_account.agency' => ['required', 'string'],
            'bank_account.account' => ['required', 'string'],
            'bank_account.account_type' => ['required', 'string', 'in:CHECKING,SAVINGS'],
            'bank_account.account_holder_name' => ['nullable', 'string', 'max:255'],
            'bank_account.account_holder_document' => ['nullable', 'string'],
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
            'amount.required' => 'O valor do saque é obrigatório.',
            'amount.numeric' => 'O valor do saque deve ser um número.',
            'amount.min' => 'O valor do saque deve ser maior que zero.',
            'bank_account.required' => 'Os dados bancários são obrigatórios.',
            'bank_account.array' => 'Os dados bancários devem ser um objeto.',
            'bank_account.bank_code.required' => 'O código do banco é obrigatório.',
            'bank_account.agency.required' => 'A agência é obrigatória.',
            'bank_account.account.required' => 'A conta é obrigatória.',
            'bank_account.account_type.required' => 'O tipo de conta é obrigatório.',
            'bank_account.account_type.in' => 'O tipo de conta deve ser CHECKING ou SAVINGS.',
        ];
    }
}
