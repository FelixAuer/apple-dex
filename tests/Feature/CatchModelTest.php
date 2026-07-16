<?php

use App\Models\AppleCatch;
use App\Models\User;
use App\Models\Variety;
use Illuminate\Database\QueryException;

test('a user cannot catch the same variety twice', function () {
    $user = User::factory()->create();
    $variety = Variety::factory()->create();

    AppleCatch::factory()->create(['user_id' => $user->id, 'variety_id' => $variety->id]);

    expect(fn () => AppleCatch::factory()->create(['user_id' => $user->id, 'variety_id' => $variety->id]))
        ->toThrow(QueryException::class);
});

test('two different users can each catch the same variety', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();
    $variety = Variety::factory()->create();

    AppleCatch::factory()->create(['user_id' => $userA->id, 'variety_id' => $variety->id]);
    $second = AppleCatch::factory()->create(['user_id' => $userB->id, 'variety_id' => $variety->id]);

    expect($second->exists)->toBeTrue();
});
