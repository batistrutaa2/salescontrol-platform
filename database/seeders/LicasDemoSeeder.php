<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Dados fictícios da LICAS CONSULTORIA para desenhar e validar o tema Licas
 * no ambiente local. Só roda fora de produção.
 *
 * Tudo que o seeder cria leva um marcador (e-mail @demo.licas.local,
 * contatos.nome_base = DEMO_LICAS, vendas.numero_proposta DEMO-*), e cada
 * execução apaga o que ele mesmo criou antes de recriar.
 *
 *   ./dev artisan db:seed --class=LicasDemoSeeder
 */
class LicasDemoSeeder extends Seeder
{
    private const EMPRESA_ID = 2;

    private const MARCA_CONTATO = 'DEMO_LICAS';

    private const DOMINIO_EMAIL = '@demo.licas.local';

    private const SENHA = 'licas2026';

    private const VENDEDORES = [
        'RAFAELA MONTEIRO', 'BRUNO TAVARES', 'CAMILA REZENDE',
        'DIEGO FONSECA', 'LARISSA QUEIROZ', 'THIAGO MOURA',
    ];

    private const OPERADORAS = [
        'AMIL' => ['AMIL S380', 'AMIL S450', 'AMIL S750'],
        'BRADESCO SAÚDE' => ['EFETIVO III', 'NACIONAL FLEX'],
        'SULAMÉRICA' => ['EXATO', 'CLÁSSICO'],
        'PORTO SAÚDE' => ['PRATA', 'OURO'],
        'HAPVIDA NOTREDAME' => ['SMART 200', 'ADVANCE 600'],
    ];

    private const NOMES = [
        'ANA', 'BEATRIZ', 'CARLOS', 'DANIELA', 'EDUARDO', 'FERNANDA', 'GABRIEL', 'HELENA',
        'IGOR', 'JULIANA', 'KLEBER', 'LUCIANA', 'MARCELO', 'NATÁLIA', 'OTÁVIO', 'PATRÍCIA',
        'RICARDO', 'SIMONE', 'TATIANE', 'VINÍCIUS', 'WAGNER', 'ADRIANA', 'ROBERTO', 'SÔNIA',
    ];

    private const SOBRENOMES = [
        'ALMEIDA', 'BARBOSA', 'CARDOSO', 'DIAS', 'FERREIRA', 'GOMES', 'LIMA', 'MARTINS',
        'NASCIMENTO', 'OLIVEIRA', 'PEREIRA', 'RIBEIRO', 'SANTOS', 'SOUZA', 'TEIXEIRA', 'VIEIRA',
    ];

    private const EMPRESAS_PJ = [
        'PADARIA', 'CLÍNICA', 'AUTO PEÇAS', 'ESCRITÓRIO CONTÁBIL', 'TRANSPORTADORA',
        'ACADEMIA', 'MERCADINHO', 'CONSTRUTORA', 'LABORATÓRIO', 'PET SHOP',
    ];

    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->error('LicasDemoSeeder não roda em produção.');

            return;
        }

        mt_srand(2026);
        $agora = Carbon::now();

        DB::transaction(function () use ($agora) {
            $this->limpar();

            DB::table('empresas')->where('id', self::EMPRESA_ID)->update(['tema' => 'licas']);

            $tab = DB::table('tabulacoes')
                ->where('empresa_id', self::EMPRESA_ID)
                ->pluck('id', 'codigo');

            $gestorId = $this->criarUsuario('Gestor Demo', 'gestor', 2, $agora);
            $vendedores = [];
            foreach (self::VENDEDORES as $i => $nome) {
                $vendedores[] = $this->criarUsuario($nome, 'vendedor'.($i + 1), 1, $agora);
            }

            $planos = $this->garantirOperadorasEPlanos($agora);
            $contatos = $this->criarContatos($vendedores, $gestorId, $tab, $agora);
            $vendas = $this->criarVendas($vendedores, $contatos, $planos, $tab, $agora);
            $this->criarRecebiveis($vendas, $tab);
            $this->criarComissoes($vendas, $vendedores, $gestorId, $tab, $agora);
            $this->criarAgendamentos($vendedores, $contatos, $agora);
            $this->garantirMeta($agora);
        });

        $this->command?->info('Licas demo: login gestor'.self::DOMINIO_EMAIL.' / '.self::SENHA);
    }

    private function limpar(): void
    {
        $userIds = DB::table('users')->where('email', 'like', '%'.self::DOMINIO_EMAIL)->pluck('id');
        $contatoIds = DB::table('contatos')
            ->where('empresa_id', self::EMPRESA_ID)
            ->where('nome_base', self::MARCA_CONTATO)
            ->pluck('id');
        $vendaIds = DB::table('vendas')
            ->where('empresa_id', self::EMPRESA_ID)
            ->where('numero_proposta', 'like', 'DEMO-%')
            ->pluck('id');

        $pagamentoIds = DB::table('comissao_pagamentos')->whereIn('vendedor_id', $userIds)->pluck('id');
        DB::table('comissao_pagamento_itens')->whereIn('comissao_pagamento_id', $pagamentoIds)->delete();
        DB::table('comissao_pagamentos')->whereIn('id', $pagamentoIds)->delete();
        DB::table('recebiveis')->whereIn('venda_id', $vendaIds)->delete();
        DB::table('vendas')->whereIn('id', $vendaIds)->delete();
        DB::table('agendamentos')->whereIn('contato_id', $contatoIds)->delete();
        DB::table('lead_atividades')->whereIn('contato_id', $contatoIds)->delete();
        DB::table('contatos_corretores')->whereIn('contato_id', $contatoIds)->delete();
        DB::table('contatos')->whereIn('id', $contatoIds)->delete();
        DB::table('users')->whereIn('id', $userIds)->delete();
    }

    private function criarUsuario(string $nome, string $login, int $roleId, Carbon $agora): int
    {
        return DB::table('users')->insertGetId([
            'name' => $nome,
            'email' => $login.self::DOMINIO_EMAIL,
            'password' => Hash::make(self::SENHA),
            'email_verified_at' => $agora,
            'ativo' => 'Y',
            'excluir_ranking' => 0,
            'empresa_id' => self::EMPRESA_ID,
            'user_role_id' => $roleId,
            'is_platform_admin' => 0,
            'created_at' => $agora->copy()->subYear(),
            'updated_at' => $agora,
        ]);
    }

    /** @return list<array{operadora_id:int, operadora:string, plano_id:int, plano:string}> */
    private function garantirOperadorasEPlanos(Carbon $agora): array
    {
        $planos = [];
        foreach (self::OPERADORAS as $operadora => $nomesPlano) {
            $operadoraId = DB::table('operadoras')
                ->where('empresa_id', self::EMPRESA_ID)
                ->where('nome', $operadora)
                ->value('id')
                ?? DB::table('operadoras')->insertGetId([
                    'empresa_id' => self::EMPRESA_ID,
                    'nome' => $operadora,
                    'status' => 'Y',
                    'created_at' => $agora,
                    'updated_at' => $agora,
                ]);

            foreach ($nomesPlano as $i => $nomePlano) {
                $planoId = DB::table('planos')
                    ->where('empresa_id', self::EMPRESA_ID)
                    ->where('operadora_id', $operadoraId)
                    ->where('nome', $nomePlano)
                    ->value('id')
                    ?? DB::table('planos')->insertGetId([
                        'empresa_id' => self::EMPRESA_ID,
                        'operadora_id' => $operadoraId,
                        'nome' => $nomePlano,
                        'status' => 'Y',
                        'acomodacao' => $i % 2 ? 'APARTAMENTO' : 'ENFERMARIA',
                        'created_at' => $agora,
                        'updated_at' => $agora,
                    ]);

                $planos[] = [
                    'operadora_id' => $operadoraId,
                    'operadora' => $operadora,
                    'plano_id' => $planoId,
                    'plano' => $nomePlano,
                ];
            }
        }

        return $planos;
    }

    /** @return list<array{id:int, nome:string, cpf:string, telefone:string, user_id:int}> */
    private function criarContatos(array $vendedores, int $gestorId, $tab, Carbon $agora): array
    {
        // Peso de cada coluna do kanban comercial: o funil afunila.
        $etapas = [
            'NOVOS_CLIENTES' => 26, 'PROSPECCAO' => 20, 'SEM_CONTATO' => 14, 'NEGOCIACAO' => 14,
            'FOLLOW_UP' => 10, 'REUNIAO' => 7, 'DOCUMENTO' => 5, 'REMARKETING' => 4,
        ];
        $temperaturas = ['FRIO' => 50, 'MORNO' => 32, 'QUENTE' => 18];

        $contatos = [];
        for ($i = 0; $i < 420; $i++) {
            $pj = mt_rand(1, 100) <= 35;
            $nome = $pj
                ? self::EMPRESAS_PJ[array_rand(self::EMPRESAS_PJ)].' '.self::SOBRENOMES[array_rand(self::SOBRENOMES)].' LTDA'
                : self::NOMES[array_rand(self::NOMES)].' '.self::SOBRENOMES[array_rand(self::SOBRENOMES)].' '.self::SOBRENOMES[array_rand(self::SOBRENOMES)];
            $criadoEm = $agora->copy()->subDays(mt_rand(0, 180))->setTime(mt_rand(8, 18), mt_rand(0, 59));
            $userId = $vendedores[array_rand($vendedores)];
            $cpf = $pj ? $this->digitos(14) : $this->digitos(11);
            $telefone = '119'.$this->digitos(8);

            $contatoId = DB::table('contatos')->insertGetId([
                'id_operacao' => $this->digitos(15),
                'empresa_id' => self::EMPRESA_ID,
                'user_import_id' => $gestorId,
                'is_ads' => mt_rand(1, 100) <= 40 ? 'Y' : 'N',
                'tipo_layout' => 'padrao',
                'nome_base' => self::MARCA_CONTATO,
                'status' => 'Y',
                'nome_cliente' => $nome,
                'cpf' => $cpf,
                'telefone1' => $telefone,
                'telefone2' => '',
                'telefone3' => '',
                'email' => strtolower(str_replace(' ', '.', $this->semAcento($nome))).'@email.com',
                'plano_ativo' => mt_rand(0, 1) ? 'Y' : 'N',
                'vidas' => (string) mt_rand(1, $pj ? 12 : 4),
                'valor_negociacao' => mt_rand(40, 900) * 10,
                'possui_cnpj' => $pj ? 'Y' : 'N',
                'created_at' => $criadoEm,
                'updated_at' => $criadoEm,
            ]);

            $etapa = $this->sortear($etapas);
            // "Dias parado" do kanban sai do updated_at: espalha de 0 a 30 dias.
            $parado = $agora->copy()->subDays(min(mt_rand(0, 30), $criadoEm->diffInDays($agora)));
            DB::table('contatos_corretores')->insert([
                'empresa_id' => self::EMPRESA_ID,
                'contato_id' => $contatoId,
                'user_id' => $userId,
                'tabulacao_id' => $tab[$etapa],
                'temperatura' => $this->sortear($temperaturas),
                'created_at' => $criadoEm,
                'updated_at' => $parado,
            ]);

            DB::table('lead_atividades')->insert([
                'empresa_id' => self::EMPRESA_ID,
                'user_id' => $userId,
                'contato_id' => $contatoId,
                'tabulacao_anterior_id' => $tab['NOVOS_CLIENTES'],
                'tabulacao_atual_id' => $tab[$etapa],
                'log_descricao' => 'Lead movido para '.$etapa,
                'created_at' => $parado,
                'updated_at' => $parado,
            ]);

            $contatos[] = ['id' => $contatoId, 'nome' => $nome, 'cpf' => $cpf, 'telefone' => $telefone, 'user_id' => $userId];
        }

        return $contatos;
    }

    /** @return list<array<string, mixed>> */
    private function criarVendas(array $vendedores, array $contatos, array $planos, $tab, Carbon $agora): array
    {
        $vendas = [];
        $seq = 1;
        // Mês atual tem a esteira cheia; meses antigos já implantaram quase tudo.
        for ($m = 11; $m >= 0; $m--) {
            $inicioMes = $agora->copy()->startOfMonth()->subMonthsNoOverflow($m);
            $qtd = mt_rand(6, 11) + (11 - $m) / 3;

            for ($k = 0; $k < (int) $qtd; $k++) {
                $contato = $contatos[array_rand($contatos)];
                $plano = $planos[array_rand($planos)];
                $limiteDia = $m === 0 ? max(1, $agora->day) : $inicioMes->daysInMonth;
                $criadoEm = $inicioMes->copy()->addDays(mt_rand(0, $limiteDia - 1))->setTime(mt_rand(9, 18), mt_rand(0, 59));
                $status = $m === 0
                    ? $this->sortear(['VENDA' => 20, 'ANALISE_DOCUMENTOS' => 18, 'ANALISE_OPERADORA' => 16, 'PENDENCIA' => 10, 'BOLETO_DISPONIVEL' => 12, 'IMPLANTADO' => 24])
                    : ($m === 1
                        ? $this->sortear(['BOLETO_DISPONIVEL' => 15, 'PENDENCIA' => 5, 'IMPLANTADO' => 75, 'ESTORNO' => 5])
                        : $this->sortear(['IMPLANTADO' => 92, 'ESTORNO' => 8]));
                $vidas = mt_rand(1, 100) <= 60 ? mt_rand(1, 4) : mt_rand(5, 14);
                $valor = round($vidas * mt_rand(380, 980) + mt_rand(0, 99) / 100, 2);
                $implantado = $status === 'IMPLANTADO';
                $angariacao = mt_rand(1, 100) <= 30;
                $vigencia = $criadoEm->copy()->addMonthNoOverflow()->startOfMonth()->addDays([0, 9, 19][mt_rand(0, 2)]);

                $vendaId = DB::table('vendas')->insertGetId([
                    'numero_proposta' => sprintf('DEMO-%05d', $seq++),
                    'empresa_id' => self::EMPRESA_ID,
                    'user_id' => $contato['user_id'],
                    'contato_id' => $contato['id'],
                    'tabulacao_id' => $tab[$status],
                    'tabulacao_updated_at' => $criadoEm->copy()->addDays(mt_rand(1, 20))->min($agora),
                    'nome_contrato' => $contato['nome'],
                    'cpf_cnpj' => $contato['cpf'],
                    'cpf_cnpj_normalizado' => $contato['cpf'],
                    'email' => null,
                    'data_vigencia' => $vigencia->toDateString(),
                    'data_implantacao' => $implantado ? $vigencia->toDateString() : null,
                    'telefone1' => $contato['telefone'],
                    'plano_id' => $plano['plano_id'],
                    'coparticipacao' => ['Y', 'N', 'PARCIAL'][mt_rand(0, 2)],
                    'operadora' => $plano['operadora'],
                    'operadora_id' => $plano['operadora_id'],
                    'nome_plano' => $plano['plano'],
                    'valor_contrato' => $valor,
                    'vidas' => $vidas,
                    'comissao_paga' => $implantado && $m >= 2 ? 1 : 0,
                    'angariacao_paga' => 0,
                    'comissao_estornada' => $status === 'ESTORNO' ? 1 : 0,
                    'angariacao_status' => $angariacao ? 'SIM' : 'NAO',
                    'angariacao_valor' => $angariacao ? round($valor * 0.1, 2) : 0,
                    'portabilidade_status' => 'NAO',
                    'layout_venda' => 'NOVO',
                    'tipo_contrato' => strlen($contato['cpf']) === 14 ? 'PJ' : 'PF',
                    'created_at' => $criadoEm,
                    'updated_at' => $criadoEm,
                ]);

                $vendas[] = [
                    'id' => $vendaId, 'user_id' => $contato['user_id'], 'status' => $status,
                    'valor' => $valor, 'vigencia' => $vigencia, 'criado_em' => $criadoEm,
                    'operadora' => $plano['operadora'], 'plano' => $plano['plano'], 'nome' => $contato['nome'],
                ];
            }
        }

        return $vendas;
    }

    private function criarRecebiveis(array $vendas, $tab): void
    {
        $hoje = Carbon::today();
        $percentuais = [1 => 100, 2 => 80, 3 => 50];

        foreach ($vendas as $venda) {
            if ($venda['status'] !== 'IMPLANTADO') {
                continue;
            }
            foreach ($percentuais as $parcela => $pct) {
                $prevista = $venda['vigencia']->copy()->addMonthsNoOverflow($parcela - 1);
                $pago = $prevista->lt($hoje->copy()->subDays(5));
                DB::table('recebiveis')->insert([
                    'empresa_id' => self::EMPRESA_ID,
                    'venda_id' => $venda['id'],
                    'vendedor_id' => $venda['user_id'],
                    'operadora' => $venda['operadora'],
                    'plano' => $venda['plano'],
                    'parcela' => $parcela,
                    'vitalicio' => 0,
                    'valor' => round($venda['valor'] * $pct / 100, 2),
                    'data_prevista' => $prevista->toDateString(),
                    'data_recebimento' => $pago ? $prevista->copy()->addDays(mt_rand(0, 4))->toDateString() : null,
                    'status' => $pago ? 'PAGO' : 'PENDENTE',
                    'created_at' => $venda['vigencia'],
                    'updated_at' => $venda['vigencia'],
                ]);
            }
        }
    }

    private function criarComissoes(array $vendas, array $vendedores, int $gestorId, $tab, Carbon $agora): void
    {
        $percComissao = 60.0;
        $percImposto = 10.0;
        $porVendedorMes = [];

        foreach ($vendas as $venda) {
            if ($venda['status'] !== 'IMPLANTADO') {
                continue;
            }
            $mes = $venda['vigencia']->format('Y-m');
            if ($mes >= $agora->format('Y-m')) {
                continue;
            }
            $porVendedorMes[$venda['user_id']][$mes][] = $venda;
        }

        foreach ($porVendedorMes as $vendedorId => $meses) {
            foreach ($meses as $mes => $itens) {
                $bruto = round(array_sum(array_column($itens, 'valor')) * $percComissao / 100, 2);
                $imposto = round($bruto * $percImposto / 100, 2);
                $dataPagamento = Carbon::createFromFormat('Y-m-d', $mes.'-01')->addMonthNoOverflow()->addDays(4);

                $pagamentoId = DB::table('comissao_pagamentos')->insertGetId([
                    'pago_em' => $dataPagamento->toDateString(),
                    'empresa_id' => self::EMPRESA_ID,
                    'vendedor_id' => $vendedorId,
                    'mes' => $mes,
                    'data_pagamento' => $dataPagamento->toDateString(),
                    'percentual_comissao' => $percComissao,
                    'percentual_imposto' => $percImposto,
                    'total_bruto' => $bruto,
                    'total_imposto' => $imposto,
                    'total_liquido' => $bruto - $imposto,
                    'salario' => 0,
                    'total_receber' => $bruto - $imposto,
                    'created_by' => $gestorId,
                    'created_at' => $dataPagamento,
                    'updated_at' => $dataPagamento,
                ]);

                foreach ($itens as $venda) {
                    $itemBruto = round($venda['valor'] * $percComissao / 100, 2);
                    $itemImposto = round($itemBruto * $percImposto / 100, 2);
                    DB::table('comissao_pagamento_itens')->insert([
                        'comissao_pagamento_id' => $pagamentoId,
                        'venda_id' => $venda['id'],
                        'is_adicional' => 0,
                        'tipo_lancamento' => 'COMISSAO',
                        'descricao' => $venda['nome'],
                        'valor_contrato' => $venda['valor'],
                        'percentual' => $percComissao,
                        'imposto_perc' => $percImposto,
                        'bruto' => $itemBruto,
                        'imposto_valor' => $itemImposto,
                        'liquido' => $itemBruto - $itemImposto,
                        'created_at' => $dataPagamento,
                        'updated_at' => $dataPagamento,
                    ]);
                }
            }
        }
    }

    private function criarAgendamentos(array $vendedores, array $contatos, Carbon $agora): void
    {
        for ($i = 0; $i < 28; $i++) {
            $contato = $contatos[array_rand($contatos)];
            $horario = $agora->copy()->addDays(mt_rand(-3, 14))->setTime(mt_rand(9, 17), [0, 30][mt_rand(0, 1)]);
            DB::table('agendamentos')->insert([
                'empresa_id' => self::EMPRESA_ID,
                'user_id' => $contato['user_id'],
                'contato_id' => $contato['id'],
                'horario_agendamento' => $horario,
                'observacao' => ['Retornar com cotação', 'Apresentar proposta PME', 'Confirmar documentos', 'Reunião com sócios'][mt_rand(0, 3)],
                'notificado' => $horario->isPast() ? 'Y' : 'N',
                'created_at' => $agora,
                'updated_at' => $agora,
            ]);
        }
    }

    private function garantirMeta(Carbon $agora): void
    {
        $existe = DB::table('meta_configuracoes')
            ->where('empresa_id', self::EMPRESA_ID)
            ->where('nome', 'Meta mensal (demo)')
            ->exists();
        if ($existe) {
            return;
        }

        DB::table('meta_configuracoes')->insert([
            'empresa_id' => self::EMPRESA_ID,
            'nome' => 'Meta mensal (demo)',
            'tipo_calculo' => 'VALOR',
            'periodo' => 'MENSAL',
            'valor_meta' => 60000,
            'meta_individual' => 0,
            'ativa' => 1,
            'created_at' => $agora,
            'updated_at' => $agora,
        ]);
    }

    /** @param array<string, int> $pesos */
    private function sortear(array $pesos): string
    {
        $alvo = mt_rand(1, array_sum($pesos));
        foreach ($pesos as $chave => $peso) {
            $alvo -= $peso;
            if ($alvo <= 0) {
                return $chave;
            }
        }

        return array_key_first($pesos);
    }

    private function digitos(int $n): string
    {
        $s = (string) mt_rand(1, 9);
        while (strlen($s) < $n) {
            $s .= mt_rand(0, 9);
        }

        return $s;
    }

    private function semAcento(string $s): string
    {
        return strtr($s, ['Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ã' => 'A', 'Õ' => 'O', 'Ô' => 'O', 'Ç' => 'C', 'Ê' => 'E']);
    }
}
