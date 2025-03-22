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
        // Create the model_conversations table
        Schema::create('model_conversations', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('status'); 
            $table->unsignedBigInteger('user_id');
            $table->timestamps();
            $table->softDeletes();
        });

        // Create the model_messages table
        Schema::create('model_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('conversation_id');
            $table->string('model_version'); // Identifier for the model (e.g., 'gpt-3.5-turbo')
            $table->unsignedBigInteger('user_id')->nullable();
            $table->boolean('is_model')->nullable();
            $table->text('message');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('model_messages');
        Schema::table('model_memories', function (Blueprint $table) {
            $table->string('model_version');
            $table->dropUnique('model_memories_user_id_unique');
        });
        Schema::dropIfExists('model_conversations');
    }
};