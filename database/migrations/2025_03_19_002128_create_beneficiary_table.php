<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('beneficiaries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // The user who added the beneficiary
            $table->string('name');
            $table->string('unique_id');
            $table->string('type'); // Type of beneficiary: 'cash_transfer', 'airtime', 'data', 'prepaid_meter', 'postpaid_meter', etc.
            $table->string('account_number')->nullable(); // For cash transfers (bank account or mobile money number)
            $table->string('bank_name')->nullable(); // For cash transfers (bank name)
            $table->string('bank_code')->nullable(); // For cash transfers (bank name)
            $table->string('network_provider')->nullable(); // For airtime and data (e.g., MTN, Airtel)
            $table->string('phone_number')->nullable(); // For airtime, data, and prepaid/postpaid meters
            $table->string('meter_number')->nullable(); // For prepaid/postpaid meters
            $table->string('utility_type')->nullable(); // For prepaid/postpaid meters (e.g., electricity, water)
            $table->string('plan')->nullable(); // For data beneficiaries (e.g., 1GB, 2GB)
            $table->decimal('amount', 10, 2)->nullable(); // For airtime, data, or bill payments
            $table->string('description')->nullable(); // Additional details about the beneficiary
            $table->boolean('is_favorite')->default(false); // Whether the beneficiary is marked as favorite
            $table->timestamps(); // Created at and updated at timestamps
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('beneficiaries');
    }
};

