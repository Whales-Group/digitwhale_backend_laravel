<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUniqueConstraintToUserDocumentsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('user_documents', function (Blueprint $table) {
            // Add a unique constraint on user_id and document_type_id
            $table->unique(['user_id', 'document_type_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_documents', function (Blueprint $table) {
            // Drop the unique constraint
            $table->dropUnique(['user_id', 'document_type_id']);
        });
    }
}