<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LoanProfileResource\Pages;
use LBHurtado\Mortgage\Models\LoanProfile;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Mail;
use App\Mail\MortgageComputationMail;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;

class LoanProfileResource extends Resource
{
    protected static ?string $model = LoanProfile::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    
    protected static ?string $navigationGroup = 'Mortgage';
    
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Borrower Information')
                    ->schema([
                        Forms\Components\TextInput::make('borrower_name')
                            ->label('Borrower Name'),
                        Forms\Components\TextInput::make('borrower_email')
                            ->label('Email')
                            ->email(),
                    ])
                    ->columns(2),
                    
                Forms\Components\Section::make('Loan Details')
                    ->schema([
                        Forms\Components\TextInput::make('reference_code')
                            ->label('Reference Code')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\Select::make('lending_institution')
                            ->label('Lending Institution')
                            ->options([
                                'hdmf' => 'HDMF (Pag-IBIG)',
                                'rcbc' => 'RCBC Savings Bank',
                                'cbc' => 'China Banking Corporation',
                            ])
                            ->disabled(),
                        Forms\Components\TextInput::make('total_contract_price')
                            ->label('Total Contract Price')
                            ->numeric()
                            ->prefix('₱')
                            ->disabled(),
                        Forms\Components\Toggle::make('qualified')
                            ->label('Qualified')
                            ->disabled(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference_code')
                    ->label('Reference')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('borrower_name')
                    ->label('Borrower')
                    ->searchable()
                    ->sortable()
                    ->default('—'),
                Tables\Columns\TextColumn::make('borrower_email')
                    ->label('Email')
                    ->searchable()
                    ->default('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('lending_institution')
                    ->label('Institution')
                    ->sortable()
                    ->badge()
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
                Tables\Columns\TextColumn::make('total_contract_price')
                    ->label('Contract Price')
                    ->money('PHP')
                    ->sortable(),
                Tables\Columns\TextColumn::make('computation.monthly_amortization')
                    ->label('Monthly Amortization')
                    ->money('PHP')
                    ->sortable(),
                Tables\Columns\IconColumn::make('qualified')
                    ->label('Qualified')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('lending_institution')
                    ->label('Institution')
                    ->options([
                        'hdmf' => 'HDMF (Pag-IBIG)',
                        'rcbc' => 'RCBC Savings Bank',
                        'cbc' => 'China Banking Corporation',
                    ]),
                SelectFilter::make('qualified')
                    ->label('Qualification Status')
                    ->options([
                        '1' => 'Qualified',
                        '0' => 'Not Qualified',
                    ]),
                Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Created From'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Created Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('download_pdf')
                    ->label('Export PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(function (LoanProfile $record) {
                        $pdf = Pdf::loadView('pdf.loan-profile', ['loanProfile' => $record]);
                        return response()->streamDownload(
                            fn () => print($pdf->output()),
                            "loan-profile-{$record->reference_code}.pdf"
                        );
                    }),
                Tables\Actions\Action::make('resend_email')
                    ->label('Resend Email')
                    ->icon('heroicon-o-envelope')
                    ->requiresConfirmation()
                    ->hidden(fn (LoanProfile $record) => !$record->borrower_email)
                    ->action(function (LoanProfile $record) {
                        Mail::to($record->borrower_email)
                            ->send(new MortgageComputationMail($record));
                    })
                    ->successNotificationTitle('Email sent successfully')
                    ->color('success'),
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
                                'Reference Code',
                                'Lending Institution',
                                'Borrower Name',
                                'Borrower Email',
                                'Total Contract Price',
                                'Monthly Amortization',
                                'Loanable Amount',
                                'Required Equity',
                                'Qualified',
                                'Created At',
                            ];
                            
                            foreach ($records as $record) {
                                $csvData[] = [
                                    $record->reference_code,
                                    strtoupper($record->lending_institution),
                                    $record->borrower_name ?? 'N/A',
                                    $record->borrower_email ?? 'N/A',
                                    $record->total_contract_price,
                                    $record->computation['monthly_amortization'] ?? 0,
                                    $record->computation['loanable_amount'] ?? 0,
                                    $record->required_equity,
                                    $record->qualified ? 'Yes' : 'No',
                                    $record->created_at->format('Y-m-d H:i:s'),
                                ];
                            }
                            
                            $filename = 'loan-profiles-' . now()->format('Y-m-d-His') . '.csv';
                            $handle = fopen('php://temp', 'r+');
                            foreach ($csvData as $row) {
                                fputcsv($handle, $row);
                            }
                            rewind($handle);
                            $csv = stream_get_contents($handle);
                            fclose($handle);
                            
                            return response()->streamDownload(
                                fn () => print($csv),
                                $filename,
                                ['Content-Type' => 'text/csv']
                            );
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListLoanProfiles::route('/'),
            'view' => Pages\ViewLoanProfile::route('/{record}'),
            'edit' => Pages\EditLoanProfile::route('/{record}/edit'),
        ];
    }
    
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}
