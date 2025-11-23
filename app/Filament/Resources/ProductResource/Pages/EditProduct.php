<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Remove price from data to prevent Price object from being serialized by Livewire
        unset($data['price']);

        return $data;
    }

    protected function handleRecordUpdate(\Illuminate\Database\Eloquent\Model $record, array $data): \Illuminate\Database\Eloquent\Model
    {
        // Get the price_amount from form data before saving
        $priceAmount = $this->data['price_amount'] ?? null;

        // Update the record with other fields
        $record->update($data);

        // Manually update price if provided
        if ($priceAmount !== null) {
            $record->update(['price' => $priceAmount]);
        }

        return $record;
    }
}
