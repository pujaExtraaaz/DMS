<?php

namespace App\Domains\Service\Models;

use App\Domains\Hrms\Models\Employee;
use App\Domains\Inventory\Models\ProductSerial;
use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\Product;
use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\Company;
use App\Domains\Sales\Models\Invoice;
use App\Models\User;
use App\Support\Traits\HasUppercaseNames;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ServiceTicket extends Model
{
    use HasUppercaseNames;

    public const STATUSES = [
        'new', 'assigned', 'visit', 'pending', 'completed', 'confirmed', 'closed', 'cancelled',
    ];

    public const REQUEST_TYPES = ['complaint', 'installation', 'service'];

    public const PRIORITIES = ['critical', 'high', 'normal', 'low'];

    protected $fillable = [
        'ticket_no', 'company_id', 'branch_id', 'request_type', 'party_type', 'customer_id', 'customer_name',
        'contact_person', 'contact_mobile', 'alt_contact_person', 'alt_contact_mobile',
        'billing_address', 'site_address', 'brand', 'product_category', 'model_number', 'product_id',
        'serial_no', 'product_serial_id', 'invoice_no', 'invoice_date', 'warranty_status',
        'warranty_start', 'warranty_expiry', 'problem_description', 'priority', 'status',
        'handler_type', 'assigned_employee_id', 'oem_centre_id', 'expected_visit_at', 'sla_due_at',
        'sla_escalated_at', 'completion_remarks', 'completion_status', 'completed_at', 'confirmed_at',
        'closed_at', 'feedback_rating', 'feedback_comments', 'feedback_at', 'invoice_id', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'warranty_start' => 'date',
            'warranty_expiry' => 'date',
            'expected_visit_at' => 'datetime',
            'sla_due_at' => 'datetime',
            'sla_escalated_at' => 'datetime',
            'completed_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'closed_at' => 'datetime',
            'feedback_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productSerial(): BelongsTo
    {
        return $this->belongsTo(ProductSerial::class);
    }

    public function assignedEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'assigned_employee_id');
    }

    public function oemCentre(): BelongsTo
    {
        return $this->belongsTo(ServiceOemCentre::class, 'oem_centre_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function salesInvoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ServiceTicketAttachment::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ServiceTicketAssignment::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(ServiceTicketActivity::class)->orderByDesc('id');
    }

    public function visits(): HasMany
    {
        return $this->hasMany(ServiceVisit::class)->orderByDesc('id');
    }

    public function installation(): HasOne
    {
        return $this->hasOne(ServiceInstallation::class);
    }

    public function charges(): HasOne
    {
        return $this->hasOne(ServiceCharge::class);
    }

    public function warranty(): HasOne
    {
        return $this->hasOne(ServiceWarranty::class);
    }

    public function isOpen(): bool
    {
        return ! in_array($this->status, ['closed', 'cancelled'], true);
    }

    public function isOverdue(): bool
    {
        return $this->isOpen()
            && $this->sla_due_at
            && $this->sla_due_at->isPast();
    }

    public function requestTypeLabel(): string
    {
        return ucfirst($this->request_type);
    }

    public function statusLabel(): string
    {
        return ucfirst(str_replace('_', ' ', $this->status));
    }
}
