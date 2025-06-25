<?php

namespace App\Filament\Resources\PlanoResource\Pages;

use App\Filament\Resources\PlanoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPlanos extends ListRecords
{
    protected static string $resource = PlanoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
