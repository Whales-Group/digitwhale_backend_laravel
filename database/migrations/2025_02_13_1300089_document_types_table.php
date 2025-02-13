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
        Schema::dropIfExists('document_types');
        Schema::dropIfExists('user_documents');

        // Migrations
        Schema::create('document_types', function (Blueprint $table) {
            $table->id();
            $table->char('country_code', 3);
            $table->string('name');
            $table->enum('input_type', ['image', 'text']);
            $table->boolean('is_required');
            $table->timestamps();

            $table->index('country_code');

        });

        Schema::create('user_documents', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained();
            $table->foreignId('document_type_id')->constrained();
            $table->string('value');
            $table->enum('status', ['pending', 'verified', 'rejected'])->default('pending');
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('document_type_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
