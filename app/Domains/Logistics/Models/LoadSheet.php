<?php

namespace App\Domains\Logistics\Models;

use App\Domains\Delivery\Models\Delivery;
use App\Domains\Master\Models\DeliveryPerson;
use App\Domains\Master\Models\Driver;
use App\Domains\Master\Models\Route;
use App\Domains\Master\Models\Vehicle;
use App\Domains\Settlement\Models\Settlement;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LoadSheet extends Model
{
    protected $fillable = [
        'load_sheet_no',
        'load_date',
        'route_id',
        'vehicle_id',
        'driver_id',
        'delivery_person_id',
        'status',
        'total_value',
        'total_quantity',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'load_date' => 'date',
            'total_value' => 'decimal:2',
            'total_quantity' => 'decimal:4',
        ];
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function deliveryPerson(): BelongsTo
    {
        return $this->belongsTo(DeliveryPerson::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(LoadSheetItem::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(Delivery::class);
    }

    public function settlement(): HasOne
    {
        return $this->hasOne(Settlement::class);
    }
}
