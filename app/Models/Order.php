<?php

namespace App\Models;

use App\Enums\OrderStatusEnum;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @method static where(string $string, string $code)
 */
class Order extends Model
{
    use  HasUuid;


    protected $fillable = [
        'provider_id',
        'user_id',
        'agent_id',
        'order_number',
        'status',
        'total_price',
    ];

    public function user(): belongsTo
    {
        return $this->belongsTo(User::class);
    }
    public function provider(): belongsTo
    {
        return $this->belongsTo(Provider::class);
    }
    public function agent(): belongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }
    protected $casts = [
        'status' => OrderStatusEnum::class,
    ];
}
