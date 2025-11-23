<?php

namespace App\Filament\Resources\LendingInstitutionResource\Pages;

use App\Filament\Resources\LendingInstitutionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLendingInstitution extends EditRecord
{
    protected static string $resource = LendingInstitutionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
