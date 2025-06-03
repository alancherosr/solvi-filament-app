<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Transaction record for personal finances.
 */
class Transaction extends Model
{
    protected $fillable = [
        'date',
        'description',
        'amount',
        'category',
    ];
}
