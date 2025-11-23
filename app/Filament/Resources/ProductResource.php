<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use LBHurtado\Mortgage\Models\Product;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationGroup = 'Mortgage';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Product Information')
                    ->schema([
                        Forms\Components\TextInput::make('sku')
                            ->label('SKU')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Forms\Components\TextInput::make('name')
                            ->label('Product Name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('brand')
                            ->label('Brand')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('category')
                            ->label('Category')
                            ->options([
                                'Socialized' => 'Socialized',
                                'Economic' => 'Economic',
                                'Open Market' => 'Open Market',
                            ])
                            ->required()
                            ->searchable(),
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->required()
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Pricing & Institution')
                    ->schema([
                        Forms\Components\TextInput::make('price_amount')
                            ->label('Price')
                            ->required()
                            ->numeric()
                            ->prefix('₱')
                            ->minValue(0)
                            ->step(1000)
                            ->afterStateHydrated(function (Forms\Components\TextInput $component, $state, $record) {
                                if ($record && $record->price) {
                                    // Get amount from Price object
                                    $component->state($record->price->inclusive()->getAmount()->toFloat());
                                }
                            })
                            ->dehydrated(false),
                        Forms\Components\Select::make('lending_institution')
                            ->label('Lending Institution')
                            ->options([
                                'hdmf' => 'HDMF (Pag-IBIG)',
                                'rcbc' => 'RCBC Savings Bank',
                                'cbc' => 'China Banking Corporation',
                            ])
                            ->required()
                            ->searchable(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Selection & Priority')
                    ->schema([
                        Forms\Components\TextInput::make('base_priority')
                            ->label('Base Priority')
                            ->required()
                            ->numeric()
                            ->default(50)
                            ->minValue(0)
                            ->maxValue(100)
                            ->helperText('0-100, higher values appear first'),
                        Forms\Components\TextInput::make('commission_rate')
                            ->label('Commission Rate')
                            ->required()
                            ->numeric()
                            ->default(0.05)
                            ->minValue(0)
                            ->maxValue(1)
                            ->step(0.001)
                            ->helperText('Decimal format (e.g., 0.05 for 5%)'),
                        Forms\Components\Toggle::make('is_featured')
                            ->label('Featured Product')
                            ->default(false)
                            ->helperText('Featured products are highlighted'),
                        Forms\Components\TextInput::make('boost_multiplier')
                            ->label('Boost Multiplier')
                            ->required()
                            ->numeric()
                            ->default(1.0)
                            ->minValue(0.1)
                            ->maxValue(10)
                            ->step(0.1)
                            ->helperText('Priority multiplier (e.g., 1.5 for 50% boost)'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Product')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('brand')
                    ->label('Brand')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('category')
                    ->label('Category')
                    ->badge()
                    ->sortable()
                    ->color(fn (string $state): string => match ($state) {
                        'Socialized' => 'success',
                        'Economic' => 'info',
                        'Open Market' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('price')
                    ->label('Price')
                    ->formatStateUsing(fn ($record) => $record->price ? '₱'.number_format($record->price->inclusive()->getAmount()->toFloat(), 2) : '-')
                    ->sortable(),
                Tables\Columns\TextColumn::make('lending_institution')
                    ->label('Institution')
                    ->badge()
                    ->sortable()
                    ->color(fn (string $state): string => match ($state) {
                        'hdmf' => 'success',
                        'rcbc' => 'info',
                        'cbc' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'hdmf' => 'HDMF',
                        'rcbc' => 'RCBC',
                        'cbc' => 'CBC',
                        default => strtoupper($state),
                    }),
                Tables\Columns\TextColumn::make('base_priority')
                    ->label('Priority')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('commission_rate')
                    ->label('Commission')
                    ->formatStateUsing(fn ($state): string => number_format($state * 100, 2).'%')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_featured')
                    ->label('Featured')
                    ->boolean()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('boost_multiplier')
                    ->label('Boost')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->options([
                        'Socialized' => 'Socialized',
                        'Economic' => 'Economic',
                        'Open Market' => 'Open Market',
                    ]),
                SelectFilter::make('lending_institution')
                    ->label('Institution')
                    ->options([
                        'hdmf' => 'HDMF (Pag-IBIG)',
                        'rcbc' => 'RCBC Savings Bank',
                        'cbc' => 'China Banking Corporation',
                    ]),
                SelectFilter::make('is_featured')
                    ->label('Featured')
                    ->options([
                        '1' => 'Featured',
                        '0' => 'Not Featured',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('export_csv')
                        ->label('Export CSV')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->action(function (Collection $records) {
                            $csvData = [];
                            $csvData[] = [
                                'SKU',
                                'Name',
                                'Brand',
                                'Category',
                                'Description',
                                'Price',
                                'Lending Institution',
                                'Base Priority',
                                'Commission Rate',
                                'Is Featured',
                                'Boost Multiplier',
                            ];

                            foreach ($records as $record) {
                                $csvData[] = [
                                    $record->sku,
                                    $record->name,
                                    $record->brand,
                                    $record->category,
                                    $record->description,
                                    $record->price ? $record->price->inclusive()->getAmount()->toFloat() : 0,
                                    $record->lending_institution,
                                    $record->base_priority,
                                    $record->commission_rate,
                                    $record->is_featured ? 'Yes' : 'No',
                                    $record->boost_multiplier,
                                ];
                            }

                            $filename = 'products-'.now()->format('Y-m-d-His').'.csv';
                            $handle = fopen('php://temp', 'r+');
                            foreach ($csvData as $row) {
                                fputcsv($handle, $row);
                            }
                            rewind($handle);
                            $csv = stream_get_contents($handle);
                            fclose($handle);

                            return response()->streamDownload(
                                fn () => print ($csv),
                                $filename,
                                ['Content-Type' => 'text/csv']
                            );
                        }),
                ]),
            ])
            ->defaultSort('base_priority', 'desc');
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
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'view' => Pages\ViewProduct::route('/{record}'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}
