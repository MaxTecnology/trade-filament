<?php

namespace App\Filament\Resources\FundoPermutaResource\Pages;

use App\Filament\Resources\FundoPermutaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFundoPermutas extends ListRecords
{
    protected static string $resource = FundoPermutaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
