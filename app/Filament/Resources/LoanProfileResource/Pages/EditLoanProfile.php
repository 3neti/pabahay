<?php

namespace App\Filament\Resources\LoanProfileResource\Pages;

use App\Filament\Resources\LoanProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLoanProfile extends EditRecord
{
    protected static string $resource = LoanProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
