<?php

namespace App\Domains\Master\Models;

use App\Domains\PhoneDirectory\Models\PhoneDirectoryContact;
use App\Support\Traits\HasUppercaseNames;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartyContact extends Model
{
    use HasUppercaseNames;

    protected $fillable = [
        'customer_id',
        'phone_directory_contact_id',
        'designation_id',
        'level',
        'name',
        'role',
        'phone',
        'alternate_phone',
        'whatsapp_number',
        'email',
        'location',
        'is_primary',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function designation(): BelongsTo
    {
        return $this->belongsTo(Designation::class, 'designation_id');
    }

    public function phoneDirectoryContact(): BelongsTo
    {
        return $this->belongsTo(PhoneDirectoryContact::class, 'phone_directory_contact_id');
    }

    public function getEffectiveNameAttribute(): string
    {
        return $this->phoneDirectoryContact?->contact_name ?? $this->name ?? '';
    }

    public function getEffectivePhoneAttribute(): string
    {
        return $this->phoneDirectoryContact?->mobile_number ?? $this->phone ?? '';
    }

    public function getEffectiveAlternatePhoneAttribute(): ?string
    {
        return $this->phoneDirectoryContact?->alternate_mobile ?? $this->alternate_phone;
    }

    public function getEffectiveWhatsappNumberAttribute(): ?string
    {
        return $this->whatsapp_number ?: ($this->phoneDirectoryContact?->whatsapp_number ?: null);
    }

    public function getEffectiveEmailAttribute(): ?string
    {
        return $this->phoneDirectoryContact?->email ?? $this->email;
    }

    public function getEffectiveDesignationAttribute(): string
    {
        return $this->phoneDirectoryContact?->designation
            ?: ($this->phoneDirectoryContact?->designationMaster?->name
            ?? ($this->designation?->name ?? ($this->role ?? '')));
    }

    public function getEffectiveCompanyNameAttribute(): string
    {
        return $this->phoneDirectoryContact?->effective_company_name
            ?: ($this->phoneDirectoryContact?->company_name ?? '');
    }

    public function getEffectiveCompanyWebsiteAttribute(): ?string
    {
        return $this->phoneDirectoryContact?->company_website;
    }
}
