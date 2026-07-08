<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KomojuCustomer extends Model
{
    protected $fillable = [
        'user_id',
        'email',
        'komoju_customer_id',
        'payment_resource_id',
        'card_brand',
        'card_last4',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
