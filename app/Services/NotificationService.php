<?php

namespace App\Services;

use App\Models\Cobranca;
use App\Models\Usuario;
use App\Notifications\CobrancaNotification;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Notificar sobre cobrança de alto valor
     */
    public static function notificarAltoValor(Cobranca $cobranca): void
    {
        try {
            // Notificar gerente
            if ($cobranca->gerente) {
                $cobranca->gerente->notify(new CobrancaNotification($cobranca, 'alto_valor'));
            }

            // Notificar administradores
            $admins = Usuario::where('permissoes_do_usuario->admin', true)->get();
            foreach ($admins as $admin) {
                $admin->notify(new CobrancaNotification($cobranca, 'alto_valor'));
            }

            Log::info('Notificações de alto valor enviadas', [
                'cobranca_id' => $cobranca->id,
                'valor' => $cobranca->valor_fatura,
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao enviar notificações de alto valor', [
                'cobranca_id' => $cobranca->id,
                'erro' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notificar sobre pagamento
     */
    public static function notificarPagamento(Cobranca $cobranca): void
    {
        try {
            if ($cobranca->gerente) {
                $cobranca->gerente->notify(new CobrancaNotification($cobranca, 'pagamento'));
            }

            Log::info('Notificação de pagamento enviada', [
                'cobranca_id' => $cobranca->id,
                'gerente_id' => $cobranca->gerente_conta_id,
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao enviar notificação de pagamento', [
                'cobranca_id' => $cobranca->id,
                'erro' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notificar sobre inadimplência
     */
    public static function notificarInadimplencia(Cobranca $cobranca): void
    {
        try {
            // Notificar gerente
            if ($cobranca->gerente) {
                $cobranca->gerente->notify(new CobrancaNotification($cobranca, 'inadimplencia'));
            }

            // Se for crítico (>30 dias), notificar admins também
            if ($cobranca->dias_atraso > 30) {
                $admins = Usuario::where('permissoes_do_usuario->admin', true)->get();
                foreach ($admins as $admin) {
                    $admin->notify(new CobrancaNotification($cobranca, 'inadimplencia'));
                }
            }

            Log::warning('Notificações de inadimplência enviadas', [
                'cobranca_id' => $cobranca->id,
                'dias_atraso' => $cobranca->dias_atraso,
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao enviar notificações de inadimplência', [
                'cobranca_id' => $cobranca->id,
                'erro' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notificar sobre vencimento próximo
     */
    public static function notificarVencimentoProximo(Cobranca $cobranca, int $diasParaVencer): void
    {
        try {
            if ($cobranca->gerente) {
                $cobranca->gerente->notify(new CobrancaNotification(
                    $cobranca,
                    'vencimento_proximo',
                    ['dias_para_vencer' => $diasParaVencer]
                ));
            }

            Log::info('Notificação de vencimento próximo enviada', [
                'cobranca_id' => $cobranca->id,
                'dias_para_vencer' => $diasParaVencer,
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao enviar notificação de vencimento próximo', [
                'cobranca_id' => $cobranca->id,
                'erro' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notificar múltiplas cobranças vencidas (para relatórios)
     */
    public static function notificarRelatorioInadimplencia(array $cobrancasVencidas): void
    {
        try {
            $gerentes = collect($cobrancasVencidas)
                ->groupBy('gerente_conta_id')
                ->keys()
                ->filter();

            foreach ($gerentes as $gerenteId) {
                $gerente = Usuario::find($gerenteId);
                if (!$gerente) continue;

                $cobrancasDoGerente = collect($cobrancasVencidas)
                    ->where('gerente_conta_id', $gerenteId);

                // Aqui você poderia criar uma notificação específica para relatórios
                // Por agora, vamos usar a notificação individual para a primeira cobrança
                $primeiraCobranca = $cobrancasDoGerente->first();
                if ($primeiraCobranca) {
                    $gerente->notify(new CobrancaNotification(
                        $primeiraCobranca,
                        'inadimplencia',
                        [
                            'total_cobrancas' => $cobrancasDoGerente->count(),
                            'valor_total' => $cobrancasDoGerente->sum('valor_fatura'),
                        ]
                    ));
                }
            }

            Log::info('Relatório de inadimplência enviado para gerentes', [
                'total_cobrancas' => count($cobrancasVencidas),
                'gerentes_notificados' => $gerentes->count(),
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao enviar relatório de inadimplência', [
                'erro' => $e->getMessage(),
            ]);
        }
    }
}
