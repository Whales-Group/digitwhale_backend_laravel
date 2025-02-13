<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAccountSettingsTable extends Migration
{
    public function up()
    {
        Schema::dropIfExists('account_settings');
        Schema::dropIfExists('next_of_kins');
        Schema::dropIfExists('security_questions');
        Schema::dropIfExists('verification_records');

        Schema::create('account_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->boolean('hide_balance')->default(false);
            $table->boolean('enable_biometrics')->default(false);
            $table->boolean('enable_air_transfer')->default(false);
            $table->boolean('enable_notifications')->default(true);
            $table->string('transaction_pin')->nullable();
            $table->boolean('enabled_2fa')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('account_settings');
    }
}