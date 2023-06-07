<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $table = 'transactions';

    protected $fillable = [
        'recipient_name',
        'recipient_phone',
        'recipient_address',
        'recipient_latitude',
        'recipient_longitude',
        'total',
        'profile_id',
        'payment_method_id',
        'payment_status_id',
    ];

    /**
     * Get the profile that owns the transaction.
     */
    public function profile()
    {
        return $this->belongsTo(Profile::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function transactionStatus()
    {
        return $this->belongsTo(TransactionStatus::class);
    }

    public function transactionByStore()
    {
        return $this->hasMany(TransactionByStore::class);
    }
}
