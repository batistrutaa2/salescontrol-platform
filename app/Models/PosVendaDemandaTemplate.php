<?php

namespace App\Models;

use App\Enums\TipoDemandaContrato;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PosVendaDemandaTemplate extends Model
{
    use \App\Models\Concerns\BelongsToTenant;
    use HasFactory;

    protected $table = 'pos_venda_demanda_templates';

    protected $fillable = [
        'empresa_id',
        'tipo',
        'titulo',
        'descricao',
        'gerar_automatico',
        'ativo',
        'ordem',
    ];

    protected $casts = [
        'gerar_automatico' => 'boolean',
        'ativo' => 'boolean',
        'ordem' => 'integer',
    ];

    /**
     * Check-list padrão de pós-venda. Cada item: [tipo, titulo, gerar_automatico].
     * Os 6 primeiros são o núcleo gerado automaticamente na implantação; os demais
     * ficam disponíveis para criação avulsa (gerar_automatico = false).
     *
     * @return array<int, array{tipo: string, titulo: string, gerar_automatico: bool}>
     */
    public static function defaults(): array
    {
        return [
            ['tipo' => TipoDemandaContrato::ACESSO_EMPRESA->value, 'titulo' => 'Criar acesso da empresa', 'gerar_automatico' => true],
            ['tipo' => TipoDemandaContrato::LOGIN_APPS->value, 'titulo' => 'Criar login dos aplicativos', 'gerar_automatico' => true],
            ['tipo' => TipoDemandaContrato::TROCA_EMAIL->value, 'titulo' => 'Alterar e-mail pós-implantação', 'gerar_automatico' => true],
            ['tipo' => TipoDemandaContrato::CANCELAMENTO_INTERMEDIADORA->value, 'titulo' => 'Cancelar vínculo com intermediadora', 'gerar_automatico' => true],
            ['tipo' => TipoDemandaContrato::CANCELAMENTO_LIMITAR->value, 'titulo' => 'Cancelar via Limitar', 'gerar_automatico' => true],
            ['tipo' => TipoDemandaContrato::BOAS_VINDAS->value, 'titulo' => 'Enviar boas-vindas', 'gerar_automatico' => true],
            ['tipo' => TipoDemandaContrato::ENVIO_BOLETO->value, 'titulo' => 'Enviar boleto', 'gerar_automatico' => false],
            ['tipo' => TipoDemandaContrato::REEMBOLSO->value, 'titulo' => 'Solicitar reembolso', 'gerar_automatico' => false],
            ['tipo' => TipoDemandaContrato::INCLUSAO_BENEFICIARIO->value, 'titulo' => 'Incluir beneficiário', 'gerar_automatico' => false],
            ['tipo' => TipoDemandaContrato::EXCLUSAO_BENEFICIARIO->value, 'titulo' => 'Excluir beneficiário', 'gerar_automatico' => false],
            ['tipo' => TipoDemandaContrato::PORTABILIDADE->value, 'titulo' => 'Tratar portabilidade', 'gerar_automatico' => false],
        ];
    }

    /**
     * Cria os templates padrão para a empresa caso ela ainda não tenha nenhum.
     * Idempotente: não duplica se já houver templates da empresa.
     */
    public static function seedDefaults(int $empresaId): void
    {
        if (self::where('empresa_id', $empresaId)->exists()) {
            return;
        }

        $ordem = 0;
        foreach (self::defaults() as $item) {
            self::create([
                'empresa_id' => $empresaId,
                'tipo' => $item['tipo'],
                'titulo' => $item['titulo'],
                'descricao' => null,
                'gerar_automatico' => $item['gerar_automatico'],
                'ativo' => true,
                'ordem' => $ordem++,
            ]);
        }
    }
}
