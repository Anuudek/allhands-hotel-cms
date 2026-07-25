<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('camera_web')) {
            return;
        }

        // Ada does not persist camera photos. Keep Atom's public photo pages
        // operational (and empty) instead of querying an Arcturus-only table.
        Schema::create('camera_web', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedBigInteger('user_id')->index();
            $table->integer('room_id')->default(0);
            $table->unsignedInteger('timestamp');
            $table->string('url', 128)->default('');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('camera_web');
    }
};
