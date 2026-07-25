<?php

namespace App\Providers;

use App\Emulator\Contracts\RankRepository;
use App\Models\Community\Staff\WebsiteTeam as StaffWebsiteTeam;
use App\Models\Community\Teams\WebsiteTeam;
use App\Models\User;
use App\Observers\CommunityCacheObserver;
use App\Observers\UserObserver;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
    ];

    protected $observers = [
        User::class => [UserObserver::class, CommunityCacheObserver::class],
        StaffWebsiteTeam::class => [CommunityCacheObserver::class],
        WebsiteTeam::class => [CommunityCacheObserver::class],
    ];

    /**
     * A hotel runs one emulator, so only the active driver's rank model needs
     * to bust the community caches. The model differs per driver, which is why
     * it cannot be listed in $observers.
     */
    public function boot(): void
    {
        CommunityCacheObserver::observeRanks($this->app->make(RankRepository::class));
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
