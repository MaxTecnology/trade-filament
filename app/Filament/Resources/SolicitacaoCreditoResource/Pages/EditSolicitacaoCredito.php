<?php

namespace App\Filament\Resources\SolicitacaoCreditoResource\Pages;

use App\Filament\Resources\SolicitacaoCreditoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSolicitacaoCredito extends EditRecord
{
    protected static string $resource = SolicitacaoCreditoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
