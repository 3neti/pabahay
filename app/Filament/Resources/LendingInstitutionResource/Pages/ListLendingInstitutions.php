<?php

namespace App\Filament\Resources\LendingInstitutionResource\Pages;

use App\Filament\Resources\LendingInstitutionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLendingInstitutions extends ListRecords
{
    protected static string $resource = LendingInstitutionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
