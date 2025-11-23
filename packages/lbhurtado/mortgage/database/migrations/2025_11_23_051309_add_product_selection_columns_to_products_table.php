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
        Schema::table('products', function (Blueprint $table) {
            $table->integer('base_priority')->default(50)->after('lending_institution');
            $table->decimal('commission_rate', 5, 4)->default(0)->after('base_priority');
            $table->boolean('is_featured')->default(false)->after('commission_rate');
            $table->decimal('boost_multiplier', 3, 2)->default(1.0)->after('is_featured');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['base_priority', 'commission_rate', 'is_featured', 'boost_multiplier']);
        });
    }
};
