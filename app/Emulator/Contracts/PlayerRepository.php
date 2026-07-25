<?php

namespace App\Emulator\Contracts;

use App\Emulator\Data\HomeFriend;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Bridges Atom's user model to the configured emulator's player identity.
 *
 * Arcturus stores the CMS and emulator identity in the same users row. Ada
 * normalises player data across several EF-owned tables, so its driver keeps
 * Atom's compatibility row in sync and hydrates it from Ada when it is read.
 */
interface PlayerRepository
{
    public function created(User $user): void;

    public function updated(User $user): void;

    public function deleted(User $user): void;

    /**
     * Refresh emulator-owned attributes on freshly loaded users.
     *
     * Called once per query with the whole result set, so drivers that mirror
     * their state into Atom's users table can do so without an N+1.
     *
     * @param  array<int, User>  $users
     */
    public function hydrateMany(array $users): void;

    /**
     * Constrain a user query to the players the emulator considers online.
     *
     * Drivers that do not own the users.online column must express this
     * against their own tables; the mirrored column is not authoritative.
     *
     * @param  Builder<User>  $query
     *
     * @return Builder<User>
     */
    public function whereOnline(Builder $query): Builder;

    public function issueSso(User $user): string;

    /** @return Collection<int, User> */
    public function onlineFriends(User $user, int $limit): Collection;

    /** @return LengthAwarePaginator<int, HomeFriend> */
    public function friendsForHome(User $user, int $perPage, string $pageName): LengthAwarePaginator;
}
