<?php

namespace App\Policies;

use App\Enums\PurchaseOrderStatus;
use App\Models\PurchaseOrder;
use App\Models\User;

class PurchaseOrderPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('pedidos-compra.ver');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->can('pedidos-compra.ver');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('pedidos-compra.crear');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->can('pedidos-compra.crear') && $purchaseOrder->status === PurchaseOrderStatus::Draft;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->can('pedidos-compra.crear') && $purchaseOrder->status === PurchaseOrderStatus::Draft;
    }

    public function send(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->can('pedidos-compra.crear') && $purchaseOrder->status === PurchaseOrderStatus::Draft;
    }

    public function confirm(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->can('pedidos-compra.aprobar') && $purchaseOrder->status === PurchaseOrderStatus::Sent;
    }

    public function cancel(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->can('pedidos-compra.cancelar') && in_array($purchaseOrder->status, [
            PurchaseOrderStatus::Draft,
            PurchaseOrderStatus::Sent,
            PurchaseOrderStatus::Confirmed,
        ], true);
    }

    public function receive(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->can('pedidos-compra.recibir') && in_array($purchaseOrder->status, [
            PurchaseOrderStatus::Confirmed,
            PurchaseOrderStatus::PartiallyReceived,
        ], true);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return false;
    }
}
