<?php

namespace App\Models;

use App\Traits\HasSubscriberScope;
use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    use HasSubscriberScope;

    protected $fillable = ['subscriber_id', 'name', 'code', 'symbol', 'is_default'];
}
