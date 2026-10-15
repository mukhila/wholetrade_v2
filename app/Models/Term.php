<?php

namespace App\Models;

use App\Traits\HasSubscriberScope;
use Illuminate\Database\Eloquent\Model;

class Term extends Model
{
    use HasSubscriberScope;

    protected $fillable = ['subscriber_id', 'name', 'no_of_days', 'status'];
}
