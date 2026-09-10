<?php

namespace App\Policies;

use App\Models\CashRegister;
use App\Models\User;

class CashRegisterPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('caja.ver');
    }

    public function view(User $user, CashRegister $cashRegister): bool
    {
        return $user->can('caja.ver');
    }

    public function open(User $user): bool
    {
        return $user->can('caja.abrir');
    }

    public function close(User $user, CashRegister $cashRegister): bool
    {
        return $user->can('caja.cerrar') && $cashRegister->isOpen();
    }

    public function registerTransaction(User $user, CashRegister $cashRegister): bool
    {
        return $user->can('caja.registrar-movimiento') && $cashRegister->isOpen();
    }
}
