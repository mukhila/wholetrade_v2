<?php

namespace App\Models;

use App\Traits\HasSubscriberScope;
use Illuminate\Database\Eloquent\Model;

class Bank extends Model
{
    use HasSubscriberScope;

    protected $fillable = ['subscriber_id', 'name', 'status'];
}
