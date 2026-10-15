<?php

namespace App\Models;

use App\Traits\HasSubscriberScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssociateGroup extends Model
{
    use SoftDeletes, HasSubscriberScope;

    protected $fillable = ['subscriber_id', 'name', 'status'];
}
