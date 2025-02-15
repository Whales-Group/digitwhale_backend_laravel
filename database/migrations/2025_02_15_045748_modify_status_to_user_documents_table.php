<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop the existing column
        Schema::table('user_documents', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        // Recreate the column with the new ENUM values
        Schema::table('user_documents', function (Blueprint $table) {
            $table->enum('status', ['Pending', 'Verified', 'Rejected', 'Failed', 'None'])
                ->default('Pending')
                ->after('comment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the column again
        Schema::table('user_documents', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        // Recreate the column with the original ENUM values
        Schema::table('user_documents', function (Blueprint $table) {
            $table->enum('status', ['Pending', 'Verified', 'Rejected', 'Failed', 'None'])
                ->default('Pending')
                ->after('comment');
        });
    }
};