<?php

namespace App\Emulator\Drivers\Arcturus;

use App\Emulator\Contracts\BadgeRepository;
use App\Emulator\Data\OwnedBadge;
use App\Models\Game\Player\UserBadge;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Arcturus stores owned badges in users_badges (user_id, slot_id, badge_code).
 */
class ArcturusBadgeRepository implements BadgeRepository
{
    /** @return HasMany<UserBadge, User> */
    public function relation(User $user): HasMany
    {
        return $user->hasMany(UserBadge::class, 'user_id');
    }

    public function codes(User $user): array
    {
        return $this->relation($user)->pluck('badge_code')->all();
    }

    public function grant(User $user, string $badge): void
    {
        // users_badges has no single-column key Eloquent can address, so guard
        // for idempotency and insert through the query builder.
        if ($this->relation($user)->where('badge_code', $badge)->exists()) {
            return;
        }

        UserBadge::query()->insert([
            'user_id' => $user->id,
            'slot_id' => 0,
            'badge_code' => $badge,
        ]);
    }

    public function revoke(User $user, string $badge): void
    {
        $this->relation($user)->where('badge_code', $badge)->delete();
    }

    public function paginate(User $user, int $perPage, string $pageName): LengthAwarePaginator
    {
        return $this->relation($user)
            ->orderByDesc('id')
            ->paginate($perPage, ['*'], $pageName)
            ->through(fn ($row) => new OwnedBadge($row->badge_code, (int) $row->slot_id));
    }
}
