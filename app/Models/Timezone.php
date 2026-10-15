<?php

namespace App\Models;

use App\Traits\HasSubscriberScope;
use Illuminate\Database\Eloquent\Model;

class Timezone extends Model
{
    use HasSubscriberScope;

    protected $fillable = ['subscriber_id', 'timezone', 'is_default'];
}
