<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaystackCustomersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create("paystack_customers", function (Blueprint $table) {
            $table->id();
            $table->string("email")->unique();
            $table->string("integration");
            $table->string("domain");
            $table->string("customer_code")->unique();
            $table->unsignedBigInteger("paystack_id")->unique(); // Corresponds to Paystack "id" field
            $table->boolean("identified")->default(false);
            $table->json("identifications")->nullable();
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
        Schema::dropIfExists("paystack_customers");
    }
}
