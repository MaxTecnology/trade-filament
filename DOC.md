# 🚀 Guia de Configuração Cron para Produção - Sistema Marketplace

## 📋 **Visão Geral**

Este guia contém todas as instruções para configurar a automação do Sistema Marketplace em ambiente de produção através de **cron jobs**.

---

## ⏰ **Sistema de Agendamento Implementado**

### **📅 Horários Configurados no Laravel:**
- **06:00 Diário** - Processamento automático completo
- **07:00 Dia 1º** - Geração de cobranças mensais
- **08:00, 14:00, 20:00 Diário** - Verificação de vencidas (3x/dia)
- **Segunda 08:00** - Relatório semanal
- **Dia 28 23:00** - Limpeza mensal
- **Domingo 03:00** - Limpeza de cache

### **🔄 Comandos que Executam Automaticamente:**
```bash
# Processamento diário completo
php artisan cobrancas:processar-automaticas

# Geração mensal (dia 1º)
php artisan cobrancas:gerar-mensais

# Marcação de vencidas (3x/dia)
php artisan cobrancas:marcar-vencidas

# Relatório semanal (segundas)
php artisan cobrancas:processar-automaticas --relatorio

# Limpeza mensal (dia 28)
php artisan cobrancas:processar-automaticas --skip-gerar --skip-vencidas
```

---

## 🔧 **Configuração Principal do Cron**

### **📋 Comando Cron Obrigatório:**
```bash
* * * * * cd /caminho/do/seu/projeto && php artisan schedule:run >> /dev/null 2>&1
```

### **🎯 Explicação do Comando:**
- `* * * * *` - Executa a cada minuto
- `cd /caminho/do/seu/projeto` - Navega para o diretório do projeto
- `php artisan schedule:run` - Executa o scheduler do Laravel
- `>> /dev/null 2>&1` - Descarta a saída para não encher o email

---

## 🛠️ **Instruções de Configuração**

### **1. Configuração Básica (Servidor Linux):**

#### **Acessar o Crontab:**
```bash
# Via SSH no servidor
crontab -e
```

#### **Adicionar a Linha:**
```bash
# Laravel Schedule - Sistema Marketplace
* * * * * cd /var/www/html/marketplace && php artisan schedule:run >> /dev/null 2>&1
```

#### **Salvar e Sair:**
```bash
# No editor vim/nano
:wq  # vim
Ctrl+X, Y, Enter  # nano
```

### **2. Configuração para Docker (se usar em produção):**
```bash
# Para containers Docker
* * * * * cd /caminho/do/projeto && docker exec -t nome-do-container php artisan schedule:run >> /dev/null 2>&1

# Para Docker Compose
* * * * * cd /caminho/do/projeto && docker-compose exec app php artisan schedule:run >> /dev/null 2>&1
```

### **3. Configuração com Usuário Específico:**
```bash
# Se usar usuário específico (recomendado)
sudo crontab -u www-data -e

# Adicionar:
* * * * * cd /var/www/html/marketplace && php artisan schedule:run >> /dev/null 2>&1
```

---

## 📜 **Scripts de Deploy Automatizado**

### **🚀 Script de Configuração (`deploy-cron.sh`):**
```bash
#!/bin/bash
# Script para configurar cron em produção automaticamente

# Configurações
PROJECT_PATH="/var/www/html/marketplace"
PHP_PATH="/usr/bin/php"
BACKUP_DIR="/backup/crontab"

echo "🚀 Configurando Cron para Sistema Marketplace..."

# Criar diretório de backup se não existir
mkdir -p $BACKUP_DIR

# Backup do crontab atual
echo "📋 Fazendo backup do crontab atual..."
crontab -l > $BACKUP_DIR/crontab-backup-$(date +%Y%m%d-%H%M%S) 2>/dev/null

# Verificar se o projeto existe
if [ ! -f "$PROJECT_PATH/artisan" ]; then
    echo "❌ Erro: Projeto não encontrado em $PROJECT_PATH"
    exit 1
fi

# Remover linha existente do Laravel (se houver)
crontab -l 2>/dev/null | grep -v "schedule:run" | crontab -

# Adicionar nova linha do Laravel
(crontab -l 2>/dev/null; echo "* * * * * cd $PROJECT_PATH && $PHP_PATH artisan schedule:run >> /dev/null 2>&1") | crontab -

echo "✅ Cron configurado com sucesso!"
echo "📋 Para verificar: crontab -l"
echo "🔍 Para testar: cd $PROJECT_PATH && php artisan schedule:run"
```

### **🔧 Como Usar o Script:**
```bash
# Tornar executável
chmod +x deploy-cron.sh

# Executar
sudo ./deploy-cron.sh

# Verificar se foi configurado
crontab -l
```

---

## 📊 **Configuração com Logs (Recomendada)**

### **📝 Cron com Logs Básicos:**
```bash
# Com logs simples
* * * * * cd /var/www/html/marketplace && php artisan schedule:run >> /var/log/laravel-schedule.log 2>&1
```

### **📝 Cron com Logs Detalhados:**
```bash
# Com logs completos e rotação
* * * * * cd /var/www/html/marketplace && /usr/bin/php artisan schedule:run 1>> /var/log/laravel-cron.log 2>> /var/log/laravel-cron-error.log
```

### **📋 Script Robusto com Logs (`laravel-schedule.sh`):**
```bash
#!/bin/bash
# Script robusto para produção com logs

PROJECT_PATH="/var/www/html/marketplace"
LOG_FILE="/var/log/laravel-schedule.log"
ERROR_LOG="/var/log/laravel-schedule-error.log"
PHP_PATH="/usr/bin/php"

# Função para log com timestamp
log() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') - $1" >> $LOG_FILE
}

error_log() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') - ERROR: $1" >> $ERROR_LOG
}

# Verificar se o projeto existe
if [ ! -d "$PROJECT_PATH" ]; then
    error_log "Diretório do projeto não encontrado: $PROJECT_PATH"
    exit 1
fi

cd $PROJECT_PATH

# Verificar se o arquivo artisan existe
if [ ! -f "artisan" ]; then
    error_log "Arquivo artisan não encontrado em $PROJECT_PATH"
    exit 1
fi

# Executar schedule
log "Iniciando execução do schedule..."
$PHP_PATH artisan schedule:run >> $LOG_FILE 2>> $ERROR_LOG

# Verificar resultado
if [ $? -eq 0 ]; then
    log "Schedule executado com sucesso"
else
    error_log "Falha na execução do schedule (exit code: $?)"
fi

# Limpeza de logs antigos (manter apenas últimos 30 dias)
find /var/log -name "laravel-schedule*.log" -mtime +30 -delete 2>/dev/null
```

### **🚀 Usar o Script Robusto:**
```bash
# Salvar como /usr/local/bin/laravel-schedule.sh
sudo cp laravel-schedule.sh /usr/local/bin/
sudo chmod +x /usr/local/bin/laravel-schedule.sh

# Configurar cron para usar o script
* * * * * /usr/local/bin/laravel-schedule.sh
```

---

## 🔍 **Comandos de Verificação e Teste**

### **✅ Verificar Configuração:**
```bash
# Ver crontab atual
crontab -l

# Ver próximas execuções programadas
cd /var/www/html/marketplace && php artisan schedule:list

# Testar execução manual
cd /var/www/html/marketplace && php artisan schedule:run
```

### **📊 Monitorar Execução:**
```bash
# Logs do sistema
tail -f /var/log/laravel-schedule.log

# Logs do Laravel
tail -f /var/www/html/marketplace/storage/logs/laravel.log | grep -i schedule

# Ver processos do cron
ps aux | grep cron

# Status do serviço cron
sudo systemctl status cron
```

### **🧪 Testar Commands Individuais:**
```bash
# Testar processamento completo
php artisan cobrancas:processar-automaticas --dry-run

# Testar geração mensal
php artisan cobrancas:gerar-mensais --dry-run

# Testar marcação de vencidas
php artisan cobrancas:marcar-vencidas --dry-run

# Ver apenas relatório
php artisan cobrancas:processar-automaticas --relatorio
```

---

## ⚠️ **Configurações Importantes**

### **🔒 1. Permissões do Sistema:**
```bash
# Proprietário correto dos arquivos
sudo chown -R www-data:www-data /var/www/html/marketplace

# Permissões corretas
sudo chmod -R 755 /var/www/html/marketplace
sudo chmod -R 775 /var/www/html/marketplace/storage
sudo chmod -R 775 /var/www/html/marketplace/bootstrap/cache

# Logs do cron
sudo mkdir -p /var/log/laravel
sudo chown www-data:www-data /var/log/laravel
sudo chmod 755 /var/log/laravel
```

### **🌍 2. Variáveis de Ambiente:**
```bash
# Se usar .env específico para produção
* * * * * cd /var/www/html/marketplace && /usr/bin/php artisan schedule:run --env=production >> /dev/null 2>&1

# Verificar variáveis de ambiente no .env
APP_ENV=production
APP_DEBUG=false
APP_TIMEZONE=America/Sao_Paulo
```

### **🕐 3. Timezone do Servidor:**
```bash
# Verificar timezone atual
timedatectl

# Listar timezones disponíveis
timedatectl list-timezones | grep Sao_Paulo

# Alterar timezone se necessário
sudo timedatectl set-timezone America/Sao_Paulo

# Verificar timezone do PHP
php -r "echo date_default_timezone_get();"
```

### **📧 4. Configuração de Email:**
```bash
# Verificar configuração de email no .env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=seu-email@gmail.com
MAIL_PASSWORD=sua-senha-app
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@seudominio.com
MAIL_FROM_NAME="Sistema Marketplace"

# Testar envio de email
php artisan tinker
Mail::raw('Teste', function($m) { $m->to('admin@seudominio.com')->subject('Teste Cron'); });
```

---

## 🚨 **Troubleshooting**

### **❌ Problemas Comuns:**

#### **1. Cron Não Executa:**
```bash
# Verificar se o serviço cron está rodando
sudo systemctl status cron
sudo systemctl start cron
sudo systemctl enable cron

# Verificar logs do sistema
sudo tail -f /var/log/syslog | grep CRON
```

#### **2. Permissões Negadas:**
```bash
# Corrigir permissões
sudo chown -R www-data:www-data /var/www/html/marketplace
sudo chmod -R 755 /var/www/html/marketplace

# Verificar se www-data pode executar PHP
sudo -u www-data php -v
```

#### **3. Caminho do PHP Incorreto:**
```bash
# Encontrar caminho correto do PHP
which php
whereis php

# Usar caminho absoluto no cron
* * * * * cd /var/www/html/marketplace && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

#### **4. Variáveis de Ambiente não Carregadas:**
```bash
# Adicionar source do bashrc se necessário
* * * * * cd /var/www/html/marketplace && /bin/bash -l -c 'php artisan schedule:run' >> /dev/null 2>&1

# Ou definir PATH no crontab
PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin
* * * * * cd /var/www/html/marketplace && php artisan schedule:run >> /dev/null 2>&1
```

---

## 📋 **Checklist de Deploy para Produção**

### **🔧 Antes de Ativar o Cron:**
- [ ] ✅ Projeto deployado e funcionando
- [ ] ✅ Banco de dados migrado e seedado
- [ ] ✅ Arquivo .env configurado para produção
- [ ] ✅ Permissões corretas nos diretórios
- [ ] ✅ Email configurado e testado
- [ ] ✅ Timezone correto configurado
- [ ] ✅ Commands testados manualmente
- [ ] ✅ Logs de diretório criados

### **⚡ Configuração do Cron:**
- [ ] ✅ Crontab configurado
- [ ] ✅ Caminho do projeto correto
- [ ] ✅ Caminho do PHP correto
- [ ] ✅ Permissões do usuário corretas
- [ ] ✅ Logs configurados

### **📊 Após Ativar:**
- [ ] ✅ Monitorar logs por 24h
- [ ] ✅ Verificar execução nos horários corretos
- [ ] ✅ Confirmar emails sendo enviados
- [ ] ✅ Testar notificações de erro
- [ ] ✅ Validar geração de cobranças (dia 1º)
- [ ] ✅ Acompanhar performance do sistema

---

## 📈 **Monitoramento Contínuo**

### **📊 Comandos de Monitoramento Diário:**
```bash
# Ver últimas execuções
tail -n 50 /var/log/laravel-schedule.log

# Verificar erros
tail -n 20 /var/log/laravel-schedule-error.log

# Status geral do sistema
php artisan cobrancas:processar-automaticas --relatorio

# Verificar saúde do banco
php artisan tinker
Cobranca::count()
Cobranca::resumoFinanceiro()
```

### **📧 Script de Relatório Diário (`daily-report.sh`):**
```bash
#!/bin/bash
# Relatório diário automático por email

PROJECT_PATH="/var/www/html/marketplace"
ADMIN_EMAIL="admin@seudominio.com"

cd $PROJECT_PATH

# Gerar relatório
php artisan cobrancas:processar-automaticas --relatorio > /tmp/daily-report.txt

# Verificar erros nos logs
ERROR_COUNT=$(grep -c "ERROR" /var/log/laravel-schedule.log || echo "0")

# Enviar por email
{
    echo "📊 Relatório Diário - Sistema Marketplace"
    echo "Data: $(date)"
    echo "Servidor: $(hostname)"
    echo ""
    echo "🔍 Erros encontrados: $ERROR_COUNT"
    echo ""
    echo "📋 Relatório Completo:"
    cat /tmp/daily-report.txt
} | mail -s "Relatório Diário - Marketplace $(date +%d/%m/%Y)" $ADMIN_EMAIL

# Limpeza
rm -f /tmp/daily-report.txt
```

---

## 🎯 **Configuração Final Recomendada**

### **📋 Cron de Produção (Versão Final):**
```bash
# Adicionar ao crontab
SHELL=/bin/bash
PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin
MAILTO=admin@seudominio.com

# Laravel Schedule - Sistema Marketplace
* * * * * cd /var/www/html/marketplace && /usr/bin/php artisan schedule:run >> /var/log/laravel-schedule.log 2>&1

# Relatório diário (opcional)
0 7 * * * /usr/local/bin/daily-report.sh

# Limpeza de logs (semanal)
0 2 * * 0 find /var/log -name "laravel-*.log" -mtime +30 -delete
```

---

## 🎉 **Conclusão**

Com essa configuração, seu **Sistema Marketplace** estará **100% automatizado** em produção:

✅ **Execução automática** a cada minuto via cron  
✅ **Logs detalhados** para monitoramento  
✅ **Scripts robustos** com tratamento de erro  
✅ **Backup automático** de configurações  
✅ **Monitoramento contínuo** da saúde do sistema  
✅ **Relatórios automáticos** por email  
✅ **Limpeza automática** de logs antigos

**🚀 Seu sistema agora opera 24/7 sem intervenção manual!**

---

## 📞 **Suporte**

### **🔧 Para Problemas:**
1. **Verificar logs**: `/var/log/laravel-schedule.log`
2. **Testar manualmente**: `php artisan schedule:run`
3. **Verificar permissões**: `ls -la storage/`
4. **Status do cron**: `sudo systemctl status cron`

### **📧 Comandos de Debug:**
```bash
# Debug completo
cd /var/www/html/marketplace
php artisan schedule:list
php artisan config:cache
php artisan route:cache
php artisan view:cache
tail -f storage/logs/laravel.log
```

**💪 Sistema totalmente automatizado e pronto para escala empresarial!**


# 🔄 COMPLEMENTO - Sistema de Transações Automatizado

## 📋 **ADIÇÃO AO SISTEMA EXISTENTE**

Este complemento deve ser **ADICIONADO** à sua documentação existente de configuração cron. O sistema de transações foi integrado com o sistema de cobranças já existente.

---

## ⏰ **NOVOS HORÁRIOS ADICIONADOS AO AGENDAMENTO**

### **🔄 Comandos de Transações Integrados:**
```bash
# 06:30 Diário - Processamento de transações pendentes (30min após cobranças)
php artisan transacoes:processar-pendentes --auto-approve --limit=100

# 08:15, 14:15, 20:15 Diário - Parcelamentos vencidos (15min após cobranças)
php artisan transacoes:processar-parcelamentos-vencidos --calcular-encargos

# 09:00 Diário - Expirar vouchers e alertas
php artisan transacoes:expirar-vouchers --dias-alerta=7

# Segunda 09:00 - Relatório semanal de transações (1h após cobranças)
php artisan transacoes:relatorio --periodo=mes

# Desenvolvimento 12:30 - Teste de transações (30min após teste de cobranças)
php artisan transacoes:processar-pendentes --dry-run --limit=10
```

---

## 🎯 **CRONOGRAMA COMPLETO INTEGRADO**

### **📅 Sequência Coordenada Diária:**
```
06:00 ➜ Cobranças automáticas (existente)
06:30 ➜ 🆕 Transações pendentes (novo)
08:00 ➜ Cobranças vencidas (existente)
08:15 ➜ 🆕 Parcelamentos vencidos (novo)
09:00 ➜ 🆕 Vouchers expirados (novo)
14:00 ➜ Cobranças vencidas (existente)
14:15 ➜ 🆕 Parcelamentos vencidos (novo)
20:00 ➜ Cobranças vencidas (existente)
20:15 ➜ 🆕 Parcelamentos vencidos (novo)
```

### **📅 Sequência Semanal/Mensal:**
```
Segunda 08:00 ➜ Relatório cobranças (existente)
Segunda 09:00 ➜ 🆕 Relatório transações (novo)
Dia 1º 07:00 ➜ Geração cobranças mensais (existente)
Dia 28 23:00 ➜ Limpeza geral (existente)
```

---

## 🔧 **CONFIGURAÇÃO DE PRODUÇÃO ATUALIZADA**

### **📋 O Mesmo Cron Principal Funciona:**
```bash
# NENHUMA MUDANÇA NECESSÁRIA - O cron existente já funciona
* * * * * cd /var/www/html/marketplace && php artisan schedule:run >> /dev/null 2>&1
```

**💡 IMPORTANTE:** Não precisa alterar o cron! O Laravel Schedule gerencia tudo automaticamente.

---

## 🧪 **NOVOS COMANDOS DE TESTE**

### **✅ Verificar Comandos de Transações:**
```bash
# Verificar se os novos comandos existem
cd /var/www/html/marketplace

# Testar processamento de pendentes
php artisan transacoes:processar-pendentes --dry-run --limit=5

# Testar parcelamentos vencidos
php artisan transacoes:processar-parcelamentos-vencidos --dry-run

# Testar vouchers
php artisan transacoes:expirar-vouchers --dry-run

# Gerar relatório completo
php artisan transacoes:relatorio
```

### **📊 Verificar Integração Completa:**
```bash
# Ver TODOS os agendamentos (cobranças + transações)
php artisan schedule:list

# Executar schedule completo manualmente
php artisan schedule:run

# Ver próximas execuções
php artisan schedule:list --next
```

---

## 📊 **LOGS ATUALIZADOS**

### **📝 Novos Logs a Monitorar:**
```bash
# Logs específicos de transações
tail -f /var/log/laravel-schedule.log | grep -i "transacao\|parcelamento\|voucher"

# Logs do Laravel com filtro
tail -f /var/www/html/marketplace/storage/logs/laravel.log | grep -E "(transacao|parcelamento|voucher)"

# Verificar execução de comandos específicos
grep "transacoes:" /var/log/laravel-schedule.log
```

### **🔍 Monitoramento de Saúde Expandido:**
```bash
# Script de monitoramento atualizado
#!/bin/bash
cd /var/www/html/marketplace

echo "📊 RELATÓRIO DE SAÚDE - SISTEMA COMPLETO"
echo "Data: $(date)"
echo ""

echo "🏦 COBRANÇAS:"
php artisan cobrancas:processar-automaticas --relatorio

echo ""
echo "💳 TRANSAÇÕES:"
php artisan transacoes:relatorio --periodo=mes

echo ""
echo "📈 ÚLTIMAS EXECUÇÕES:"
tail -n 10 /var/log/laravel-schedule.log | grep -E "(SUCCESS|ERROR|transacao|cobranca)"
```

---

## ⚠️ **VALIDAÇÕES ESPECÍFICAS PARA TRANSAÇÕES**

### **🔒 Permissões Adicionais:**
```bash
# Verificar se comandos de transação funcionam
sudo -u www-data php artisan transacoes:relatorio

# Verificar tabelas necessárias
php artisan tinker
Schema::hasTable('transacoes')
Schema::hasTable('parcelamentos')  
Schema::hasTable('vouchers')
```

### **📊 Dados de Teste:**
```bash
# Verificar se tem dados para processar
php artisan tinker
\App\Models\Transacao::count()
\App\Models\Transacao::pendentes()->count()
\App\Models\Parcelamento::where('status', 'pendente')->count()
\App\Models\Voucher::where('status', 'Ativo')->count()
```

---

## 🚀 **DEPLOY ATUALIZADO**

### **📋 Checklist Expandido:**

#### **Antes de Ativar (Adicionar aos existentes):**
- [ ] ✅ Commands de transação testados manualmente
- [ ] ✅ Models de Transacao, Parcelamento, Voucher funcionando
- [ ] ✅ Tabelas criadas (transacoes, parcelamentos, vouchers)
- [ ] ✅ Relacionamentos entre tabelas verificados
- [ ] ✅ Dados de teste criados via seeder

#### **Após Ativar (Adicionar ao monitoramento):**
- [ ] ✅ Verificar execução de transações às 06:30
- [ ] ✅ Confirmar parcelamentos às 08:15, 14:15, 20:15
- [ ] ✅ Validar vouchers às 09:00
- [ ] ✅ Acompanhar relatórios de segunda às 09:00
- [ ] ✅ Monitorar aprovações automáticas

---

## 🔧 **SCRIPT DE DEPLOY COMPLEMENTAR**

### **🚀 Script Adicional (`deploy-transacoes.sh`):**
```bash
#!/bin/bash
# Complemento para verificar sistema de transações

PROJECT_PATH="/var/www/html/marketplace"
cd $PROJECT_PATH

echo "🔄 Verificando Sistema de Transações..."

# Verificar se comandos existem
echo "📋 Verificando comandos..."
php artisan list | grep "transacoes:" || echo "❌ Comandos de transação não encontrados"

# Verificar tabelas
echo "📊 Verificando tabelas..."
php artisan tinker --execute="
echo 'Transacoes: ' . \App\Models\Transacao::count();
echo 'Parcelamentos: ' . \App\Models\Parcelamento::count();
echo 'Vouchers: ' . \App\Models\Voucher::count();
"

# Testar commands em dry-run
echo "🧪 Testando comandos..."
php artisan transacoes:processar-pendentes --dry-run --limit=1
php artisan transacoes:processar-parcelamentos-vencidos --dry-run
php artisan transacoes:expirar-vouchers --dry-run

echo "✅ Verificação de transações concluída!"
```

---

## 📈 **RELATÓRIOS EXPANDIDOS**

### **📊 Relatório Diário Atualizado:**
```bash
#!/bin/bash
# Relatório diário expandido (adicionar ao existente)

PROJECT_PATH="/var/www/html/marketplace"
cd $PROJECT_PATH

# Gerar relatório completo
{
    echo "📊 RELATÓRIO COMPLETO - Sistema Marketplace"
    echo "Data: $(date)"
    echo ""
    
    echo "🏦 COBRANÇAS:"
    php artisan cobrancas:processar-automaticas --relatorio
    
    echo ""
    echo "💳 TRANSAÇÕES:"
    php artisan transacoes:relatorio --periodo=mes
    
    echo ""
    echo "📈 ESTATÍSTICAS RÁPIDAS:"
    php artisan tinker --execute="
    echo 'Transações Pendentes: ' . \App\Models\Transacao::pendentes()->count();
    echo 'Parcelamentos Vencidos: ' . \App\Models\Parcelamento::where('status', 'vencida')->count();
    echo 'Vouchers Ativos: ' . \App\Models\Voucher::where('status', 'Ativo')->count();
    "
    
} > /tmp/relatorio-completo.txt

# Enviar por email (usar o mesmo email do sistema de cobranças)
mail -s "Relatório Completo - Marketplace $(date +%d/%m/%Y)" $ADMIN_EMAIL < /tmp/relatorio-completo.txt
```

---

## 🎯 **COMANDOS DE DEBUG ESPECÍFICOS**

### **🔍 Para Problemas com Transações:**
```bash
# Debug específico de transações
cd /var/www/html/marketplace

# Verificar últimas execuções
grep "transacoes:" storage/logs/laravel.log | tail -20

# Verificar dados inconsistentes
php artisan tinker
// Transações sem comprador ou vendedor
\App\Models\Transacao::whereNull('comprador_id')->count()
\App\Models\Transacao::whereNull('vendedor_id')->count()

// Parcelamentos órfãos
\App\Models\Parcelamento::whereDoesntHave('transacao')->count()

// Vouchers órfãos  
\App\Models\Voucher::whereDoesntHave('transacao')->count()
```

### **🛠️ Correções Rápidas:**
```bash
# Reprocessar transações pendentes manualmente
php artisan transacoes:processar-pendentes --limit=50

# Forçar marcação de parcelamentos vencidos
php artisan transacoes:processar-parcelamentos-vencidos --calcular-encargos

# Limpar vouchers expirados
php artisan transacoes:expirar-vouchers
```

---

## 🎉 **INTEGRAÇÃO FINAL**

### **✅ O que Você Tem Agora:**

1. **Sistema de Cobranças** (existente) + **Sistema de Transações** (novo)
2. **Horários coordenados** - transações executam APÓS cobranças
3. **Logs integrados** - tudo no mesmo sistema de monitoramento
4. **Relatórios unificados** - visão completa do negócio
5. **Automação 24/7** - opera sem intervenção manual

### **📋 Para Adicionar à Sua Documentação:**

1. **Copie esta seção** e adicione após a seção de configuração de cobranças
2. **Atualize o checklist** de deploy com os novos itens
3. **Modifique o script** de relatório diário para incluir transações
4. **Adicione os novos comandos** de debug à seção de troubleshooting

### **🚀 Resultado Final:**

**Sistema Marketplace Completamente Automatizado:**
- ✅ Cobranças automáticas
- ✅ Transações automáticas
- ✅ Parcelamentos controlados
- ✅ Vouchers gerenciados
- ✅ Relatórios integrados
- ✅ Monitoramento unificado

**💪 Seu sistema agora é uma máquina completa que opera 24/7 sem intervenção manual!**
