<?php

namespace App\Models;

use App\Traits\HasSubscriberScope;
use Illuminate\Database\Eloquent\Model;

class UnitOfMeasure extends Model
{
    use HasSubscriberScope;

    protected $fillable = ['subscriber_id', 'name', 'code', 'status'];
}
