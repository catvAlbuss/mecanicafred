<?php

namespace App\Policies;

use App\Enums\SaleStatus;
use App\Models\Sale;
use App\Models\User;

class SalePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ventas.ver');
    }

    public function view(User $user, Sale $sale): bool
    {
        return $user->can('ventas.ver');
    }

    public function create(User $user): bool
    {
        return $user->can('ventas.registrar');
    }

    public function cancel(User $user, Sale $sale): bool
    {
        return $user->can('ventas.anular') && $sale->status === SaleStatus::Completed;
    }
}
