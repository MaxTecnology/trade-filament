<?php

namespace App\Filament\Resources\FundoPermutaResource\Pages;

use App\Filament\Resources\FundoPermutaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFundoPermuta extends EditRecord
{
    protected static string $resource = FundoPermutaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
