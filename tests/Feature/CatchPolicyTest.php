<?php

use App\Models\AppleCatch;
use App\Models\User;

test('a user can view, update, and delete only their own catch', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $catch = AppleCatch::factory()->create(['user_id' => $owner->id]);

    expect($owner->can('view', $catch))->toBeTrue()
        ->and($owner->can('update', $catch))->toBeTrue()
        ->and($owner->can('delete', $catch))->toBeTrue()
        ->and($other->can('view', $catch))->toBeFalse()
        ->and($other->can('update', $catch))->toBeFalse()
        ->and($other->can('delete', $catch))->toBeFalse();
});

test('any authenticated user can create a catch', function () {
    $user = User::factory()->create();

    expect($user->can('create', AppleCatch::class))->toBeTrue();
});
