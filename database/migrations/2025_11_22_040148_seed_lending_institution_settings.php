<?php

use Illuminate\Database\Migrations\Migration;
use App\Settings\HdmfSettings;
use App\Settings\RcbcSettings;
use App\Settings\CbcSettings;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $config = config('mortgage.lending_institutions');
        
        // Seed HDMF settings
        \Illuminate\Support\Facades\DB::table('settings')->insert([
            'group' => 'hdmf',
            'name' => 'name',
            'payload' => json_encode($config['hdmf']['name']),
            'locked' => false,
        ]);
        \Illuminate\Support\Facades\DB::table('settings')->insert([
            ['group' => 'hdmf', 'name' => 'alias', 'payload' => json_encode($config['hdmf']['alias']), 'locked' => false],
            ['group' => 'hdmf', 'name' => 'type', 'payload' => json_encode($config['hdmf']['type']), 'locked' => false],
            ['group' => 'hdmf', 'name' => 'interest_rate', 'payload' => json_encode($config['hdmf']['interest_rate']), 'locked' => false],
            ['group' => 'hdmf', 'name' => 'percent_dp', 'payload' => json_encode($config['hdmf']['percent_dp']), 'locked' => false],
            ['group' => 'hdmf', 'name' => 'percent_mf', 'payload' => json_encode($config['hdmf']['percent_mf']), 'locked' => false],
            ['group' => 'hdmf', 'name' => 'borrowing_age_minimum', 'payload' => json_encode($config['hdmf']['borrowing_age']['minimum']), 'locked' => false],
            ['group' => 'hdmf', 'name' => 'borrowing_age_maximum', 'payload' => json_encode($config['hdmf']['borrowing_age']['maximum']), 'locked' => false],
            ['group' => 'hdmf', 'name' => 'borrowing_age_offset', 'payload' => json_encode($config['hdmf']['borrowing_age']['offset']), 'locked' => false],
            ['group' => 'hdmf', 'name' => 'maximum_term', 'payload' => json_encode($config['hdmf']['maximum_term']), 'locked' => false],
            ['group' => 'hdmf', 'name' => 'maximum_paying_age', 'payload' => json_encode($config['hdmf']['maximum_paying_age']), 'locked' => false],
            ['group' => 'hdmf', 'name' => 'buffer_margin', 'payload' => json_encode($config['hdmf']['buffer_margin']), 'locked' => false],
            ['group' => 'hdmf', 'name' => 'income_requirement_multiplier', 'payload' => json_encode($config['hdmf']['income_requirement_multiplier']), 'locked' => false],
            ['group' => 'hdmf', 'name' => 'loanable_value_multiplier', 'payload' => json_encode($config['hdmf']['loanable_value_multiplier']), 'locked' => false],
        ]);
        
        // Seed RCBC settings
        \Illuminate\Support\Facades\DB::table('settings')->insert([
            ['group' => 'rcbc', 'name' => 'name', 'payload' => json_encode($config['rcbc']['name']), 'locked' => false],
            ['group' => 'rcbc', 'name' => 'alias', 'payload' => json_encode($config['rcbc']['alias']), 'locked' => false],
            ['group' => 'rcbc', 'name' => 'type', 'payload' => json_encode($config['rcbc']['type']), 'locked' => false],
            ['group' => 'rcbc', 'name' => 'interest_rate', 'payload' => json_encode($config['rcbc']['interest_rate']), 'locked' => false],
            ['group' => 'rcbc', 'name' => 'percent_dp', 'payload' => json_encode($config['rcbc']['percent_dp']), 'locked' => false],
            ['group' => 'rcbc', 'name' => 'percent_mf', 'payload' => json_encode($config['rcbc']['percent_mf']), 'locked' => false],
            ['group' => 'rcbc', 'name' => 'borrowing_age_minimum', 'payload' => json_encode($config['rcbc']['borrowing_age']['minimum']), 'locked' => false],
            ['group' => 'rcbc', 'name' => 'borrowing_age_maximum', 'payload' => json_encode($config['rcbc']['borrowing_age']['maximum']), 'locked' => false],
            ['group' => 'rcbc', 'name' => 'borrowing_age_offset', 'payload' => json_encode($config['rcbc']['borrowing_age']['offset']), 'locked' => false],
            ['group' => 'rcbc', 'name' => 'maximum_term', 'payload' => json_encode($config['rcbc']['maximum_term']), 'locked' => false],
            ['group' => 'rcbc', 'name' => 'maximum_paying_age', 'payload' => json_encode($config['rcbc']['maximum_paying_age']), 'locked' => false],
            ['group' => 'rcbc', 'name' => 'buffer_margin', 'payload' => json_encode($config['rcbc']['buffer_margin']), 'locked' => false],
            ['group' => 'rcbc', 'name' => 'income_requirement_multiplier', 'payload' => json_encode($config['rcbc']['income_requirement_multiplier']), 'locked' => false],
            ['group' => 'rcbc', 'name' => 'loanable_value_multiplier', 'payload' => json_encode($config['rcbc']['loanable_value_multiplier']), 'locked' => false],
        ]);
        
        // Seed CBC settings
        \Illuminate\Support\Facades\DB::table('settings')->insert([
            ['group' => 'cbc', 'name' => 'name', 'payload' => json_encode($config['cbc']['name']), 'locked' => false],
            ['group' => 'cbc', 'name' => 'alias', 'payload' => json_encode($config['cbc']['alias']), 'locked' => false],
            ['group' => 'cbc', 'name' => 'type', 'payload' => json_encode($config['cbc']['type']), 'locked' => false],
            ['group' => 'cbc', 'name' => 'interest_rate', 'payload' => json_encode($config['cbc']['interest_rate']), 'locked' => false],
            ['group' => 'cbc', 'name' => 'percent_dp', 'payload' => json_encode($config['cbc']['percent_dp']), 'locked' => false],
            ['group' => 'cbc', 'name' => 'percent_mf', 'payload' => json_encode($config['cbc']['percent_mf']), 'locked' => false],
            ['group' => 'cbc', 'name' => 'borrowing_age_minimum', 'payload' => json_encode($config['cbc']['borrowing_age']['minimum']), 'locked' => false],
            ['group' => 'cbc', 'name' => 'borrowing_age_maximum', 'payload' => json_encode($config['cbc']['borrowing_age']['maximum']), 'locked' => false],
            ['group' => 'cbc', 'name' => 'borrowing_age_offset', 'payload' => json_encode($config['cbc']['borrowing_age']['offset']), 'locked' => false],
            ['group' => 'cbc', 'name' => 'maximum_term', 'payload' => json_encode($config['cbc']['maximum_term']), 'locked' => false],
            ['group' => 'cbc', 'name' => 'maximum_paying_age', 'payload' => json_encode($config['cbc']['maximum_paying_age']), 'locked' => false],
            ['group' => 'cbc', 'name' => 'buffer_margin', 'payload' => json_encode($config['cbc']['buffer_margin']), 'locked' => false],
            ['group' => 'cbc', 'name' => 'income_requirement_multiplier', 'payload' => json_encode($config['cbc']['income_requirement_multiplier']), 'locked' => false],
            ['group' => 'cbc', 'name' => 'loanable_value_multiplier', 'payload' => json_encode($config['cbc']['loanable_value_multiplier']), 'locked' => false],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Delete all settings
        \Illuminate\Support\Facades\DB::table('settings')
            ->whereIn('group', ['hdmf', 'rcbc', 'cbc'])
            ->delete();
    }
};
