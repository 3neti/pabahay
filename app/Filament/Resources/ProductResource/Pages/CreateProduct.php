<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Convert price_amount to price (the actual field name)
        if (isset($data['price_amount'])) {
            $data['price'] = $data['price_amount'];
            unset($data['price_amount']);
        }

        return $data;
    }
}
