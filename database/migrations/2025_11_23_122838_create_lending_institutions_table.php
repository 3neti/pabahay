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
        Schema::create('lending_institutions', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // hdmf, rcbc, cbc
            $table->string('name');
            $table->string('alias');
            $table->string('type'); // government financial institution, universal bank, etc.
            $table->boolean('is_active')->default(true);
            
            // Interest rates & fees
            $table->decimal('interest_rate', 8, 6); // e.g., 0.0625 for 6.25%
            $table->decimal('percent_dp', 8, 6)->default(0); // down payment percentage
            $table->decimal('percent_mf', 8, 6)->default(0); // miscellaneous fees percentage
            
            // Age & term limits
            $table->integer('borrowing_age_minimum')->default(18);
            $table->integer('borrowing_age_maximum')->default(65);
            $table->integer('borrowing_age_offset')->default(0);
            $table->integer('maximum_term')->default(30); // in years
            $table->integer('maximum_paying_age')->default(70);
            
            // Financial multipliers
            $table->decimal('buffer_margin', 8, 6)->default(0.1);
            $table->decimal('income_requirement_multiplier', 8, 6)->default(0.35);
            $table->decimal('loanable_value_multiplier', 8, 6)->default(1.0);
            
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lending_institutions');
    }
};
