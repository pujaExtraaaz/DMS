<?php

namespace App\Domains\PhoneDirectory\Models;

use App\Domains\Master\Models\CustomerType;
use App\Domains\Master\Models\Designation;
use App\Domains\Organization\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PhoneDirectoryContact extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'phone_directory_contacts';

    protected $fillable = [
        'contact_name',
        'profile_photo',
        'contact_type',
        'designation',
        'designation_id',
        'department_id',
        'company_id',
        'customer_type_id',
        'company_name',
        'company_website',
        'category_id',
        'mobile_number',
        'alternate_mobile',
        'office_phone',
        'extension_number',
        'whatsapp_number',
        'email',
        'alternate_email',
        'country',
        'state',
        'city',
        'area',
        'address',
        'pincode',
        'is_important',
        'status',
        'notes',
        'created_by',
        'updated_by',
        'archived_at',
    ];

    protected $casts = [
        'is_important' => 'boolean',
        'archived_at' => 'datetime',
    ];

    public const CONTACT_TYPES = [
        'employee' => 'Employee',
        'customer' => 'Customer',
        'supplier' => 'Supplier',
        'dealer' => 'Dealer',
        'distributor' => 'Distributor',
        'business_partner' => 'Business Partner',
        'service_provider' => 'Service Provider',
        'management' => 'Management',
        'government_official' => 'Government / Official',
        'other' => 'Other',
    ];

    public function designationMaster(): BelongsTo
    {
        return $this->belongsTo(Designation::class, 'designation_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(PhoneDirectoryDepartment::class, 'department_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(PhoneDirectoryCategory::class, 'category_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function classification(): BelongsTo
    {
        return $this->belongsTo(CustomerType::class, 'customer_type_id');
    }

    public function customerType(): BelongsTo
    {
        return $this->belongsTo(CustomerType::class, 'customer_type_id');
    }

    public function partyContacts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Domains\Master\Models\PartyContact::class, 'phone_directory_contact_id');
    }

    public function parties(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(\App\Domains\Master\Models\Customer::class, 'party_contacts', 'phone_directory_contact_id', 'customer_id')
            ->withPivot(['id', 'is_primary', 'level', 'role', 'designation_id', 'phone', 'email'])
            ->withTimestamps();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Check if contact is archived.
     */
    public function isArchived(): bool
    {
        return ! is_null($this->archived_at);
    }

    /**
     * Get initials for avatar (e.g. John Doe -> JD).
     */
    public function getInitialsAttribute(): string
    {
        $words = preg_split('/\s+/', trim($this->contact_name ?? ''));
        $initials = '';
        foreach ($words as $word) {
            if (! empty($word)) {
                $initials .= mb_substr($word, 0, 1);
            }
            if (mb_strlen($initials) >= 2) {
                break;
            }
        }

        return strtoupper($initials ?: 'C');
    }

    /**
     * Deterministic pastel avatar background color based on name.
     */
    public function getAvatarBgClassAttribute(): string
    {
        $colors = [
            'bg-violet-100 text-violet-700 border-violet-200',
            'bg-indigo-100 text-indigo-700 border-indigo-200',
            'bg-purple-100 text-purple-700 border-purple-200',
            'bg-fuchsia-100 text-fuchsia-700 border-fuchsia-200',
            'bg-sky-100 text-sky-700 border-sky-200',
            'bg-blue-100 text-blue-700 border-blue-200',
            'bg-emerald-100 text-emerald-700 border-emerald-200',
            'bg-teal-100 text-teal-700 border-teal-200',
            'bg-amber-100 text-amber-700 border-amber-200',
            'bg-rose-100 text-rose-700 border-rose-200',
        ];

        $hash = crc32($this->contact_name ?? 'Contact') % count($colors);

        return $colors[abs($hash)];
    }

    /**
     * Get profile photo URL or null.
     */
    public function getProfilePhotoUrlAttribute(): ?string
    {
        if (empty($this->profile_photo)) {
            return null;
        }

        if (Str::startsWith($this->profile_photo, ['http://', 'https://'])) {
            return $this->profile_photo;
        }

        return Storage::disk('public')->url($this->profile_photo);
    }

    /**
     * Sanitized primary mobile number for tel: links.
     */
    public function getCleanMobileAttribute(): string
    {
        return preg_replace('/[^\d+]/', '', $this->mobile_number ?? '');
    }

    /**
     * Sanitized whatsapp number for wa.me links.
     */
    public function getCleanWhatsappAttribute(): string
    {
        $raw = $this->whatsapp_number ?: $this->mobile_number;
        $clean = preg_replace('/\D/', '', $raw ?? '');

        // If 10 digits Indian number without country code, prefix 91
        if (strlen($clean) === 10) {
            $clean = '91'.$clean;
        }

        return $clean;
    }

    /**
     * Formatted location string (City, State).
     */
    public function getFormattedLocationAttribute(): string
    {
        $parts = array_filter([$this->city, $this->state]);

        return implode(', ', $parts) ?: '—';
    }

    /**
     * Get effective company name (from master relationship or fallback to text).
     */
    public function getEffectiveCompanyNameAttribute(): string
    {
        return $this->company?->name ?? $this->company_name ?? '';
    }

    /**
     * Get party classification name.
     */
    public function getClassificationNameAttribute(): string
    {
        return $this->classification?->name ?? '';
    }

    /**
     * Scopes
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('archived_at')->where('status', 'active');
    }

    public function scopeArchived(Builder $query): Builder
    {
        return $query->whereNotNull('archived_at');
    }

    public function scopeImportant(Builder $query): Builder
    {
        return $query->where('is_important', true);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (empty($term)) {
            return $query;
        }

        $term = trim($term);

        $prefix = str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $term).'%';

        return $query->where(function (Builder $q) use ($prefix) {
            $q->where('contact_name', 'like', $prefix)
                ->orWhere('mobile_number', 'like', $prefix)
                ->orWhere('alternate_mobile', 'like', $prefix)
                ->orWhere('office_phone', 'like', $prefix)
                ->orWhere('email', 'like', $prefix)
                ->orWhere('alternate_email', 'like', $prefix)
                ->orWhere('designation', 'like', $prefix)
                ->orWhere('company_name', 'like', $prefix)
                ->orWhere('city', 'like', $prefix)
                ->orWhere('state', 'like', $prefix)
                ->orWhere('area', 'like', $prefix)
                ->orWhere('pincode', 'like', $prefix)
                ->orWhereHas('department', function ($deptQuery) use ($prefix) {
                    $deptQuery->where('name', 'like', $prefix);
                })
                ->orWhereHas('category', function ($catQuery) use ($prefix) {
                    $catQuery->where('name', 'like', $prefix);
                })
                ->orWhereHas('company', function ($compQuery) use ($prefix) {
                    $compQuery->where('name', 'like', $prefix);
                })
                ->orWhereHas('classification', function ($classQuery) use ($prefix) {
                    $classQuery->where('name', 'like', $prefix);
                });
        });
    }

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        // Global search
        if (! empty($filters['search'])) {
            $query->search($filters['search']);
        }

        // Contact Type
        if (! empty($filters['contact_type'])) {
            $query->where('contact_type', $filters['contact_type']);
        }

        // Department
        if (! empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }

        // Designation
        if (! empty($filters['designation'])) {
            $query->where('designation', 'like', "%{$filters['designation']}%");
        }

        // Company (ID or Name)
        if (! empty($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }
        if (! empty($filters['company_name'])) {
            $term = $filters['company_name'];
            $query->where(function (Builder $q) use ($term) {
                $q->where('company_name', 'like', "%{$term}%")
                    ->orWhereHas('company', function (Builder $cq) use ($term) {
                        $cq->where('name', 'like', "%{$term}%");
                    });
            });
        }

        // Classification
        $classificationId = $filters['classification_id'] ?? $filters['customer_type_id'] ?? null;
        if (! empty($classificationId)) {
            $query->where('customer_type_id', $classificationId);
        }

        // State
        if (! empty($filters['state'])) {
            $query->where('state', $filters['state']);
        }

        // City
        if (! empty($filters['city'])) {
            $query->where('city', 'like', "%{$filters['city']}%");
        }

        // Category
        if (! empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        // Important
        if (isset($filters['important']) && $filters['important'] !== '' && $filters['important'] !== null) {
            $val = filter_var($filters['important'], FILTER_VALIDATE_BOOLEAN);
            if ($val) {
                $query->where('is_important', true);
            }
        }

        // Status also covers archive: active | inactive | archived | all | (empty = current non-archived)
        $status = (string) ($filters['status'] ?? '');
        if ($status === 'archived') {
            $query->whereNotNull('archived_at');
        } elseif ($status === 'all') {
            // No archive/status restriction.
        } elseif ($status === 'active' || $status === 'inactive') {
            $query->whereNull('archived_at')->where('status', $status);
        } else {
            $query->whereNull('archived_at');
        }

        return $query;
    }
}
