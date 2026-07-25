<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users')) {
            return;
        }

        if (! Schema::hasTable('players')) {
            throw new RuntimeException(
                'Ada tables were not found. Start Ada once so its EF migrations run before installing Atom CMS.',
            );
        }

        Schema::create('users', function (Blueprint $table): void {
            // Atom's existing website tables use Arcturus' signed INT user IDs
            // for their foreign keys. Keep the compatibility table identical so
            // every existing CMS migration remains valid on Ada.
            $table->integer('id', autoIncrement: true);
            $table->string('username', 50)->unique();
            $table->string('real_name', 50)->default('');
            $table->string('password', 60);
            $table->string('mail', 255)->nullable();
            $table->string('mail_verified', 1)->default('0');
            $table->unsignedInteger('account_created')->default(0);
            $table->unsignedInteger('account_day_of_birth')->default(0);
            $table->unsignedInteger('last_login')->default(0);
            $table->unsignedInteger('last_online')->default(0);
            $table->string('motto', 127)->default('');
            $table->string('look', 256)->default('');
            $table->string('gender', 1)->default('M');
            $table->unsignedInteger('rank')->default(1);
            $table->integer('credits')->default(0);
            $table->integer('pixels')->default(0);
            $table->integer('points')->default(0);
            $table->boolean('online')->default(false);
            $table->string('auth_ticket', 256)->default('')->index();
            $table->string('ip_register', 45)->default('');
            $table->string('ip_current', 45)->default('');
            $table->string('machine_id', 64)->default('');
            $table->unsignedInteger('home_room')->default(0);
            $table->string('secret_key', 40)->nullable();
            $table->string('pincode', 11)->nullable();
            $table->unsignedInteger('extra_rank')->nullable();
        });

        DB::table('players')
            ->leftJoin('player_avatar_data', 'player_avatar_data.player_id', '=', 'players.id')
            ->leftJoin('player_data', 'player_data.player_id', '=', 'players.id')
            ->leftJoin('player_website_data', 'player_website_data.player_id', '=', 'players.id')
            ->orderBy('players.id')
            ->select([
                'players.id',
                'players.username',
                'players.email',
                'players.password',
                'players.created_at',
                'player_avatar_data.figure_code',
                'player_avatar_data.motto',
                'player_avatar_data.gender',
                'player_data.home_room_id',
                'player_data.credit_balance',
                'player_data.pixel_balance',
                'player_data.gotw_points',
                'player_data.is_online',
                'player_data.last_online',
                'player_website_data.initial_ip',
                'player_website_data.last_ip',
                'player_website_data.last_login',
            ])
            ->chunk(250, function ($players): void {
                $roleIds = DB::table('player_role')
                    ->whereIn('player_id', $players->pluck('id'))
                    ->selectRaw('player_id, MAX(role_id) as role_id')
                    ->groupBy('player_id')
                    ->pluck('role_id', 'player_id');

                DB::table('users')->insert($players->map(fn (object $player): array => [
                    'id' => $player->id,
                    'username' => $player->username,
                    'password' => $player->password,
                    'mail' => $player->email,
                    'account_created' => $this->unix($player->created_at),
                    'last_login' => $this->unix($player->last_login),
                    'last_online' => $this->unix($player->last_online),
                    'motto' => $player->motto ?? '',
                    'look' => $player->figure_code ?? '',
                    'gender' => $player->gender ?? 'M',
                    'rank' => (int) ($roleIds[$player->id] ?? 1),
                    'credits' => (int) ($player->credit_balance ?? 0),
                    'pixels' => (int) ($player->pixel_balance ?? 0),
                    'points' => (int) ($player->gotw_points ?? 0),
                    'online' => (bool) ($player->is_online ?? false),
                    'ip_register' => $player->initial_ip ?? '',
                    'ip_current' => $player->last_ip ?? '',
                    'home_room' => (int) ($player->home_room_id ?? 0),
                ])->all());
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }

    private function unix(mixed $value): int
    {
        return $value === null ? 0 : Carbon::parse($value)->unix();
    }
};
