<?php

namespace Tests\Unit\Services;

use App\Models\ComercialReunioes;
use App\Models\User;
use App\Services\ReuniaoAgendadaFormatter;
use Carbon\Carbon;
use Tests\TestCase;

class ReuniaoAgendadaFormatterTest extends TestCase
{
    private ReuniaoAgendadaFormatter $formatter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->formatter = new ReuniaoAgendadaFormatter();
    }

    public function test_location_em_url_marca_como_virtual(): void
    {
        $body = $this->formatter->format($this->reuniao([
            'location' => 'https://meet.google.com/abc-defg-hij',
        ]));

        $this->assertStringContainsString('https://meet.google.com/abc-defg-hij', $body);
        $this->assertStringContainsString('_(Virtual)_', $body);
        $this->assertStringNotContainsString('_(Presencial)_', $body);
    }

    public function test_location_em_texto_simples_marca_como_presencial(): void
    {
        $body = $this->formatter->format($this->reuniao([
            'location' => 'Sala 2 - Sede',
        ]));

        $this->assertStringContainsString('Sala 2 - Sede', $body);
        $this->assertStringContainsString('_(Presencial)_', $body);
    }

    public function test_meet_zoom_e_teams_sao_detectados_como_virtual(): void
    {
        foreach (['meet.google.com/xyz', 'zoom.us/j/123', 'teams.microsoft.com/abc'] as $loc) {
            $body = $this->formatter->format($this->reuniao(['location' => $loc]));
            $this->assertStringContainsString('_(Virtual)_', $body, "Falhou para: {$loc}");
        }
    }

    public function test_location_e_observacao_vazios_nao_quebram(): void
    {
        $body = $this->formatter->format($this->reuniao([
            'location' => null,
            'observacao' => null,
        ]));

        $this->assertStringContainsString('modalidade não informada', $body);
        $this->assertStringContainsString('Observações: —', $body);
    }

    public function test_mensagem_inclui_nome_do_vendedor_gestor_titulo_e_horario(): void
    {
        $body = $this->formatter->format($this->reuniao([
            'titulo' => 'Reunião com lead premium',
            'data_inicio' => Carbon::create(2026, 5, 7, 14, 30, 0, 'America/Sao_Paulo'),
            'data_final' => Carbon::create(2026, 5, 7, 15, 30, 0, 'America/Sao_Paulo'),
        ]));

        $this->assertStringContainsString('*Vendedor Teste*', $body);
        $this->assertStringContainsString('*Gestor Closer*', $body);
        $this->assertStringContainsString('*Reunião com lead premium*', $body);
        $this->assertStringContainsString('07/05/2026', $body);
        $this->assertStringContainsString('14:30 → 15:30', $body);
        $this->assertStringContainsString('_Mensagem automática — SalesControl_', $body);
    }

    private function reuniao(array $overrides = []): ComercialReunioes
    {
        $defaults = [
            'titulo' => 'Reunião de qualificação',
            'data_inicio' => Carbon::create(2026, 5, 7, 10, 0, 0, 'America/Sao_Paulo'),
            'data_final' => Carbon::create(2026, 5, 7, 11, 0, 0, 'America/Sao_Paulo'),
            'location' => 'Sala de Reuniões A',
            'observacao' => 'Cliente interessado em plano empresarial',
        ];

        $reuniao = new ComercialReunioes(array_merge($defaults, $overrides));

        $vendedor = new User(['name' => 'Vendedor Teste']);
        $gestor = new User(['name' => 'Gestor Closer']);

        $reuniao->setRelation('user', $vendedor);
        $reuniao->setRelation('manager', $gestor);

        return $reuniao;
    }
}
