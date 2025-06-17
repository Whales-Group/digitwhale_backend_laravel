<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateLiveLocationsAddLongitudeColumn extends Migration
{
    public function up(): void
    {
        Schema::table('live_locations', function (Blueprint $table) {
            $table->float('latitude')->change();
            $table->float('longitude')->change();
        });
    }

    public function down(): void
    {
        Schema::table('live_locations', function (Blueprint $table) {
            $table->dropColumn('longitude');
        });
    }
}
