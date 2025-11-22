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
        Schema::table('loan_profiles', function (Blueprint $table) {
            $table->index('reference_code');
            $table->index('lending_institution');
            $table->index('qualified');
            $table->index('created_at');
            $table->index('borrower_email');
            $table->index(['lending_institution', 'qualified'], 'idx_institution_qualified');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loan_profiles', function (Blueprint $table) {
            $table->dropIndex(['reference_code']);
            $table->dropIndex(['lending_institution']);
            $table->dropIndex(['qualified']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['borrower_email']);
            $table->dropIndex('idx_institution_qualified');
        });
    }
};
