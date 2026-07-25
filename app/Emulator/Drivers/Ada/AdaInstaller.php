<?php

namespace App\Emulator\Drivers\Ada;

use App\Emulator\Contracts\EmulatorInstaller;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

use function Laravel\Prompts\error;

/**
 * Ada owns its schema through EF migrations, so there is nothing for Atom to
 * import - only a check that the installer is aimed at the right database.
 */
class AdaInstaller implements EmulatorInstaller
{
    private const MARKER_TABLE = 'players';

    /** A table only another emulator creates. */
    private const FOREIGN_MARKER_TABLE = 'emulator_settings';

    public function prepare(Command $command): bool
    {
        if (! Schema::hasTable(self::MARKER_TABLE)) {
            error('Ada tables were not found. Start Ada once so its EF migrations run, then re-run this installer.');

            return false;
        }

        if (Schema::hasTable(self::FOREIGN_MARKER_TABLE)) {
            error('This database already belongs to another emulator. Point Atom and Ada at a database of their own.');

            return false;
        }

        return true;
    }
}
