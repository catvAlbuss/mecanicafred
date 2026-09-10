<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

test('creates the workshop roles with their permissions', function () {
    $this->seed(RolePermissionSeeder::class);

    expect(Role::query()->pluck('name')->sort()->values()->all())->toBe([
        'Administrador',
        'Mecánico',
        'Recepción',
    ])->and(Permission::query()->count())->toBe(31)
        ->and(Role::findByName('Administrador')->permissions)->toHaveCount(31)
        ->and(Role::findByName('Recepción')->hasPermissionTo('proveedores.gestionar'))->toBeTrue()
        ->and(Role::findByName('Recepción')->hasPermissionTo('pedidos-compra.aprobar'))->toBeFalse()
        ->and(Role::findByName('Mecánico')->hasPermissionTo('inventario.ver'))->toBeTrue()
        ->and(Role::findByName('Mecánico')->hasPermissionTo('inventario.gestionar'))->toBeFalse();
});

test('updates roles idempotently', function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(RolePermissionSeeder::class);

    expect(Role::query()->count())->toBe(3)
        ->and(Permission::query()->count())->toBe(31);
});

test('grants every ability to administrators through the gate', function () {
    $this->seed(RolePermissionSeeder::class);
    $administrator = User::factory()->create();
    $administrator->assignRole('Administrador');

    expect($administrator->can('una-habilidad-futura'))->toBeTrue();
});
