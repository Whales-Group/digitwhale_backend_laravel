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
        Schema::create("paystack_d_v_a_s", function (Blueprint $table) {
            $table->id();
            $table->string("bank_name");
            $table->integer("bank_id");
            $table->string("bank_slug");
            $table->string("account_name");
            $table->string("account_number")->unique();
            $table->boolean("assigned");
            $table->string("currency", 3);
            $table->boolean("active");
            $table->integer("dva_id")->unique(); // ID of the DVA
            $table->integer("integration");
            $table->integer("assignee_id");
            $table->string("assignee_type");
            $table->boolean("expired")->default(false);
            $table->string("account_type");
            $table->timestamp("assigned_at");
            $table->unsignedBigInteger("customer_id");
            $table->string("customer_first_name");
            $table->string("customer_last_name");
            $table->string("customer_email");
            $table->string("customer_code");
            $table->string("customer_phone");
            $table->string("customer_risk_action")->default("default");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("paystack_d_v_a_s");
    }
};
