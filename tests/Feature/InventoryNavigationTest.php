<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RolePermissionSeeder::class);
});

test('redirects guests away from inventory', function () {
    $this->get(route('inventory.products.index'))
        ->assertRedirect(route('login'));
});

test('allows mechanics to view inventory but not suppliers', function () {
    $mechanic = User::factory()->create();
    $mechanic->assignRole('Mecánico');

    $this->actingAs($mechanic)
        ->get(route('inventory.products.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('inventory/Index'));

    $this->actingAs($mechanic)
        ->get(route('suppliers.index'))
        ->assertForbidden();
});

test('allows reception to view suppliers', function () {
    $receptionist = User::factory()->create();
    $receptionist->assignRole('Recepción');

    $this->actingAs($receptionist)
        ->get(route('suppliers.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('suppliers/Index'));
});
