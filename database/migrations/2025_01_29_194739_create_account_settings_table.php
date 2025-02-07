<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAccountSettingsTable extends Migration
{
    public function up()
    {
        Schema::create('account_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->boolean('hide_balance')->default(false);
            $table->boolean('enable_biometrics')->default(false);
            $table->boolean('enable_air_transfer')->default(false);
            $table->boolean('enable_notifications')->default(true);
            $table->string('address')->nullable();
            $table->string('transaction_pin')->nullable();
            $table->boolean('enabled_2fa')->default(false);
            $table->json('fcm_tokens')->nullable();
            $table->timestamps();
        });

        Schema::create('security_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_setting_id')->constrained()->onDelete('cascade');
            $table->string('question');
            $table->string('answer');
            $table->timestamps();
        });

        Schema::create('next_of_kins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_setting_id')->constrained()->onDelete('cascade');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('phone_number');
            $table->string('email');
            $table->string('verification_type');
            $table->string('verification_doc_url');
            $table->string('relationship');
            $table->timestamps();
        });

        Schema::create('personal_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_setting_id')->constrained()->onDelete('cascade');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('middle_name')->nullable();
            $table->string('tag')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('gender')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('email');
            $table->string('nin')->nullable();
            $table->string('bvn')->nullable();
            $table->string('marital_status')->nullable();
            $table->string('employment_status')->nullable();
            $table->string('annual_income')->nullable();
            $table->timestamps();
        });

        Schema::create('verification_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_setting_id')->constrained()->onDelete('cascade');
            $table->string('type');
            $table->string('status');
            $table->boolean('allow_file')->default(false);
            $table->string('value')->nullable();
            $table->string('url')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('account_settings');
        Schema::dropIfExists('security_questions');
        Schema::dropIfExists('next_of_kins');
        Schema::dropIfExists('personal_details');
        Schema::dropIfExists('verification_records');
    }
}