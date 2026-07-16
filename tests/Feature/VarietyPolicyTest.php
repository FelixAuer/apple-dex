<?php

use App\Models\User;
use App\Models\Variety;

test('any user can view a global variety', function () {
    $user = User::factory()->create();
    $global = Variety::factory()->create(['user_id' => null]);

    expect($user->can('view', $global))->toBeTrue();
});

test('a user can view their own custom variety but not another user\'s', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $custom = Variety::factory()->create(['user_id' => $owner->id]);

    expect($owner->can('view', $custom))->toBeTrue()
        ->and($other->can('view', $custom))->toBeFalse();
});

test('any user can create a custom variety', function () {
    $user = User::factory()->create();

    expect($user->can('create', Variety::class))->toBeTrue();
});

test('only the owner can update or delete their own custom variety', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $custom = Variety::factory()->create(['user_id' => $owner->id]);

    expect($owner->can('update', $custom))->toBeTrue()
        ->and($owner->can('delete', $custom))->toBeTrue()
        ->and($other->can('update', $custom))->toBeFalse()
        ->and($other->can('delete', $custom))->toBeFalse();
});

test('global varieties cannot be updated or deleted by any user', function () {
    $user = User::factory()->create();
    $global = Variety::factory()->create(['user_id' => null]);

    expect($user->can('update', $global))->toBeFalse()
        ->and($user->can('delete', $global))->toBeFalse();
});
