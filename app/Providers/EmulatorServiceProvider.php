<?php

namespace App\Providers;

use App\Contracts\Rcon;
use App\Emulator\EmulatorManager;
use App\Services\AfterCommitRcon;
use Illuminate\Support\ServiceProvider;

/**
 * Binds the emulator contracts to the implementations of the configured driver,
 * so the rest of the CMS depends only on the contracts.
 */
class EmulatorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(EmulatorManager::class);

        foreach (EmulatorManager::REPOSITORY_CONTRACTS as $contract) {
            $this->app->singleton(
                $contract,
                fn ($app) => $app->make(EmulatorManager::class)->repository($contract),
            );
        }

        // Wrapped so RCON sends inside a database transaction only fire once it
        // commits - a rolled-back purchase never grants items in the emulator.
        $this->app->singleton(
            Rcon::class,
            fn ($app): Rcon => new AfterCommitRcon($app->make(EmulatorManager::class)->active()->rcon()),
        );
    }

    public function boot(EmulatorManager $manager): void
    {
        foreach ($manager->active()->migrationPaths() as $path) {
            $this->loadMigrationsFrom($path);
        }
    }
}
