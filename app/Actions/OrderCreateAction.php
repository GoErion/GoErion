<?php

namespace App\Actions;

use App\Models\Provider;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;

class OrderCreateAction
{
    /**
     * @param array $input
     * @return void
     * 'provider_id',
     * 'user_id',
     * 'agent_id',
     * 'order_number',
     * 'status',
     * 'total_price',
     */
    public function handle(array $input, #[CurrentUser] User $user)
    {
        $provider = User::findOrFail('provider_id');
    }
}
