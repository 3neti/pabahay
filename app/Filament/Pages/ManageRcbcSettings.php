<?php

namespace App\Filament\Pages;

use App\Settings\RcbcSettings;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\SettingsPage;

class ManageRcbcSettings extends SettingsPage
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    
    public static function canAccess(): bool
    {
        return auth()->user()?->is_admin ?? false;
    }

    protected static string $settings = RcbcSettings::class;
    
    protected static ?string $navigationGroup = 'Mortgage';
    
    protected static ?int $navigationSort = 11;
    
    protected static ?string $title = 'RCBC Savings Bank Settings';
    
    protected static ?string $navigationLabel = 'RCBC Settings';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('alias')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('type')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->columns(2),
                    
                Forms\Components\Section::make('Interest Rates & Fees')
                    ->schema([
                        Forms\Components\TextInput::make('interest_rate')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(1)
                            ->step(0.0001)
                            ->suffix('%')
                            ->helperText('Enter as decimal (e.g., 0.0625 for 6.25%)'),
                        Forms\Components\TextInput::make('percent_dp')
                            ->label('Down Payment %')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(1)
                            ->step(0.01)
                            ->helperText('Enter as decimal (e.g., 0.10 for 10%)'),
                        Forms\Components\TextInput::make('percent_mf')
                            ->label('Miscellaneous Fees %')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(1)
                            ->step(0.01)
                            ->helperText('Enter as decimal (e.g., 0.085 for 8.5%)'),
                    ])
                    ->columns(3),
                    
                Forms\Components\Section::make('Age & Term Limits')
                    ->schema([
                        Forms\Components\TextInput::make('borrowing_age_minimum')
                            ->label('Minimum Borrowing Age')
                            ->required()
                            ->numeric()
                            ->minValue(18)
                            ->maxValue(65),
                        Forms\Components\TextInput::make('borrowing_age_maximum')
                            ->label('Maximum Borrowing Age')
                            ->required()
                            ->numeric()
                            ->minValue(18)
                            ->maxValue(65),
                        Forms\Components\TextInput::make('borrowing_age_offset')
                            ->label('Age Offset')
                            ->required()
                            ->numeric()
                            ->default(-1),
                        Forms\Components\TextInput::make('maximum_term')
                            ->label('Maximum Loan Term (years)')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(30),
                        Forms\Components\TextInput::make('maximum_paying_age')
                            ->label('Maximum Paying Age')
                            ->required()
                            ->numeric()
                            ->minValue(60)
                            ->maxValue(75),
                    ])
                    ->columns(3),
                    
                Forms\Components\Section::make('Financial Multipliers')
                    ->schema([
                        Forms\Components\TextInput::make('buffer_margin')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(1)
                            ->step(0.01)
                            ->helperText('Enter as decimal'),
                        Forms\Components\TextInput::make('income_requirement_multiplier')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(1)
                            ->step(0.01)
                            ->helperText('Enter as decimal'),
                        Forms\Components\TextInput::make('loanable_value_multiplier')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(1)
                            ->step(0.01)
                            ->helperText('Enter as decimal'),
                    ])
                    ->columns(3),
            ]);
    }
}
