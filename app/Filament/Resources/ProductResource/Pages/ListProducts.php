<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;
use LBHurtado\Mortgage\Models\Product;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('import')
                ->label('Import CSV')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->form([
                    FileUpload::make('file')
                        ->label('CSV File')
                        ->acceptedFileTypes(['text/csv', 'application/csv'])
                        ->required()
                        ->helperText('Upload a CSV file with columns: SKU, Name, Brand, Category, Description, Price, Lending Institution, Base Priority, Commission Rate, Is Featured, Boost Multiplier'),
                ])
                ->action(function (array $data) {
                    $file = Storage::disk('local')->path($data['file']);
                    $csv = array_map('str_getcsv', file($file));
                    $header = array_shift($csv);

                    $imported = 0;
                    $errors = [];

                    foreach ($csv as $index => $row) {
                        try {
                            if (count($row) < 11) {
                                $errors[] = 'Row '.($index + 2).': Insufficient columns';

                                continue;
                            }

                            Product::updateOrCreate(
                                ['sku' => $row[0]],
                                [
                                    'name' => $row[1],
                                    'brand' => $row[2],
                                    'category' => $row[3],
                                    'description' => $row[4],
                                    'price' => (float) $row[5],
                                    'lending_institution' => $row[6],
                                    'base_priority' => (int) $row[7],
                                    'commission_rate' => (float) $row[8],
                                    'is_featured' => in_array(strtolower($row[9]), ['yes', '1', 'true']),
                                    'boost_multiplier' => (float) $row[10],
                                ]
                            );
                            $imported++;
                        } catch (\Exception $e) {
                            $errors[] = 'Row '.($index + 2).': '.$e->getMessage();
                        }
                    }

                    // Clean up uploaded file
                    Storage::disk('local')->delete($data['file']);

                    if ($imported > 0) {
                        Notification::make()
                            ->title('Import completed')
                            ->body("{$imported} products imported successfully".(count($errors) > 0 ? '. '.count($errors).' errors occurred.' : ''))
                            ->success()
                            ->send();
                    }

                    if (count($errors) > 0) {
                        Notification::make()
                            ->title('Import errors')
                            ->body(implode("\n", array_slice($errors, 0, 5)).(count($errors) > 5 ? "\n...and ".(count($errors) - 5).' more' : ''))
                            ->warning()
                            ->send();
                    }
                }),
        ];
    }
}
