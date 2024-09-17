<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaystackDVAsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create("paystack_d_v_a_s", function (Blueprint $table) {
            $table->id();
            $table->string("bank_name");
            $table->string("bank_id");
            $table->string("bank_slug");
            $table->string("account_name");
            $table->string("account_number")->unique();
            $table->boolean("assigned");
            $table->string("currency", 3);
            $table->boolean("active");
            $table->string("dva_id")->unique();
            $table->string("integration");
            $table->string("assignee_id");
            $table->string("assignee_type");
            $table->boolean("expired")->default(false);
            $table->string("account_type");
            $table->timestamp("assigned_at")->nullable();
            $table->unsignedBigInteger("customer_id");
            $table->string("customer_first_name");
            $table->string("customer_last_name");
            $table->string("customer_email");
            $table->string("customer_code")->unique();
            $table->string("customer_phone");
            $table->string("customer_risk_action")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists("paystack_d_v_a_s");
    }
}
