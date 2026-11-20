<?php

namespace App\Http\Requests\PhoneDirectory;

use Illuminate\Foundation\Http\FormRequest;

class StoreContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && (
            auth()->user()->hasAnyRole(['super-admin', 'client-admin']) ||
            auth()->user()->hasAnyPermission(['phone_directory.create', 'phone_directory.manage'])
        );
    }

    public function rules(): array
    {
        return [
            'contact_name' => ['required', 'string', 'max:255'],
            'profile_photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:3072'],
            'contact_type' => ['required', 'string', 'max:50'],
            'designation' => ['nullable', 'string', 'max:255'],
            'designation_id' => ['nullable', 'integer', 'exists:designations,id'],
            'department_id' => ['nullable', 'exists:phone_directory_departments,id'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'category_id' => ['nullable', 'exists:phone_directory_categories,id'],
            'mobile_number' => ['required', 'string', 'max:30'],
            'alternate_mobile' => ['nullable', 'string', 'max:30'],
            'office_phone' => ['nullable', 'string', 'max:30'],
            'extension_number' => ['nullable', 'string', 'max:20'],
            'whatsapp_number' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'alternate_email' => ['nullable', 'email', 'max:255'],
            'country' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'area' => ['nullable', 'string', 'max:150'],
            'address' => ['nullable', 'string', 'max:1000'],
            'pincode' => ['nullable', 'string', 'max:20'],
            'is_important' => ['nullable', 'boolean'],
            'status' => ['nullable', 'in:active,inactive'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'contact_name.required' => 'The contact name is required.',
            'mobile_number.required' => 'The primary mobile number is required.',
            'contact_type.required' => 'Please select a contact type.',
            'profile_photo.image' => 'The profile photo must be an image file (jpeg, png, webp).',
            'profile_photo.max' => 'The profile photo may not be greater than 3MB.',
        ];
    }
}
