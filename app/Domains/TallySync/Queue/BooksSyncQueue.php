<?php

namespace App\Domains\TallySync\Queue;

use App\Domains\Tally\Models\TallySyncMapping;
use App\Domains\Tally\Models\TallySyncQueue;
use App\Domains\Tally\Services\TallyExportService;
use App\Domains\Tally\Services\TallySyncMappingService;
use App\Domains\TallySync\Support\SyncGate;
use App\Domains\Sync\Support\SyncLinks;
use App\Jobs\ProcessTallySyncJob;
use Illuminate\Database\Eloquent\Model;
use Tally\Accounting\VoucherStatus;
use Tally\Models\AccountGroup;
use Tally\Models\CostCategory;
use Tally\Models\CostCentre;
use Tally\Models\Godown;
use Tally\Models\Ledger;
use Tally\Models\Product;
use Tally\Models\ProductGroup;
use Tally\Models\TallyLink;
use Tally\Models\Unit;
use Tally\Models\Voucher;

class BooksSyncQueue
{
    /** @var array<string, class-string<Model>> */
    public const MODELS = [
        'books_voucher' => Voucher::class,
        'books_ledger' => Ledger::class,
        'books_group' => AccountGroup::class,
        'books_stock_item' => Product::class,
        'books_stock_group' => ProductGroup::class,
        'books_godown' => Godown::class,
        'books_unit' => Unit::class,
        'books_cost_centre' => CostCentre::class,
        'books_cost_category' => CostCategory::class,
    ];

    /** @var array<string, true> */
    private static array $building = [];

    public function __construct(private readonly BooksXml $xml) {}

    public function enqueue(Model $model): ?TallySyncQueue
    {
        if (! SyncGate::enabled()) {
            return null;
        }

        $type = array_search($model::class, self::MODELS, true);
        if ($type === false) {
            return null;
        }

        $key = $model::class.':'.$model->getKey();
        if (isset(self::$building[$key])) {
            return null;
        }

        self::$building[$key] = true;

        try {
            $action = $this->action($model, $type);
            if ($action === null) {
                return null;
            }

            $this->dependencies($model);
            $payload = $this->xml->render($model, $action);
            $existing = TallySyncQueue::query()
                ->where('document_type', $type)
                ->where('document_id', $model->getKey())
                ->where('status', 'pending')
                ->first();

            if ($existing) {
                $existing->update(['payload' => $payload]);

                return $existing;
            }

            $queue = TallySyncQueue::query()->create([
                'document_type' => $type,
                'document_id' => $model->getKey(),
                'payload' => $payload,
                'status' => 'pending',
                'attempts' => 0,
            ]);

            ProcessTallySyncJob::dispatch($queue->id);

            return $queue;
        } finally {
            unset(self::$building[$key]);
        }
    }

    public function recordLink(TallySyncQueue $queue, ?string $response): void
    {
        $class = self::MODELS[$queue->document_type] ?? null;
        if (! $class) {
            return;
        }

        $model = $class::query()->find($queue->document_id);
        if (! $model) {
            return;
        }

        $guid = null;
        $alter = null;
        if ($response && preg_match('/<GUID>([^<]+)<\/GUID>/i', $response, $match)) {
            $guid = trim($match[1]);
        }
        if ($response && preg_match('/<ALTERID>(\d+)<\/ALTERID>/i', $response, $match)) {
            $alter = $match[1];
        }
        $parsed = TallyExportService::parseTallyResponse($response);
        $remote = $this->remoteId($model);

        TallyLink::query()->updateOrCreate(
            [
                'linkable_type' => $model::class,
                'linkable_id' => $model->getKey(),
                'tally_type' => $queue->document_type,
            ],
            [
                'company_id' => $model->company_id ?? null,
                'guid' => $guid,
                'alter_id' => $alter,
                'master_id' => $parsed['lastvchid'] > 0 ? (string) $parsed['lastvchid'] : null,
                'remote_id' => $remote,
            ]
        );

        $name = $model->name ?? $model->voucher_number ?? null;
        app(TallySyncMappingService::class)->createOrUpdate(
            $model,
            $model::class,
            $queue->document_type,
            $guid,
            $name,
            'synced'
        );

        $this->mirror($model, $guid, $name);
    }

    public static function pendingFor(Model $model): bool
    {
        $type = array_search($model::class, self::MODELS, true);
        if ($type === false) {
            return false;
        }

        return TallySyncQueue::query()
            ->where('document_type', $type)
            ->where('document_id', $model->getKey())
            ->where('status', 'pending')
            ->exists();
    }

    private function action(Model $model, string $type): ?string
    {
        if ($model instanceof Voucher) {
            $status = $model->status instanceof VoucherStatus ? $model->status : VoucherStatus::tryFrom((string) $model->status);
            $linked = $this->linked($model, $type);
            if ($status === VoucherStatus::Cancelled) {
                return $linked ? 'Cancel' : null;
            }
            if ($status !== VoucherStatus::Posted) {
                return null;
            }

            return $linked ? 'Alter' : 'Create';
        }

        return $this->linked($model, $type) ? 'Alter' : 'Create';
    }

    private function linked(Model $model, string $type): bool
    {
        return TallyLink::query()
            ->where('linkable_type', $model::class)
            ->where('linkable_id', $model->getKey())
            ->where('tally_type', $type)
            ->exists();
    }

    private function dependencies(Model $model): void
    {
        if ($model instanceof Ledger && $model->account_group_id) {
            $group = $model->accountGroup;
            if ($group) {
                $this->enqueue($group);
            }
        }

        if ($model instanceof Product) {
            $model->loadMissing(['primaryUnit', 'productGroup']);
            if ($model->primaryUnit) {
                $this->enqueue($model->primaryUnit);
            }
            if ($model->productGroup) {
                $this->enqueue($model->productGroup);
            }
        }

        foreach (['parent'] as $relation) {
            if (method_exists($model, $relation) && $model->{$relation}) {
                $this->enqueue($model->{$relation});
            }
        }

        if ($model instanceof CostCentre && $model->category) {
            $this->enqueue($model->category);
        }

        if ($model instanceof Voucher) {
            $model->loadMissing([
                'entries.ledger.accountGroup',
                'entries.costCentre.category',
                'inventoryLines.product.primaryUnit',
                'inventoryLines.product.productGroup',
                'inventoryLines.godown',
                'inventoryLines.destinationGodown',
            ]);
            foreach ($model->entries as $entry) {
                if ($entry->ledger) {
                    $this->enqueue($entry->ledger);
                }
                if ($entry->costCentre) {
                    $this->enqueue($entry->costCentre);
                }
            }
            foreach ($model->inventoryLines as $line) {
                if ($line->product) {
                    $this->enqueue($line->product);
                }
                if ($line->godown) {
                    $this->enqueue($line->godown);
                }
                if ($line->destinationGodown) {
                    $this->enqueue($line->destinationGodown);
                }
            }
        }
    }

    private function remoteId(Model $model): string
    {
        $type = array_search($model::class, self::MODELS, true);

        return $type.'-'.$model->getKey();
    }

    private function mirror(Model $model, ?string $guid, ?string $name): void
    {
        if (! $guid) {
            return;
        }

        $row = SyncLinks::rowFor($model);
        if (! $row?->dms_type || ! $row->dms_id || ! class_exists($row->dms_type)) {
            return;
        }

        $dms = $row->dms_type::query()->find($row->dms_id);
        if (! $dms) {
            return;
        }

        TallySyncMapping::query()->updateOrCreate(
            [
                'entity_type' => $dms::class,
                'entity_id' => $dms->getKey(),
                'tally_type' => 'ledger',
            ],
            [
                'tally_guid' => $guid,
                'tally_name' => $name,
                'sync_status' => 'synced',
                'last_synced_at' => now(),
            ]
        );
    }
}