<?php

use App\Models\Variety;
use Database\Seeders\VarietySeeder;

test('seeder creates between 70 and 90 global varieties', function () {
    $this->seed(VarietySeeder::class);

    $count = Variety::whereNull('user_id')->count();

    expect($count)->toBeGreaterThanOrEqual(70)->toBeLessThanOrEqual(90);
});

test('seeder is idempotent when run twice', function () {
    $this->seed(VarietySeeder::class);
    $firstCount = Variety::count();

    $this->seed(VarietySeeder::class);
    $secondCount = Variety::count();

    expect($secondCount)->toBe($firstCount);
});

test('seeded varieties are global (user_id is null)', function () {
    $this->seed(VarietySeeder::class);

    expect(Variety::whereNotNull('user_id')->count())->toBe(0);
});
