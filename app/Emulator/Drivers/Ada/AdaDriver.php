<?php

namespace App\Emulator\Drivers\Ada;

use App\Contracts\Rcon;
use App\Emulator\Contracts\BadgeRepository;
use App\Emulator\Contracts\BanRepository;
use App\Emulator\Contracts\CurrencyRepository;
use App\Emulator\Contracts\EmulatorDriver;
use App\Emulator\Contracts\EmulatorInstaller;
use App\Emulator\Contracts\FurnitureRepository;
use App\Emulator\Contracts\PlayerRepository;
use App\Emulator\Contracts\PlayerSettingsRepository;
use App\Emulator\Contracts\PlayerStatsRepository;
use App\Emulator\Contracts\RankRepository;
use App\Emulator\Contracts\RoomRepository;
use App\Emulator\Data\Feature;
use App\Emulator\Data\PlayerConstraints;
use App\Filament\Resources\User\Users\RelationManagers\AdaBadgesRelationManager;
use App\Services\UnsupportedRcon;

final class AdaDriver implements EmulatorDriver
{
    public function key(): string
    {
        return 'ada';
    }

    public function label(): string
    {
        return 'Ada';
    }

    public function bindings(): array
    {
        return [
            BadgeRepository::class => AdaBadgeRepository::class,
            BanRepository::class => AdaBanRepository::class,
            CurrencyRepository::class => AdaCurrencyRepository::class,
            FurnitureRepository::class => AdaFurnitureRepository::class,
            PlayerRepository::class => AdaPlayerRepository::class,
            PlayerStatsRepository::class => AdaPlayerStatsRepository::class,
            PlayerSettingsRepository::class => AdaPlayerSettingsRepository::class,
            RankRepository::class => AdaRankRepository::class,
            RoomRepository::class => AdaRoomRepository::class,
        ];
    }

    public function features(): array
    {
        // Ada has no command log, no word filter, no per-player name-change
        // grant and no camera storage. Everything else Ada owns is reachable
        // through this driver's repositories and needs no gate.
        return [Feature::RareValues];
    }

    public function schemaFeatures(): array
    {
        return [];
    }

    /**
     * Mirrors Ada's EF column widths: players.username, players.email and
     * player_avatar_data.motto are varchar(50), figure_code is varchar(200).
     */
    public function playerConstraints(): PlayerConstraints
    {
        return new PlayerConstraints(50, 50, 50, 200);
    }

    public function installer(): EmulatorInstaller
    {
        return app(AdaInstaller::class);
    }

    public function rcon(): Rcon
    {
        return new UnsupportedRcon($this->key());
    }

    public function migrationPaths(): array
    {
        return [__DIR__ . '/Migrations'];
    }

    public function userRelationManagers(): array
    {
        return [AdaBadgesRelationManager::class];
    }
}
