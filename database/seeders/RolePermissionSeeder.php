<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Contracts\Permission as PermissionContract;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissionNames = [
            'usuarios.ver',
            'usuarios.gestionar',
            'clientes.ver',
            'clientes.gestionar',
            'vehiculos.ver',
            'vehiculos.gestionar',
            'ordenes-trabajo.ver',
            'ordenes-trabajo.gestionar',
            'inventario.ver',
            'inventario.gestionar',
            'reportes.ver',
        ];

        $permissions = collect($permissionNames)
            ->map(fn (string $permissionName): PermissionContract => Permission::findOrCreate($permissionName, 'web'));

        Role::findOrCreate('Administrador', 'web')
            ->syncPermissions($permissions);

        Role::findOrCreate('Recepción', 'web')
            ->syncPermissions($permissions->whereIn('name', [
                'clientes.ver',
                'clientes.gestionar',
                'vehiculos.ver',
                'vehiculos.gestionar',
                'ordenes-trabajo.ver',
                'ordenes-trabajo.gestionar',
                'inventario.ver',
                'reportes.ver',
            ]));

        Role::findOrCreate('Mecánico', 'web')
            ->syncPermissions($permissions->whereIn('name', [
                'clientes.ver',
                'vehiculos.ver',
                'ordenes-trabajo.ver',
                'ordenes-trabajo.gestionar',
                'inventario.ver',
            ]));

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
