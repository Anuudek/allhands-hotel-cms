<?php

namespace App\Emulator\Drivers\Arcturus;

use App\Emulator\Contracts\FurnitureRepository;
use App\Emulator\Data\FurnitureHolding;
use App\Models\Game\Furniture\Item;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Arcturus inventory rows live in items, referencing items_base via item_id.
 */
class ArcturusFurnitureRepository implements FurnitureRepository
{
    public function definitionCount(): int
    {
        return DB::table('items_base')->count();
    }

    public function isLimitedEdition(int $baseItemId): bool
    {
        return DB::table('catalog_items')
            ->where('item_ids', (string) $baseItemId)
            ->where('limited_stack', '>', 0)
            ->exists();
    }

    public function grant(User $user, int $baseItemId, int $amount): void
    {
        for ($i = 0; $i < $amount; $i++) {
            Item::query()->create([
                'user_id' => $user->id,
                'item_id' => $baseItemId,
            ]);
        }
    }

    public function holdings(int $baseItemId, int $limit = 100): Collection
    {
        return DB::table('items')
            ->where('item_id', $baseItemId)
            ->groupBy('user_id')
            ->selectRaw('user_id, COUNT(*) as item_count')
            ->orderByDesc('item_count')
            ->limit($limit)
            ->get()
            ->map(fn (object $row) => new FurnitureHolding(
                (int) data_get($row, 'user_id'),
                (int) data_get($row, 'item_count'),
            ));
    }
}
