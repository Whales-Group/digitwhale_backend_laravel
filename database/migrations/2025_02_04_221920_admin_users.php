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
        Schema::create("admin_users", function (Blueprint $table) {
            $table->id();
            $table->string("profile_type")->nullable(); // Type of admin profile (e.g., super_admin, regular_admin)
            $table->string("first_name")->nullable();
            $table->string("last_name")->nullable();
            $table->string("middle_name")->nullable();
            $table->string("email", 100)->unique()->nullable();
            $table->string("tag", 20)->unique()->nullable();
            $table->date("dob")->nullable();
            $table->string("profile_url")->nullable();
            $table->string("other_url")->nullable();
            $table->string("phone_number")->nullable();
            $table->timestamp("email_verified_at")->nullable();
            $table->string("password");
            $table->rememberToken();
            $table->softDeletes();
            $table->timestamps();

            $table->string("role")->default("regular_admin");
            $table->json("permissions")->nullable();

            $table->boolean("is_active")->default(true);
            $table->boolean("is_locked")->default(false);
        });


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("admin_users");
    }
};