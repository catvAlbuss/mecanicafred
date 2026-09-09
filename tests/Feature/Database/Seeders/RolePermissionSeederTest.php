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
    ])->and(Permission::query()->count())->toBe(11)
        ->and(Role::findByName('Administrador')->permissions)->toHaveCount(11)
        ->and(Role::findByName('Recepción')->hasPermissionTo('clientes.gestionar'))->toBeTrue()
        ->and(Role::findByName('Recepción')->hasPermissionTo('usuarios.gestionar'))->toBeFalse()
        ->and(Role::findByName('Mecánico')->hasPermissionTo('ordenes-trabajo.gestionar'))->toBeTrue()
        ->and(Role::findByName('Mecánico')->hasPermissionTo('inventario.gestionar'))->toBeFalse();
});

test('grants every ability to administrators through the gate', function () {
    $this->seed(RolePermissionSeeder::class);
    $administrator = User::factory()->create();
    $administrator->assignRole('Administrador');

    expect($administrator->can('una-habilidad-futura'))->toBeTrue();
});
