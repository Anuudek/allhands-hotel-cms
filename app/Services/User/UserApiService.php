<?php

namespace App\Services\User;

use App\Emulator\Contracts\PlayerRepository;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class UserApiService
{
    public function __construct(private readonly PlayerRepository $players) {}

    public function fetchUser(string $username): ?User
    {
        return User::query()
            ->select(['username', 'motto', 'look'])
            ->where('username', $username)
            ->first();
    }

    /**
     * @param  array<int, string>  $columns
     *
     * @return Collection<int, User>
     */
    public function onlineUsers(array $columns = ['username', 'motto', 'look'], int $limit = 50): Collection
    {
        $cacheKey = sprintf('api_online_users:%s:%d', implode(',', $columns), $limit);

        return Cache::remember($cacheKey, now()->addSeconds(30), fn () => $this->players
            ->whereOnline(User::query())
            ->select($columns)
            ->inRandomOrder()
            ->limit($limit)
            ->get());
    }

    public function onlineUserCount(): int
    {
        return $this->players->whereOnline(User::query())->count();
    }
}
