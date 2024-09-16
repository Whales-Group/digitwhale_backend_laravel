<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create("recipients", function (Blueprint $table) {
            $table->id();
            $table->boolean("active");
            $table->timestamp("createdAt")->nullable();
            $table->string("currency");
            $table->string("domain");
            $table->bigInteger("integration");
            $table->string("name");
            $table->string("recipient_code");
            $table->string("type");
            $table->timestamp("updatedAt")->nullable();
            $table->boolean("is_deleted")->default(false);
            $table->string("account_number");
            $table->string("account_name");
            $table->string("bank_code");
            $table->string("bank_name");
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("recipients");
    }
};
