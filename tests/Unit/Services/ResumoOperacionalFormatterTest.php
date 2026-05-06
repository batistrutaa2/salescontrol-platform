<?php

namespace Tests\Unit\Services;

use App\Services\ResumoOperacionalFormatter;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class ResumoOperacionalFormatterTest extends TestCase
{
    private ResumoOperacionalFormatter $formatter;
    private Carbon $data;

    protected function setUp(): void
    {
        parent::setUp();
        $this->formatter = new ResumoOperacionalFormatter();
        $this->data      = Carbon::create(2026, 5, 5, 18, 0, 0, 'America/Sao_Paulo'); // terça
    }

    public function test_dia_sem_movimento_mostra_placeholder(): void
    {
        $body = $this->formatter->format(
            $this->snapshotVazio(),
            'João Admin',
            'Corretora Teste',
            $this->data,
        );

        $this->assertStringContainsString('Sem movimento registrado hoje', $body);
        $this->assertStringContainsString('Sem ranking de vendedores hoje', $body);
        $this->assertStringContainsString('Reuniões marcadas: _nenhuma_', $body);
        $this->assertStringContainsString('Agendamentos de retorno: *0*', $body);
    }

    public function test_dia_com_vendas_e_top_vendedores(): void
    {
        $snapshot = $this->snapshotVazio();
        $snapshot['vendas_cadastradas'] = ['count' => 8, 'valor_total' => 12430.00, 'ticket_medio' => 1553.75];
        $snapshot['vendas_implantadas'] = ['count' => 5, 'valor_total' => 9800.00, 'vidas' => 17];
        $snapshot['pipeline']           = ['boleto_disponivel' => 6, 'implantadas' => 5];
        $snapshot['equipe']             = ['leads' => 41];
        $snapshot['top_vendedores']     = [
            ['user_id' => 1, 'nome' => 'João Silva',  'total' => 5200.00],
            ['user_id' => 2, 'nome' => 'Maria Souza', 'total' => 3800.00],
            ['user_id' => 3, 'nome' => 'Pedro Lima',  'total' => 1430.00],
        ];
        $snapshot['amanha'] = [
            'agendamentos'        => 4,
            'reunioes_por_gestor' => [
                ['manager_id' => 7, 'nome' => 'Carlos Gestor', 'total' => 3],
                ['manager_id' => 9, 'nome' => 'Ana Gestora',   'total' => 2],
            ],
        ];

        $body = $this->formatter->format($snapshot, 'João Admin', 'Corretora Teste', $this->data);

        $this->assertStringContainsString('Cadastradas: *8* (R$ 12.430,00)', $body);
        $this->assertStringContainsString('Ticket médio: R$ 1.553,75', $body);
        $this->assertStringContainsString('Implantadas: *5* (R$ 9.800,00) — 17 vidas', $body);
        $this->assertStringContainsString('Boleto disponível: *6*', $body);
        $this->assertStringContainsString('📈 *Pipeline*', $body);
        $this->assertStringContainsString('Leads trabalhados: *41*', $body);
        $this->assertStringContainsString('🥇 João Silva — R$ 5.200,00', $body);
        $this->assertStringContainsString('🥈 Maria Souza — R$ 3.800,00', $body);
        $this->assertStringContainsString('🥉 Pedro Lima — R$ 1.430,00', $body);
        $this->assertStringContainsString('Agendamentos de retorno: *4*', $body);
        $this->assertStringContainsString('Reuniões marcadas: *5*', $body);
        $this->assertStringContainsString('Carlos Gestor: 3', $body);
        $this->assertStringContainsString('Ana Gestora: 2', $body);
    }

    public function test_top_vendedor_sem_nome_usa_fallback(): void
    {
        $snapshot = $this->snapshotVazio();
        $snapshot['top_vendedores'] = [
            ['user_id' => 99, 'nome' => null, 'total' => 100.00],
        ];

        $body = $this->formatter->format($snapshot, 'Admin', 'Empresa X', $this->data);

        $this->assertStringContainsString('Vendedor sem nome', $body);
    }

    public function test_inclui_nome_empresa_e_data_brasileira(): void
    {
        $body = $this->formatter->format($this->snapshotVazio(), 'Admin', 'Corretora ACME', $this->data);

        $this->assertStringContainsString('📊 *Resumo Operacional — Corretora ACME*', $body);
        $this->assertStringContainsString('📅 05/05/2026 (terça) — 18:00', $body);
        $this->assertStringContainsString('_Mensagem automática — SalesControl_', $body);
    }

    public function test_nao_menciona_ligacoes_nem_interacoes(): void
    {
        $body = $this->formatter->format($this->snapshotVazio(), 'Admin', 'X', $this->data);

        $this->assertStringNotContainsString('Ligações', $body);
        $this->assertStringNotContainsString('Ligacoes', $body);
        $this->assertStringNotContainsString('Interações', $body);
        $this->assertStringNotContainsString('Interacoes', $body);
    }

    public function test_reunioes_por_gestor_mostra_total_e_lista(): void
    {
        $snapshot = $this->snapshotVazio();
        $snapshot['amanha'] = [
            'agendamentos'        => 0,
            'reunioes_por_gestor' => [
                ['manager_id' => 7, 'nome' => 'Carlos', 'total' => 5],
                ['manager_id' => 9, 'nome' => null,     'total' => 1],
            ],
        ];

        $body = $this->formatter->format($snapshot, 'Admin', 'X', $this->data);

        $this->assertStringContainsString('Reuniões marcadas: *6*', $body);
        $this->assertStringContainsString('Carlos: 5', $body);
        $this->assertStringContainsString('Gestor sem nome: 1', $body);
    }

    private function snapshotVazio(): array
    {
        return [
            'vendas_cadastradas' => ['count' => 0, 'valor_total' => 0.0, 'ticket_medio' => 0.0],
            'vendas_implantadas' => ['count' => 0, 'valor_total' => 0.0, 'vidas' => 0],
            'pipeline'           => ['boleto_disponivel' => 0, 'implantadas' => 0],
            'equipe'             => ['leads' => 0],
            'top_vendedores'     => [],
            'amanha'             => ['agendamentos' => 0, 'reunioes_por_gestor' => []],
        ];
    }
}
