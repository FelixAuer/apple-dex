<?php

use App\Models\User;
use App\Models\Variety;
use Illuminate\Database\QueryException;

test('a user cannot have two custom varieties with the same name', function () {
    $user = User::factory()->create();
    Variety::factory()->create(['name' => 'Omas Apfel', 'user_id' => $user->id]);

    expect(fn () => Variety::factory()->create(['name' => 'Omas Apfel', 'user_id' => $user->id]))
        ->toThrow(QueryException::class);
});

test('seeding the same global variety name twice updates rather than duplicates it', function () {
    Variety::query()->updateOrCreate(
        ['name' => 'Boskoop', 'user_id' => null],
        ['origin' => 'Original origin'],
    );
    Variety::query()->updateOrCreate(
        ['name' => 'Boskoop', 'user_id' => null],
        ['origin' => 'Updated origin'],
    );

    expect(Variety::where('name', 'Boskoop')->count())->toBe(1)
        ->and(Variety::where('name', 'Boskoop')->first()->origin)->toBe('Updated origin');
});

test('two different users can each create a custom variety with the same name', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    Variety::factory()->create(['name' => 'Omas Apfel', 'user_id' => $userA->id]);
    $second = Variety::factory()->create(['name' => 'Omas Apfel', 'user_id' => $userB->id]);

    expect($second->exists)->toBeTrue();
});

test('visibleTo scope returns global varieties plus the given user\'s own customs only', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $global = Variety::factory()->create(['user_id' => null]);
    $ownCustom = Variety::factory()->create(['user_id' => $user->id]);
    $othersCustom = Variety::factory()->create(['user_id' => $otherUser->id]);

    $visible = Variety::visibleTo($user)->pluck('id');

    expect($visible)->toContain($global->id, $ownCustom->id)
        ->not->toContain($othersCustom->id);
});
