<?php

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('allows reception to manage the phase one catalog', function () {
    $receptionist = User::factory()->create();
    $receptionist->assignRole('Recepción');
    $supplier = Supplier::factory()->create();

    expect($receptionist->can('viewAny', ProductCategory::class))->toBeTrue()
        ->and($receptionist->can('create', ProductCategory::class))->toBeTrue()
        ->and($receptionist->can('viewAny', Product::class))->toBeTrue()
        ->and($receptionist->can('create', Product::class))->toBeTrue()
        ->and($receptionist->can('adjust', Product::factory()->create()))->toBeTrue()
        ->and($receptionist->can('viewAny', Supplier::class))->toBeTrue()
        ->and($receptionist->can('view', $supplier))->toBeTrue()
        ->and($receptionist->can('create', Supplier::class))->toBeTrue()
        ->and($receptionist->can('update', $supplier))->toBeTrue()
        ->and($receptionist->can('delete', $supplier))->toBeTrue();
});

test('limits mechanics to viewing inventory', function () {
    $mechanic = User::factory()->create();
    $mechanic->assignRole('Mecánico');
    $supplier = Supplier::factory()->create();

    expect($mechanic->can('viewAny', Product::class))->toBeTrue()
        ->and($mechanic->can('create', Product::class))->toBeFalse()
        ->and($mechanic->can('adjust', Product::factory()->create()))->toBeFalse()
        ->and($mechanic->can('viewAny', ProductCategory::class))->toBeFalse()
        ->and($mechanic->can('viewAny', Supplier::class))->toBeFalse()
        ->and($mechanic->can('view', $supplier))->toBeFalse()
        ->and($mechanic->can('create', Supplier::class))->toBeFalse()
        ->and($mechanic->can('update', $supplier))->toBeFalse()
        ->and($mechanic->can('delete', $supplier))->toBeFalse();
});

test('keeps administrators authorized through the global gate', function () {
    $administrator = User::factory()->create();
    $administrator->assignRole('Administrador');

    expect($administrator->can('create', Product::class))->toBeTrue()
        ->and($administrator->can('create', Supplier::class))->toBeTrue();
});
