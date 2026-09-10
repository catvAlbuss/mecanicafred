<?php

namespace App\Concerns;

use App\Models\Supplier;
use Illuminate\Validation\Rule;

trait SupplierValidationRules
{
    /**
     * Get the validation rules for supplier data.
     *
     * @return array<string, array<mixed>>
     */
    protected function supplierRules(?Supplier $supplier = null): array
    {
        return [
            'tax_id' => [
                'required',
                'string',
                'digits_between:8,20',
                Rule::unique('suppliers', 'tax_id')->ignore($supplier),
            ],
            'business_name' => ['required', 'string', 'max:255'],
            'trade_name' => ['nullable', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'secondary_phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email:rfc', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'district' => ['nullable', 'string', 'max:255'],
            'province' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['required', 'boolean'],
            'logo' => [
                'nullable',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'extensions:jpg,jpeg,png,webp',
                'max:2048',
            ],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => [
                'file',
                'mimes:pdf,jpg,jpeg,png,webp',
                'extensions:pdf,jpg,jpeg,png,webp',
                'max:5120',
            ],
        ];
    }

    /**
     * Get the validation messages shown in the supplier form.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tax_id.required' => 'El RUC o documento fiscal es obligatorio.',
            'tax_id.digits_between' => 'El RUC o documento fiscal debe tener entre 8 y 20 dígitos.',
            'tax_id.unique' => 'Ya existe un proveedor con este RUC o documento fiscal.',
            'business_name.required' => 'La razón social es obligatoria.',
            'email.email' => 'Ingresa un correo electrónico válido.',
            'logo.image' => 'El logo debe ser una imagen válida.',
            'logo.mimes' => 'El logo debe ser JPG, PNG o WebP.',
            'logo.extensions' => 'La extensión del logo debe ser JPG, PNG o WebP.',
            'logo.max' => 'El logo no puede superar 2 MB.',
            'attachments.max' => 'Puedes adjuntar como máximo 5 archivos por vez.',
            'attachments.*.mimes' => 'Los adjuntos deben ser PDF, JPG, PNG o WebP.',
            'attachments.*.extensions' => 'La extensión del adjunto debe ser PDF, JPG, PNG o WebP.',
            'attachments.*.max' => 'Cada adjunto no puede superar 5 MB.',
        ];
    }
}
