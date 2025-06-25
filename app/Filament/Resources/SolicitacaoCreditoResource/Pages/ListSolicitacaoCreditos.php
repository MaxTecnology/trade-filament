<?php

namespace App\Filament\Resources\SolicitacaoCreditoResource\Pages;

use App\Filament\Resources\SolicitacaoCreditoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSolicitacaoCreditos extends ListRecords
{
    protected static string $resource = SolicitacaoCreditoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
