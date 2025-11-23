<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LendingInstitutionResource\Pages;
use App\Models\LendingInstitution;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LendingInstitutionResource extends Resource
{
    protected static ?string $model = LendingInstitution::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-library';

    protected static ?string $navigationGroup = 'Mortgage';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Lending Institutions';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(50)
                            ->helperText('Unique identifier (e.g., hdmf, rcbc, cbc)'),
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('alias')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Short name (e.g., Pag-IBIG, RCBC)'),
                        Forms\Components\TextInput::make('type')
                            ->required()
                            ->maxLength(255)
                            ->helperText('e.g., government financial institution, universal bank'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Inactive institutions won\'t appear in product selection'),
                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),
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
                        Forms\Components\TextInput::make('processing_fee')
                            ->label('Processing Fee')
                            ->required()
                            ->numeric()
                            ->prefix('₱')
                            ->minValue(0)
                            ->default(0)
                            ->helperText('Fixed processing fee amount'),
                        Forms\Components\Toggle::make('default_add_mri')
                            ->label('Add MRI by Default')
                            ->default(false)
                            ->helperText('Mortgage Redemption Insurance'),
                        Forms\Components\Toggle::make('default_add_fi')
                            ->label('Add FI by Default')
                            ->default(false)
                            ->helperText('Fire Insurance'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Age & Term Limits')
                    ->schema([
                        Forms\Components\TextInput::make('borrowing_age_minimum')
                            ->label('Minimum Borrowing Age')
                            ->required()
                            ->numeric()
                            ->minValue(18)
                            ->maxValue(65)
                            ->default(18),
                        Forms\Components\TextInput::make('borrowing_age_maximum')
                            ->label('Maximum Borrowing Age')
                            ->required()
                            ->numeric()
                            ->minValue(18)
                            ->maxValue(65)
                            ->default(60),
                        Forms\Components\TextInput::make('borrowing_age_offset')
                            ->label('Age Offset')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->helperText('Adjustment to max paying age calculation'),
                        Forms\Components\TextInput::make('maximum_term')
                            ->label('Maximum Loan Term (years)')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(30)
                            ->default(30),
                        Forms\Components\TextInput::make('maximum_paying_age')
                            ->label('Maximum Paying Age')
                            ->required()
                            ->numeric()
                            ->minValue(60)
                            ->maxValue(75)
                            ->default(70),
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
                            ->default(0.1)
                            ->helperText('Enter as decimal'),
                        Forms\Components\TextInput::make('income_requirement_multiplier')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(1)
                            ->step(0.01)
                            ->default(0.35)
                            ->helperText('Enter as decimal'),
                        Forms\Components\TextInput::make('loanable_value_multiplier')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(1.5)
                            ->step(0.01)
                            ->default(1.0)
                            ->helperText('Enter as decimal'),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->badge(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('alias')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('interest_rate')
                    ->label('Interest Rate')
                    ->formatStateUsing(fn ($state) => number_format($state * 100, 2).'%')
                    ->sortable(),
                Tables\Columns\TextColumn::make('percent_dp')
                    ->label('Down Payment')
                    ->formatStateUsing(fn ($state) => number_format($state * 100, 2).'%')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('maximum_term')
                    ->label('Max Term')
                    ->suffix(' yrs')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('products_count')
                    ->counts('products')
                    ->label('Products')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        '1' => 'Active',
                        '0' => 'Inactive',
                    ]),
                SelectFilter::make('type')
                    ->options([
                        'government financial institution' => 'Government',
                        'universal bank' => 'Universal Bank',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('code', 'asc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLendingInstitutions::route('/'),
            'create' => Pages\CreateLendingInstitution::route('/create'),
            'edit' => Pages\EditLendingInstitution::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('is_active', true)->count();
    }
}
