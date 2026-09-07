<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'workspace_id',
        'user_id',
        'gateway_code',
        'gateway_name',
        'plan_name',
        'amount',
        'currency',
        'status',
        'is_simulation',
        'simulation_scenario',
        'card_brand',
        'card_last_four',
        'customer_name',
        'customer_email',
        'error_message',
        'response_payload',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'is_simulation' => 'boolean',
        'response_payload' => 'array',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function gateway(): BelongsTo
    {
        return $this->belongsTo(PaymentGateway::class, 'gateway_code', 'code');
    }
}
