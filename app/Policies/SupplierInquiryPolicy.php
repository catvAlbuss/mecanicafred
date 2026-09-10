<?php

namespace App\Policies;

use App\Enums\SupplierInquiryStatus;
use App\Models\SupplierInquiry;
use App\Models\User;

class SupplierInquiryPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('consultas-proveedor.ver');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, SupplierInquiry $supplierInquiry): bool
    {
        return $user->can('consultas-proveedor.ver');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('consultas-proveedor.gestionar');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, SupplierInquiry $supplierInquiry): bool
    {
        return $user->can('consultas-proveedor.gestionar') && $supplierInquiry->status === SupplierInquiryStatus::Draft;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, SupplierInquiry $supplierInquiry): bool
    {
        return $user->can('consultas-proveedor.gestionar') && $supplierInquiry->status === SupplierInquiryStatus::Draft;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, SupplierInquiry $supplierInquiry): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, SupplierInquiry $supplierInquiry): bool
    {
        return false;
    }

    public function transition(User $user, SupplierInquiry $supplierInquiry): bool
    {
        return $user->can('consultas-proveedor.gestionar');
    }

    public function convert(User $user, SupplierInquiry $supplierInquiry): bool
    {
        return $user->can('consultas-proveedor.gestionar') && $user->can('pedidos-compra.crear');
    }
}
