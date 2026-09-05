<?php

namespace App\Models;

use App\Enums\NaturezaEtapaSolicitacao;
use App\Enums\TipoSolicitacaoPosVenda;
use Illuminate\Database\Eloquent\Model;

class PosVendaFluxoEtapa extends Model
{
    use \App\Models\Concerns\BelongsToTenant;

    protected $table = 'pos_venda_fluxo_etapas';

    protected $fillable = [
        'empresa_id',
        'tipo',
        'nome',
        'cor',
        'ordem',
        'natureza',
    ];

    protected $casts = [
        'ordem' => 'integer',
    ];

    public function solicitacoes()
    {
        return $this->hasMany(PosVendaSolicitacao::class, 'etapa_id');
    }

    /**
     * Fluxos padrão por tipo: [nome, cor, natureza]. Cancelamento e
     * portabilidade nascem com fluxos maiores de exemplo; os demais tipos com o
     * trio básico. A pós-venda edita tudo depois pela UI.
     *
     * @return array<string, array<int, array{nome: string, cor: string, natureza: string}>>
     */
    public static function defaults(): array
    {
        $basico = [
            ['nome' => 'Aberta', 'cor' => '#7C3AED', 'natureza' => NaturezaEtapaSolicitacao::EM_ANDAMENTO->value],
            ['nome' => 'Em andamento', 'cor' => '#06B6D4', 'natureza' => NaturezaEtapaSolicitacao::EM_ANDAMENTO->value],
            ['nome' => 'Concluída', 'cor' => '#10B981', 'natureza' => NaturezaEtapaSolicitacao::CONCLUIDA->value],
            ['nome' => 'Cancelada', 'cor' => '#EF4444', 'natureza' => NaturezaEtapaSolicitacao::CANCELADA->value],
        ];

        $map = [];
        foreach (TipoSolicitacaoPosVenda::cases() as $tipo) {
            $map[$tipo->value] = $basico;
        }

        $map[TipoSolicitacaoPosVenda::CANCELAMENTO->value] = [
            ['nome' => 'Aberta', 'cor' => '#7C3AED', 'natureza' => NaturezaEtapaSolicitacao::EM_ANDAMENTO->value],
            ['nome' => 'Documentação', 'cor' => '#06B6D4', 'natureza' => NaturezaEtapaSolicitacao::EM_ANDAMENTO->value],
            ['nome' => 'Solicitado na operadora', 'cor' => '#F59E0B', 'natureza' => NaturezaEtapaSolicitacao::EM_ANDAMENTO->value],
            ['nome' => 'Aguardando confirmação', 'cor' => '#8B5CF6', 'natureza' => NaturezaEtapaSolicitacao::EM_ANDAMENTO->value],
            ['nome' => 'Concluída', 'cor' => '#10B981', 'natureza' => NaturezaEtapaSolicitacao::CONCLUIDA->value],
            ['nome' => 'Desistida', 'cor' => '#EF4444', 'natureza' => NaturezaEtapaSolicitacao::CANCELADA->value],
        ];

        $map[TipoSolicitacaoPosVenda::PORTABILIDADE->value] = [
            ['nome' => 'Aberta', 'cor' => '#7C3AED', 'natureza' => NaturezaEtapaSolicitacao::EM_ANDAMENTO->value],
            ['nome' => 'Análise de elegibilidade', 'cor' => '#06B6D4', 'natureza' => NaturezaEtapaSolicitacao::EM_ANDAMENTO->value],
            ['nome' => 'Protocolada', 'cor' => '#F59E0B', 'natureza' => NaturezaEtapaSolicitacao::EM_ANDAMENTO->value],
            ['nome' => 'Concluída', 'cor' => '#10B981', 'natureza' => NaturezaEtapaSolicitacao::CONCLUIDA->value],
            ['nome' => 'Cancelada', 'cor' => '#EF4444', 'natureza' => NaturezaEtapaSolicitacao::CANCELADA->value],
        ];

        return $map;
    }

    /**
     * Semeia as etapas padrão da empresa. Idempotente por (empresa, tipo):
     * tipos que já têm etapas são pulados, então tipos novos adicionados ao
     * enum ganham defaults sem duplicar os existentes.
     */
    public static function seedDefaults(int $empresaId): void
    {
        $tiposExistentes = self::where('empresa_id', $empresaId)
            ->distinct()
            ->pluck('tipo')
            ->all();

        foreach (self::defaults() as $tipo => $etapas) {
            if (in_array($tipo, $tiposExistentes, true)) {
                continue;
            }

            foreach ($etapas as $ordem => $etapa) {
                self::create([
                    'empresa_id' => $empresaId,
                    'tipo' => $tipo,
                    'nome' => $etapa['nome'],
                    'cor' => $etapa['cor'],
                    'ordem' => $ordem,
                    'natureza' => $etapa['natureza'],
                ]);
            }
        }
    }
}
