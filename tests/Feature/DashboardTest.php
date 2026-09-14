<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response
        ->assertOk()
        ->assertHeader('Content-Type', 'text/html; charset=UTF-8')
        ->assertHeader('Vary', 'X-Inertia')
        ->assertSee('<!DOCTYPE html>', false);

    expect($response->headers->get('Cache-Control'))
        ->toContain('no-store')
        ->toContain('private');
});

test('authenticated Inertia visits return JSON that cannot be cached as a document', function () {
    $user = User::factory()->create();
    $version = app(HandleInertiaRequests::class)->version(request());

    $response = $this->actingAs($user)
        ->withHeader('X-Inertia', 'true')
        ->withHeader('X-Inertia-Version', $version)
        ->get(route('dashboard'));

    $response
        ->assertOk()
        ->assertHeader('Content-Type', 'application/json')
        ->assertHeader('X-Inertia', 'true')
        ->assertHeader('Vary', 'X-Inertia');

    expect($response->headers->get('Cache-Control'))
        ->toContain('no-store')
        ->toContain('private');
});
