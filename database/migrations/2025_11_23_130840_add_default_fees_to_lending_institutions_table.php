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
        Schema::table('lending_institutions', function (Blueprint $table) {
            $table->decimal('processing_fee', 12, 2)->default(0)->after('percent_mf');
            $table->boolean('default_add_mri')->default(false)->after('processing_fee');
            $table->boolean('default_add_fi')->default(false)->after('default_add_mri');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lending_institutions', function (Blueprint $table) {
            $table->dropColumn(['processing_fee', 'default_add_mri', 'default_add_fi']);
        });
    }
};
