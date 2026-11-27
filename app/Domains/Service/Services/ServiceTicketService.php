<?php

namespace App\Domains\Service\Services;

use App\Domains\Communication\Services\CommunicationService;
use App\Domains\Hrms\Models\Employee;
use App\Domains\Inventory\Models\ProductSerial;
use App\Domains\Master\Models\Customer;
use App\Domains\Purchasing\Services\SerialBatchService;
use App\Domains\Sales\Models\Invoice;
use App\Domains\Sales\Models\InvoiceItem;
use App\Domains\Service\Models\ServiceCharge;
use App\Domains\Service\Models\ServiceChargeLine;
use App\Domains\Service\Models\ServiceInstallation;
use App\Domains\Service\Models\ServiceOemCentre;
use App\Domains\Service\Models\ServiceSlaPolicy;
use App\Domains\Service\Models\ServiceTicket;
use App\Domains\Service\Models\ServiceTicketActivity;
use App\Domains\Service\Models\ServiceTicketAssignment;
use App\Domains\Service\Models\ServiceTicketAttachment;
use App\Domains\Service\Models\ServiceVisit;
use App\Domains\Service\Models\ServiceWarranty;
use App\Models\User;
use App\Support\AuditLogService;
use App\Support\DocumentNumberService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class ServiceTicketService
{
    public function __construct(
        protected DocumentNumberService $documentNumberService,
        protected AuditLogService $auditLogService,
        protected SerialBatchService $serialBatchService,
    ) {}

    public function create(array $data, User $actor): ServiceTicket
    {
        return DB::transaction(function () use ($data, $actor) {
            $serialHint = $this->resolveSerial($data['serial_no'] ?? null);
            $companyId = $data['company_id']
                ?? $serialHint['customer']?->company_id
                ?? $actor->company_id
                ?? null;

            $ticket = ServiceTicket::create([
                'ticket_no' => $this->documentNumberService->nextDocument('SRV', now(), 4, $companyId),
                'company_id' => $companyId,
                'branch_id' => $data['branch_id'] ?? null,
                'request_type' => $data['request_type'],
                'party_type' => $data['party_type'] ?? 'end_user',
                'customer_id' => $data['customer_id'] ?? $serialHint['customer']?->id,
                'customer_name' => $data['customer_name'] ?? $serialHint['customer']?->name ?? 'Unknown',
                'contact_person' => $data['contact_person'] ?? null,
                'contact_mobile' => $data['contact_mobile'] ?? null,
                'alt_contact_person' => $data['alt_contact_person'] ?? null,
                'alt_contact_mobile' => $data['alt_contact_mobile'] ?? null,
                'billing_address' => $data['billing_address'] ?? null,
                'site_address' => $data['site_address'] ?? null,
                'brand' => $data['brand'] ?? $serialHint['product']?->brand?->name,
                'product_category' => $data['product_category'] ?? null,
                'model_number' => $data['model_number'] ?? $serialHint['product']?->name,
                'product_id' => $data['product_id'] ?? $serialHint['product']?->id,
                'serial_no' => $data['serial_no'] ?? null,
                'product_serial_id' => $serialHint['serial']?->id,
                'invoice_no' => $data['invoice_no'] ?? $serialHint['invoice']?->invoice_no,
                'invoice_date' => $data['invoice_date'] ?? $serialHint['invoice']?->invoice_date,
                'warranty_status' => $data['warranty_status'] ?? $serialHint['warranty_status'],
                'warranty_start' => $data['warranty_start'] ?? $serialHint['serial']?->warranty_start_on,
                'warranty_expiry' => $data['warranty_expiry'] ?? $serialHint['serial']?->warranty_expires_on,
                'problem_description' => $data['problem_description'] ?? null,
                'priority' => $data['priority'] ?? 'normal',
                'status' => 'new',
                'handler_type' => $data['handler_type'] ?? 'avit',
                'oem_centre_id' => $data['oem_centre_id'] ?? null,
                'expected_visit_at' => $data['expected_visit_at'] ?? null,
                'sla_due_at' => $this->computeSlaDue($data['priority'] ?? 'normal', $companyId),
                'created_by' => $actor->id,
            ]);

            if (($data['request_type'] ?? '') === 'installation') {
                ServiceInstallation::create([
                    'service_ticket_id' => $ticket->id,
                    'installation_type' => $data['installation_type'] ?? 'free',
                    'project_name' => $data['project_name'] ?? null,
                    'site_address' => $data['site_address'] ?? $ticket->site_address,
                    'quotation_amount' => $data['quotation_amount'] ?? null,
                    'installation_charges' => $data['installation_charges'] ?? null,
                    'material_consumed' => $data['material_consumed'] ?? null,
                ]);
            }

            ServiceCharge::create([
                'service_ticket_id' => $ticket->id,
                'is_paid_service' => (bool) ($data['is_paid_service'] ?? false),
                'payment_status' => empty($data['is_paid_service']) ? 'waived' : 'pending',
            ]);

            if (! empty($data['warranty_claim_no']) || ! empty($data['oem_ticket_no']) || ! empty($data['oem_centre_id'])) {
                ServiceWarranty::create([
                    'service_ticket_id' => $ticket->id,
                    'oem_centre_id' => $data['oem_centre_id'] ?? null,
                    'warranty_claim_no' => $data['warranty_claim_no'] ?? null,
                    'oem_ticket_no' => $data['oem_ticket_no'] ?? null,
                    'parts_covered' => $data['parts_covered'] ?? null,
                    'chargeable' => (bool) ($data['warranty_chargeable'] ?? false),
                    'notes' => $data['warranty_notes'] ?? null,
                ]);
            }

            $this->logActivity($ticket, 'created', null, 'new', 'Ticket created', $actor);
            $this->auditLogService->record($ticket, 'created', $actor->name);
            $this->notifyWhatsApp($ticket, 'created');

            return $ticket->fresh([
                'customer', 'assignedEmployee', 'installation', 'charges', 'warranty', 'oemCentre',
            ]);
        });
    }

    public function update(ServiceTicket $ticket, array $data, User $actor): ServiceTicket
    {
        return DB::transaction(function () use ($ticket, $data, $actor) {
            $locked = ServiceTicket::query()->whereKey($ticket->id)->lockForUpdate()->firstOrFail();
            if (in_array($locked->status, ['closed', 'cancelled'], true)) {
                throw new InvalidArgumentException('Closed or cancelled tickets cannot be edited.');
            }

            $locked->update(collect($data)->only([
                'party_type', 'customer_id', 'customer_name', 'contact_person', 'contact_mobile',
                'alt_contact_person', 'alt_contact_mobile', 'billing_address', 'site_address',
                'brand', 'product_category', 'model_number', 'product_id', 'serial_no',
                'invoice_no', 'invoice_date', 'warranty_status', 'warranty_start', 'warranty_expiry',
                'problem_description', 'priority', 'handler_type', 'oem_centre_id', 'expected_visit_at',
            ])->filter(fn ($v) => $v !== null)->all());

            if (isset($data['priority'])) {
                $locked->update([
                    'sla_due_at' => $this->computeSlaDue($data['priority'], $locked->company_id),
                ]);
            }

            $this->logActivity($locked, 'updated', $locked->status, $locked->status, 'Ticket details updated', $actor);

            return $locked->fresh(['customer', 'assignedEmployee', 'installation', 'charges', 'warranty']);
        });
    }

    public function assign(ServiceTicket $ticket, array $data, User $actor): ServiceTicket
    {
        return DB::transaction(function () use ($ticket, $data, $actor) {
            $locked = ServiceTicket::query()->whereKey($ticket->id)->lockForUpdate()->firstOrFail();
            if (! $locked->isOpen()) {
                throw new InvalidArgumentException('Cannot assign a closed ticket.');
            }

            $employeeId = $data['employee_id'] ?? null;
            $oemId = $data['oem_centre_id'] ?? null;
            if (! $employeeId && ! $oemId) {
                throw new InvalidArgumentException('Select a technician or OEM centre.');
            }

            if ($employeeId) {
                Employee::query()->findOrFail($employeeId);
            }
            if ($oemId) {
                ServiceOemCentre::query()->findOrFail($oemId);
            }

            $from = $locked->status;
            $handler = $employeeId && $oemId ? 'both' : ($oemId ? 'oem' : 'avit');

            $locked->update([
                'assigned_employee_id' => $employeeId,
                'oem_centre_id' => $oemId ?: $locked->oem_centre_id,
                'handler_type' => $handler,
                'status' => $from === 'new' ? 'assigned' : $from,
                'expected_visit_at' => $data['expected_visit_at'] ?? $locked->expected_visit_at,
            ]);

            ServiceTicketAssignment::create([
                'service_ticket_id' => $locked->id,
                'employee_id' => $employeeId,
                'oem_centre_id' => $oemId,
                'notes' => $data['notes'] ?? null,
                'assigned_by' => $actor->id,
            ]);

            $this->logActivity($locked, 'assigned', $from, $locked->status, $data['notes'] ?? 'Assigned', $actor);
            $this->notifyWhatsApp($locked, 'assigned');

            return $locked->fresh(['assignedEmployee', 'oemCentre', 'assignments']);
        });
    }

    public function transition(ServiceTicket $ticket, string $toStatus, User $actor, ?string $notes = null): ServiceTicket
    {
        return DB::transaction(function () use ($ticket, $toStatus, $actor, $notes) {
            $locked = ServiceTicket::query()->with(['installation', 'charges', 'visits'])->whereKey($ticket->id)->lockForUpdate()->firstOrFail();
            $from = $locked->status;

            if (! in_array($toStatus, ServiceTicket::STATUSES, true)) {
                throw new InvalidArgumentException('Invalid status.');
            }

            $this->assertTransitionAllowed($locked, $toStatus);

            $updates = ['status' => $toStatus];
            if ($toStatus === 'completed') {
                $updates['completed_at'] = now();
                if ($notes) {
                    $updates['completion_remarks'] = $notes;
                    $updates['completion_status'] = 'completed';
                }
            }
            if ($toStatus === 'confirmed') {
                $updates['confirmed_at'] = now();
            }
            if ($toStatus === 'closed') {
                $updates['closed_at'] = now();
            }

            $locked->update($updates);
            $this->logActivity($locked, 'status_changed', $from, $toStatus, $notes, $actor);
            $this->notifyWhatsApp($locked, $toStatus);

            return $locked->fresh(['assignedEmployee', 'installation', 'charges', 'visits', 'warranty']);
        });
    }

    public function addVisit(ServiceTicket $ticket, array $data, User $actor): ServiceVisit
    {
        return DB::transaction(function () use ($ticket, $data, $actor) {
            $locked = ServiceTicket::query()->whereKey($ticket->id)->lockForUpdate()->firstOrFail();
            if (! $locked->isOpen()) {
                throw new InvalidArgumentException('Cannot add visits to a closed ticket.');
            }

            $visit = ServiceVisit::create([
                'service_ticket_id' => $locked->id,
                'employee_id' => $data['employee_id'] ?? $locked->assigned_employee_id,
                'visited_at' => $data['visited_at'] ?? now(),
                'customer_complaint' => $data['customer_complaint'] ?? $locked->problem_description,
                'problem_identified' => $data['problem_identified'] ?? null,
                'action_taken' => $data['action_taken'] ?? null,
                'parts_replaced' => $data['parts_replaced'] ?? null,
                'before_photo_path' => $data['before_photo_path'] ?? null,
                'after_photo_path' => $data['after_photo_path'] ?? null,
                'customer_signature' => $data['customer_signature'] ?? null,
                'next_visit_required' => (bool) ($data['next_visit_required'] ?? false),
                'next_visit_at' => $data['next_visit_at'] ?? null,
                'created_by' => $actor->id,
            ]);

            if (in_array($locked->status, ['new', 'assigned'], true)) {
                $locked->update(['status' => 'visit']);
            }

            $this->logActivity($locked, 'visit_logged', $locked->status, $locked->status, 'Visit report added', $actor);

            return $visit;
        });
    }

    public function updateInstallation(ServiceTicket $ticket, array $data, User $actor): ServiceInstallation
    {
        return DB::transaction(function () use ($ticket, $data, $actor) {
            if ($ticket->request_type !== 'installation') {
                throw new InvalidArgumentException('Installation details only apply to installation tickets.');
            }

            $install = ServiceInstallation::query()->firstOrCreate(
                ['service_ticket_id' => $ticket->id],
                ['installation_type' => 'free']
            );

            $payload = collect($data)->only([
                'installation_type', 'project_name', 'site_address', 'quotation_amount',
                'installation_charges', 'material_consumed', 'completion_certificate_path',
                'customer_signature',
            ])->all();

            if (! empty($data['customer_approved'])) {
                $payload['customer_approved'] = true;
                $payload['customer_approved_at'] = now();
            }
            if (! empty($data['customer_acknowledged'])) {
                $payload['customer_acknowledged'] = true;
                $payload['customer_acknowledged_at'] = now();
            }

            $install->update($payload);
            $this->logActivity($ticket, 'installation_updated', $ticket->status, $ticket->status, 'Installation details updated', $actor);

            return $install->fresh();
        });
    }

    public function updateCharges(ServiceTicket $ticket, array $data, User $actor): ServiceCharge
    {
        return DB::transaction(function () use ($ticket, $data, $actor) {
            $charge = ServiceCharge::query()->firstOrCreate(
                ['service_ticket_id' => $ticket->id],
                ['is_paid_service' => false, 'payment_status' => 'pending']
            );

            $charge->update([
                'is_paid_service' => (bool) ($data['is_paid_service'] ?? $charge->is_paid_service),
                'technician_charges' => $data['technician_charges'] ?? $charge->technician_charges,
                'transportation_charges' => $data['transportation_charges'] ?? $charge->transportation_charges,
                'payment_status' => $data['payment_status'] ?? $charge->payment_status,
                'paid_amount' => $data['paid_amount'] ?? $charge->paid_amount,
            ]);

            if (array_key_exists('lines', $data) && is_array($data['lines'])) {
                $charge->lines()->delete();
                foreach ($data['lines'] as $line) {
                    if (blank($line['item_name'] ?? null)) {
                        continue;
                    }
                    $qty = (float) ($line['quantity'] ?? 1);
                    $rate = (float) ($line['rate'] ?? 0);
                    ServiceChargeLine::create([
                        'service_charge_id' => $charge->id,
                        'line_type' => $line['line_type'] ?? 'spare',
                        'item_name' => $line['item_name'],
                        'quantity' => $qty,
                        'rate' => $rate,
                        'amount' => round($qty * $rate, 2),
                    ]);
                }
            }

            if (! empty($data['customer_approved'])) {
                $charge->update([
                    'customer_approved' => true,
                    'customer_approved_at' => now(),
                ]);
            }

            $charge->recalculateTotals();
            $this->logActivity($ticket, 'charges_updated', $ticket->status, $ticket->status, 'Charges updated', $actor);

            return $charge->fresh('lines');
        });
    }

    public function updateWarranty(ServiceTicket $ticket, array $data, User $actor): ServiceWarranty
    {
        return DB::transaction(function () use ($ticket, $data, $actor) {
            $warranty = ServiceWarranty::query()->firstOrCreate(
                ['service_ticket_id' => $ticket->id],
                ['oem_status' => 'pending']
            );

            $warranty->update(collect($data)->only([
                'oem_centre_id', 'warranty_claim_no', 'oem_ticket_no', 'oem_status',
                'parts_covered', 'chargeable', 'notes',
            ])->all());

            if (! empty($data['oem_centre_id'])) {
                $ticket->update([
                    'oem_centre_id' => $data['oem_centre_id'],
                    'handler_type' => $ticket->assigned_employee_id ? 'both' : 'oem',
                ]);
            }

            $this->logActivity($ticket, 'warranty_updated', $ticket->status, $ticket->status, 'Warranty / OEM updated', $actor);

            return $warranty->fresh('oemCentre');
        });
    }

    public function storeAttachment(ServiceTicket $ticket, UploadedFile $file, string $kind, User $actor): ServiceTicketAttachment
    {
        $path = $file->store('service-tickets/'.$ticket->id, 'public');

        $attachment = ServiceTicketAttachment::create([
            'service_ticket_id' => $ticket->id,
            'kind' => $kind,
            'disk' => 'public',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'uploaded_by' => $actor->id,
        ]);

        $this->logActivity($ticket, 'attachment', $ticket->status, $ticket->status, 'Uploaded '.$kind, $actor);

        return $attachment;
    }

    public function submitFeedback(ServiceTicket $ticket, int $rating, ?string $comments = null): ServiceTicket
    {
        if ($ticket->status !== 'closed') {
            throw new InvalidArgumentException('Feedback is only accepted after closure.');
        }
        if ($rating < 1 || $rating > 5) {
            throw new InvalidArgumentException('Rating must be 1–5.');
        }

        $ticket->update([
            'feedback_rating' => $rating,
            'feedback_comments' => $comments,
            'feedback_at' => now(),
        ]);

        return $ticket->fresh();
    }

    /**
     * Create a draft tax invoice from chargeable service charges (Phase 4).
     */
    public function createServiceInvoice(ServiceTicket $ticket, User $actor): Invoice
    {
        return DB::transaction(function () use ($ticket, $actor) {
            $ticket->loadMissing(['charges.lines', 'customer']);
            if (! $ticket->customer_id) {
                throw new InvalidArgumentException('Link a customer master before creating a service invoice.');
            }
            $charges = $ticket->charges;
            if (! $charges || ! $charges->is_paid_service) {
                throw new InvalidArgumentException('Only chargeable tickets can generate a service invoice.');
            }
            if (! $charges->customer_approved) {
                throw new InvalidArgumentException('Customer must approve charges before invoicing.');
            }
            if ($ticket->invoice_id) {
                throw new InvalidArgumentException('A service invoice is already linked to this ticket.');
            }

            $amount = (float) $charges->grand_total;
            if ($amount <= 0) {
                throw new InvalidArgumentException('Chargeable amount must be greater than zero.');
            }

            $companyId = $ticket->company_id
                ?? $ticket->customer?->company_id
                ?? null;

            $invoice = Invoice::create([
                'invoice_no' => app(\App\Domains\Sales\Services\InvoiceNumberGenerator::class)->generate('SRVINV', null, $companyId),
                'customer_id' => $ticket->customer_id,
                'company_id' => $companyId,
                'invoice_date' => now()->toDateString(),
                'due_date' => now()->toDateString(),
                'status' => 'draft',
                'subtotal' => $amount,
                'taxable_amount' => $amount,
                'tax_amount' => 0,
                'grand_total' => $amount,
                'paid_amount' => 0,
                'notes' => 'Service ticket '.$ticket->ticket_no,
                'reference_no' => $ticket->ticket_no,
            ]);

            if (! $ticket->company_id && $companyId) {
                $ticket->update(['company_id' => $companyId]);
            }

            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'product_id' => $ticket->product_id,
                'is_general' => ! $ticket->product_id,
                'description' => 'Service / '.$ticket->request_type.' — '.$ticket->ticket_no,
                'quantity' => 1,
                'unit_price' => $amount,
                'taxable_amount' => $amount,
                'tax_amount' => 0,
                'line_total' => $amount,
            ]);

            $ticket->update(['invoice_id' => $invoice->id]);
            $this->logActivity($ticket, 'invoice_created', $ticket->status, $ticket->status, 'Invoice '.$invoice->invoice_no, $actor);

            return $invoice;
        });
    }

    public function escalateOverdue(?int $limit = 100): int
    {
        $count = 0;
        ServiceTicket::query()
            ->whereNotIn('status', ['closed', 'cancelled'])
            ->whereNotNull('sla_due_at')
            ->where('sla_due_at', '<', now())
            ->whereNull('sla_escalated_at')
            ->orderBy('id')
            ->limit($limit ?? 100)
            ->each(function (ServiceTicket $ticket) use (&$count) {
                $ticket->update(['sla_escalated_at' => now()]);
                $this->logActivity($ticket, 'sla_escalated', $ticket->status, $ticket->status, 'SLA overdue — escalated', null);
                $count++;
            });

        return $count;
    }

    protected function assertTransitionAllowed(ServiceTicket $ticket, string $toStatus): void
    {
        if ($toStatus === 'cancelled') {
            return;
        }

        if ($toStatus === 'closed') {
            if (blank($ticket->completion_remarks) && $ticket->visits->isEmpty()) {
                throw new InvalidArgumentException('Cannot close without technician remarks or a visit report.');
            }
            if (blank($ticket->completion_status) && $ticket->visits->isEmpty()) {
                throw new InvalidArgumentException('Cannot close without completion status.');
            }
            if ($ticket->request_type === 'installation') {
                $install = $ticket->installation;
                if (! $install || ! $install->customer_acknowledged) {
                    throw new InvalidArgumentException('Installation tickets require customer acknowledgement before closure.');
                }
            }
            if ($ticket->charges?->is_paid_service && ! $ticket->charges->customer_approved) {
                throw new InvalidArgumentException('Chargeable work requires customer approval before closure.');
            }
        }

        if (in_array($toStatus, ['pending', 'completed', 'confirmed'], true)
            && $ticket->charges?->is_paid_service
            && ! $ticket->charges->customer_approved
            && in_array($ticket->status, ['visit', 'pending'], true) === false
            && $toStatus === 'completed'
        ) {
            // Allow visit/pending freely; block completed without approval for paid work.
            throw new InvalidArgumentException('Chargeable work cannot be marked completed without customer approval.');
        }
    }

    protected function computeSlaDue(string $priority, ?int $companyId): \Carbon\Carbon
    {
        $policy = ServiceSlaPolicy::query()
            ->where('is_active', true)
            ->where('priority', $priority)
            ->where(function ($q) use ($companyId) {
                $q->whereNull('company_id');
                if ($companyId) {
                    $q->orWhere('company_id', $companyId);
                }
            })
            ->orderByRaw('company_id is null')
            ->first();

        $hours = (int) ($policy?->resolution_hours ?? match ($priority) {
            'critical' => 24,
            'high' => 48,
            'low' => 168,
            default => 72,
        });

        return now()->addHours($hours);
    }

    /**
     * @return array{serial: ?ProductSerial, product: mixed, customer: ?Customer, invoice: mixed, warranty_status: string}
     */
    protected function resolveSerial(?string $serialNo): array
    {
        $empty = [
            'serial' => null,
            'product' => null,
            'customer' => null,
            'invoice' => null,
            'warranty_status' => 'unknown',
        ];

        if (blank($serialNo)) {
            return $empty;
        }

        $serial = $this->serialBatchService->findSerial(trim($serialNo))
            ?: ProductSerial::query()->where('serial_number', trim($serialNo))->first();

        if (! $serial) {
            return $empty;
        }

        $serial->loadMissing(['product.brand', 'customer', 'salesInvoice']);
        $warranty = 'unknown';
        if (method_exists($serial, 'warrantyStatus')) {
            $status = strtolower((string) $serial->warrantyStatus());
            $warranty = str_contains($status, 'out') ? 'out_of_warranty'
                : (str_contains($status, 'in') || str_contains($status, 'active') ? 'in_warranty' : 'unknown');
        }

        return [
            'serial' => $serial,
            'product' => $serial->product,
            'customer' => $serial->customer,
            'invoice' => $serial->salesInvoice,
            'warranty_status' => $warranty,
        ];
    }

    protected function logActivity(
        ServiceTicket $ticket,
        string $event,
        ?string $from,
        ?string $to,
        ?string $notes,
        ?User $actor
    ): void {
        ServiceTicketActivity::create([
            'service_ticket_id' => $ticket->id,
            'event' => $event,
            'from_status' => $from,
            'to_status' => $to,
            'notes' => $notes,
            'user_id' => $actor?->id,
        ]);
    }

    protected function notifyWhatsApp(ServiceTicket $ticket, string $event): void
    {
        $mobile = $ticket->contact_mobile;
        if (blank($mobile)) {
            return;
        }

        try {
            $message = match ($event) {
                'created' => "Service ticket {$ticket->ticket_no} registered. We will contact you shortly.",
                'assigned' => "Ticket {$ticket->ticket_no} assigned. Expected visit: ".($ticket->expected_visit_at?->format('d M Y H:i') ?: 'TBA'),
                'visit' => "Technician visit logged for ticket {$ticket->ticket_no}.",
                'completed' => "Work completed on ticket {$ticket->ticket_no}. Awaiting confirmation.",
                'closed' => "Ticket {$ticket->ticket_no} closed. Thank you.",
                default => "Ticket {$ticket->ticket_no} status: {$ticket->status}.",
            };

            if (method_exists(CommunicationService::class, 'sendWhatsappRaw')) {
                app(CommunicationService::class)->sendWhatsappRaw($mobile, $message);
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
