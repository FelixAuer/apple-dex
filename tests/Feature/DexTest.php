<?php

use App\Models\AppleCatch;
use App\Models\User;
use App\Models\Variety;
use Livewire\Volt\Volt;

test('guest is redirected to login from the dex', function () {
    $this->get('/')->assertRedirect(route('login'));
});

test('guest is redirected to login from catch/new and variety card routes', function () {
    $variety = Variety::factory()->create();

    $this->get('/catch/new')->assertRedirect(route('login'));
    $this->get("/varieties/{$variety->id}")->assertRedirect(route('login'));
});

test('dex shows global varieties and own customs but never another user\'s customs', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $global = Variety::factory()->create(['user_id' => null]);
    $ownCustom = Variety::factory()->create(['user_id' => $user->id]);
    $othersCustom = Variety::factory()->create(['user_id' => $otherUser->id]);

    $instance = Volt::actingAs($user)->test('dex')->instance();
    $names = $instance->caught->concat($instance->uncaught)->pluck('name');

    expect($names)->toContain($global->name, $ownCustom->name)
        ->not->toContain($othersCustom->name);
});

test('completion counter counts own catches against all visible varieties', function () {
    $user = User::factory()->create();

    Variety::factory()->count(3)->create(['user_id' => null]);
    $caughtVariety = Variety::factory()->create(['user_id' => null]);
    AppleCatch::factory()->create(['user_id' => $user->id, 'variety_id' => $caughtVariety->id]);

    $component = Volt::actingAs($user)->test('dex');

    expect($component->instance()->total)->toBe(4)
        ->and($component->instance()->caughtCount)->toBe(1);
});

test('search filters the grid case-insensitively by substring', function () {
    $user = User::factory()->create();

    Variety::factory()->create(['user_id' => null, 'name' => 'Gravensteiner']);
    Variety::factory()->create(['user_id' => null, 'name' => 'Boskoop']);

    $instance = Volt::actingAs($user)->test('dex')
        ->set('search', 'graven')
        ->instance();
    $names = $instance->caught->concat($instance->uncaught)->pluck('name');

    expect($names)->toContain('Gravensteiner')->not->toContain('Boskoop');
});

test('sort toggle persists the chosen sort in the session', function () {
    $user = User::factory()->create();

    Volt::actingAs($user)->test('dex')->set('sort', 'az');

    expect(session('dex_sort'))->toBe('az');
});
