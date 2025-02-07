<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAccountsTable extends Migration
{
    public function up()
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('email');
            $table->string('phone_number');
            $table->string('tag');
            $table->string('account_id')->unique();
            $table->string('balance');
            $table->string('account_type');
            $table->string('currency');
            $table->string('validated_name');
            $table->boolean('blacklisted')->default(false);
            $table->boolean('enabled')->default(true);
            $table->integer('intrest_rate');
            $table->string('max_balance')->default('200,000.00');
            $table->string('daily_transaction_limit')->default('500,000.00');
            $table->string('daily_transaction_count')->default('5');
            $table->boolean('pnd')->default(false);
            $table->boolean('pnc')->default(false);
            $table->string('blacklist_text')->nullable();
            $table->string('dedicated_account_id');
            $table->string('account_number');
            $table->string('customer_id');
            $table->string('customer_code');
            $table->string('service_provider');
            $table->string('service_bank');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('accounts');
    }
}