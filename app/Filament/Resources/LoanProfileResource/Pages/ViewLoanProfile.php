<?php

namespace App\Filament\Resources\LoanProfileResource\Pages;

use App\Filament\Resources\LoanProfileResource;
use Filament\Actions;
use Filament\Infolists\Components;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\FontWeight;

class ViewLoanProfile extends ViewRecord
{
    protected static string $resource = LoanProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
    
    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Reference Information')
                    ->schema([
                        Components\TextEntry::make('reference_code')
                            ->label('Reference Code')
                            ->weight(FontWeight::Bold)
                            ->size('lg')
                            ->copyable(),
                        Components\TextEntry::make('created_at')
                            ->label('Created')
                            ->dateTime('F j, Y \\a\\t g:i A'),
                    ])
                    ->columns(2),
                    
                Components\Section::make('Borrower Information')
                    ->schema([
                        Components\TextEntry::make('borrower_name')
                            ->label('Borrower Name')
                            ->default('Not provided'),
                        Components\TextEntry::make('borrower_email')
                            ->label('Email Address')
                            ->default('Not provided'),
                        Components\TextEntry::make('inputs.age')
                            ->label('Age')
                            ->suffix(' years'),
                        Components\TextEntry::make('inputs.monthly_gross_income')
                            ->label('Monthly Gross Income')
                            ->money('PHP'),
                        Components\TextEntry::make('inputs.co_borrower_age')
                            ->label('Co-Borrower Age')
                            ->suffix(' years')
                            ->default('N/A'),
                        Components\TextEntry::make('inputs.co_borrower_income')
                            ->label('Co-Borrower Income')
                            ->money('PHP')
                            ->default('N/A'),
                    ])
                    ->columns(2),
                    
                Components\Section::make('Loan Details')
                    ->schema([
                        Components\TextEntry::make('lending_institution')
                            ->label('Lending Institution')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'hdmf' => 'success',
                                'rcbc' => 'info',
                                'cbc' => 'warning',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'hdmf' => 'HDMF (Pag-IBIG)',
                                'rcbc' => 'RCBC Savings Bank',
                                'cbc' => 'China Banking Corporation',
                                default => strtoupper($state),
                            }),
                        Components\TextEntry::make('total_contract_price')
                            ->label('Total Contract Price')
                            ->money('PHP')
                            ->weight(FontWeight::Bold),
                        Components\TextEntry::make('computation.interest_rate')
                            ->label('Interest Rate')
                            ->formatStateUsing(fn ($state) => number_format($state * 100, 2) . '%'),
                        Components\TextEntry::make('computation.balance_payment_term')
                            ->label('Loan Term')
                            ->suffix(' years'),
                    ])
                    ->columns(2),
                    
                Components\Section::make('Computation Results')
                    ->schema([
                        Components\TextEntry::make('computation.monthly_amortization')
                            ->label('Monthly Amortization')
                            ->money('PHP')
                            ->size('lg')
                            ->weight(FontWeight::Bold)
                            ->color('success'),
                        Components\TextEntry::make('computation.loanable_amount')
                            ->label('Loanable Amount')
                            ->money('PHP')
                            ->weight(FontWeight::Bold),
                        Components\TextEntry::make('required_equity')
                            ->label('Required Equity')
                            ->money('PHP')
                            ->weight(FontWeight::Bold),
                        Components\TextEntry::make('computation.cash_out')
                            ->label('Cash Out')
                            ->money('PHP'),
                        Components\TextEntry::make('computation.miscellaneous_fees')
                            ->label('Miscellaneous Fees')
                            ->money('PHP'),
                        Components\TextEntry::make('computation.add_on_fees')
                            ->label('Add-on Fees')
                            ->money('PHP'),
                        Components\TextEntry::make('computation.percent_down_payment')
                            ->label('Down Payment %')
                            ->formatStateUsing(fn ($state) => number_format($state * 100, 2) . '%'),
                        Components\TextEntry::make('computation.percent_miscellaneous_fees')
                            ->label('Miscellaneous Fees %')
                            ->formatStateUsing(fn ($state) => number_format($state * 100, 2) . '%'),
                    ])
                    ->columns(2),
                    
                Components\Section::make('Qualification Assessment')
                    ->schema([
                        Components\TextEntry::make('qualified')
                            ->label('Qualification Status')
                            ->badge()
                            ->formatStateUsing(fn (bool $state): string => $state ? 'Qualified' : 'Not Qualified')
                            ->color(fn (bool $state): string => $state ? 'success' : 'danger'),
                        Components\TextEntry::make('reason')
                            ->label('Reason')
                            ->default('N/A'),
                        Components\TextEntry::make('computation.monthly_disposable_income')
                            ->label('Monthly Disposable Income')
                            ->money('PHP'),
                        Components\TextEntry::make('income_gap')
                            ->label('Income Gap')
                            ->money('PHP')
                            ->visible(fn ($record) => !$record->qualified && $record->income_gap > 0),
                        Components\TextEntry::make('suggested_down_payment_percent')
                            ->label('Suggested Down Payment %')
                            ->formatStateUsing(fn ($state) => $state ? number_format($state * 100, 2) . '%' : 'N/A')
                            ->visible(fn ($record) => !$record->qualified),
                    ])
                    ->columns(2),
            ]);
    }
}
