<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class Agent extends Model
{
    public function assignedOrders()
    {
        return $this->hasMany(Order::class, 'agent_id');
    }
}
