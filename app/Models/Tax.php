<?php

namespace App\Models;

use App\Traits\HasSubscriberScope;
use Illuminate\Database\Eloquent\Model;

class Tax extends Model
{
    use HasSubscriberScope;

    protected $fillable = ['subscriber_id', 'name', 'percentage', 'type', 'status'];
}
