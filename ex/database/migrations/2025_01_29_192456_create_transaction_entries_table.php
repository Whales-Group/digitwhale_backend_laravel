<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionEntriesTable extends Migration
{
    public function up()
    {
        Schema::create('transaction_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_sys_account_id')->nullable()->constrained('accounts')->onDelete('set null');
            $table->string('from_account')->nullable();
            $table->string('from_user_name')->nullable();
            $table->string('from_user_email')->nullable();
            $table->string('currency')->nullable();
            $table->foreignId('to_sys_account_id')->nullable()->constrained('accounts')->onDelete('set null');
            $table->string('to_user_name')->nullable();
            $table->string('to_user_email')->nullable();
            $table->string('to_bank_name')->nullable();
            $table->string('to_bank_code')->nullable();
            $table->string('to_account_number')->nullable();
            $table->string('transaction_reference')->nullable();
            $table->string('status');
            $table->string('type');
            $table->double('amount');
            $table->timestamp('timestamp');
            $table->string('description')->nullable();
            $table->string('entry_type');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('transaction_entries');
    }
}