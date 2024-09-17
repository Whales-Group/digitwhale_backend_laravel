<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create("accounts", function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("user_id");
            $table->string("account_number")->unique();
            $table->string("account_name");
            $table->decimal("balance", 15, 2)->default(0.0);
            $table
                ->enum("status", ["active", "inactive", "suspended"])
                ->default("active");
            $table->enum("type", ["tire1", "tire2", "tire3"])->default("tire1");
            $table->timestamps();
            $table->timestamp("deleted_at")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("accounts");
    }
};
