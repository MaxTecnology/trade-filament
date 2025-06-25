<?php
// routes/console.php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// Comandos Artisan existentes (mantidos)
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ===== AGENDAMENTO DE COBRANÇAS (EXISTENTES - MANTIDOS) =====

// Processamento automático diário às 6h da manhã
Schedule::command('cobrancas:processar-automaticas')
    ->dailyAt('06:00')
    ->withoutOverlapping(10)
    ->runInBackground()
    ->description('Processamento automático diário de cobranças');

// Gerar cobranças mensais no dia 1º de cada mês às 7h
Schedule::command('cobrancas:gerar-mensais')
    ->cron('0 7 1 * *') // 7h do dia 1 de cada mês
    ->withoutOverlapping(15)
    ->runInBackground()
    ->description('Geração automática de cobranças mensais');

// Marcar cobranças vencidas - 3x por dia
Schedule::command('cobrancas:marcar-vencidas')
    ->cron('0 8,14,20 * * *') // 8h, 14h e 20h todos os dias
    ->withoutOverlapping(5)
    ->runInBackground()
    ->description('Marcação de cobranças vencidas');

// Relatório semanal às segundas-feiras às 8h
Schedule::command('cobrancas:processar-automaticas', ['--relatorio'])
    ->cron('0 8 * * 1') // 8h de segunda-feira
    ->withoutOverlapping(5)
    ->runInBackground()
    ->description('Relatório semanal de cobranças');

// Limpeza mensal no dia 28 às 23h
Schedule::command('cobrancas:processar-automaticas', ['--skip-gerar', '--skip-vencidas'])
    ->cron('0 23 28 * *') // 23h do dia 28 de cada mês
    ->withoutOverlapping(30)
    ->runInBackground()
    ->description('Limpeza mensal de dados de cobranças');

// ===== AGENDAMENTO DE TRANSAÇÕES (NOVOS - INTEGRADOS) =====

// Processamento de transações pendentes - após cobranças (6h30)
Schedule::command('transacoes:processar-pendentes', ['--auto-approve', '--limit=100'])
    ->dailyAt('06:30')
    ->withoutOverlapping(10)
    ->runInBackground()
    ->description('Processamento automático de transações pendentes')
    ->onSuccess(function () {
        \Log::info('✅ Processamento de transações pendentes executado com sucesso');
    })
    ->onFailure(function () {
        \Log::error('❌ Falha no processamento de transações pendentes');
    });

// Processar parcelamentos vencidos - integrado com cobranças (após 8h, 14h, 20h)
Schedule::command('transacoes:processar-parcelamentos-vencidos', ['--calcular-encargos'])
    ->cron('15 8,14,20 * * *') // 8h15, 14h15 e 20h15 (15min após cobranças)
    ->withoutOverlapping(5)
    ->runInBackground()
    ->description('Processar parcelamentos vencidos e calcular encargos')
    ->onSuccess(function () {
        \Log::info('✅ Processamento de parcelamentos vencidos executado');
    });

// Expirar vouchers vencidos - diário às 9h
Schedule::command('transacoes:expirar-vouchers', ['--dias-alerta=7'])
    ->dailyAt('09:00')
    ->withoutOverlapping(5)
    ->runInBackground()
    ->description('Expirar vouchers vencidos e alertar vencimentos próximos')
    ->onSuccess(function () {
        \Log::info('✅ Processamento de vouchers executado com sucesso');
    });

// Relatório de transações - semanal às segundas-feiras às 9h (após cobranças)
Schedule::command('transacoes:relatorio', ['--periodo=mes'])
    ->cron('0 9 * * 1') // 9h de segunda-feira (1h após relatório de cobranças)
    ->withoutOverlapping(5)
    ->runInBackground()
    ->description('Relatório semanal de transações')
    ->onSuccess(function () {
        \Log::info('📊 Relatório semanal de transações gerado');
    });

// ===== COMANDOS DE MANUTENÇÃO (EXISTENTES - MANTIDOS) =====

// Limpeza de cache semanalmente aos domingos às 3h
Schedule::command('view:clear')
    ->cron('0 3 * * 0') // 3h de domingo
    ->withoutOverlapping(5)
    ->runInBackground()
    ->description('Limpeza semanal de views');

Schedule::command('config:clear')
    ->cron('5 3 * * 0') // 3h05 de domingo
    ->withoutOverlapping(5)
    ->runInBackground()
    ->description('Limpeza semanal de config');

Schedule::command('route:clear')
    ->cron('10 3 * * 0') // 3h10 de domingo
    ->withoutOverlapping(5)
    ->runInBackground()
    ->description('Limpeza semanal de routes');

// ===== CONFIGURAÇÕES POR AMBIENTE (EXISTENTES + MELHORADOS) =====

if (app()->environment('production')) {
    // Em produção, adicionar verificações extras

    // Comando para verificar saúde das cobranças (existente)
    Schedule::command('cobrancas:processar-automaticas', ['--relatorio'])
        ->hourly()
        ->withoutOverlapping(5)
        ->runInBackground()
        ->description('Verificação hourly de saúde em produção');

    // NOVO: Verificação de transações em produção
    Schedule::command('transacoes:relatorio', ['--periodo=mes'])
        ->cron('0 */6 * * *') // A cada 6 horas
        ->withoutOverlapping(5)
        ->runInBackground()
        ->description('Verificação de transações em produção');

} else {
    // Em desenvolvimento, executar teste diário (existente)
    Schedule::command('cobrancas:processar-automaticas', ['--dry-run'])
        ->dailyAt('12:00')
        ->withoutOverlapping(5)
        ->runInBackground()
        ->description('Teste diário de cobranças em desenvolvimento');

    // NOVO: Teste de transações em desenvolvimento
    Schedule::command('transacoes:processar-pendentes', ['--dry-run', '--limit=10'])
        ->dailyAt('12:30')
        ->withoutOverlapping(5)
        ->runInBackground()
        ->description('Teste diário de transações em desenvolvimento');
}

// ===== COMANDOS MANUAIS DISPONÍVEIS =====
/*
|--------------------------------------------------------------------------
| Comandos para execução manual
|--------------------------------------------------------------------------
|
| COBRANÇAS (existentes):
| sail artisan cobrancas:processar-automaticas
| sail artisan cobrancas:gerar-mensais --dry-run
| sail artisan cobrancas:marcar-vencidas --notificar
|
| TRANSAÇÕES (novos):
| sail artisan transacoes:processar-pendentes --dry-run
| sail artisan transacoes:processar-parcelamentos-vencidos --dry-run
| sail artisan transacoes:expirar-vouchers --dry-run
| sail artisan transacoes:relatorio --periodo=mes
|
| VERIFICAÇÕES:
| sail artisan schedule:list
| sail artisan schedule:run
| sail artisan schedule:work
|
| NOTA: Verificação de saúde foi removida temporariamente para evitar
| problemas com closures. Pode ser implementada como Command depois.
|
*/
