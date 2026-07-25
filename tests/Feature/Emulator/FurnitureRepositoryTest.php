<?php

use App\Emulator\Drivers\Arcturus\ArcturusFurnitureRepository;
use App\Models\User;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    installHotel();
});

test('arcturus grants inventory rows referencing item_id', function () {
    $user = User::factory()->create();

    (new ArcturusFurnitureRepository)->grant($user, 230, 3);

    expect(DB::table('items')->where('user_id', $user->id)->where('item_id', 230)->count())->toBe(3);
});

test('arcturus reads limited editions from its catalog', function () {
    $itemId = (int) DB::table('catalog_items')->where('item_ids', 'not like', '%;%')->value('item_ids');
    DB::table('catalog_items')->where('item_ids', (string) $itemId)->update(['limited_stack' => 10]);

    expect((new ArcturusFurnitureRepository)->isLimitedEdition($itemId))->toBeTrue()
        ->and((new ArcturusFurnitureRepository)->definitionCount())->toBeGreaterThan(0);
});
