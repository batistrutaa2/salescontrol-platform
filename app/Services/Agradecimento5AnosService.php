<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\Vendas;
use Illuminate\Support\Facades\DB;

/**
 * Agradecimento individual dos 5 anos da LK Brokers.
 *
 * Exibido uma única vez por usuário após o login: vendedores veem a
 * própria jornada (primeira venda, total de vendas e vidas); demais
 * papéis recebem o agradecimento pelo empenho.
 */
class Agradecimento5AnosService
{
    /**
     * Payload do overlay, ou null se o usuário já assistiu.
     * Com $forcar = true monta o payload mesmo já visto (replay
     * pelo botão "Sua trajetória até aqui" da dashboard).
     */
    public function dadosPara(User $user, bool $forcar = false): ?array
    {
        if (!$forcar && $user->agradecimento_5anos_em !== null) {
            return null;
        }

        $primeiroNome = ucfirst(mb_strtolower(explode(' ', trim($user->name))[0]));

        $base = [
            'tipo' => 'equipe',
            'nome' => $primeiroNome,
        ];

        if ((int) $user->user_role_id !== UserRole::VENDEDOR) {
            return $base;
        }

        $estatisticas = Vendas::where('user_id', $user->id)
            ->where('empresa_id', $user->empresa_id)
            ->selectRaw('COUNT(*) as total_vendas, COALESCE(SUM(vidas), 0) as total_vidas, MIN(created_at) as primeira_venda_em')
            ->first();

        // Vendedor sem venda registrada recebe a mensagem de equipe
        if (!$estatisticas || (int) $estatisticas->total_vendas === 0) {
            return $base;
        }

        // Query builder direto: o model Vendas tem accessor que formata
        // created_at em d/m/Y, o que quebraria o Carbon::parse aqui.
        $primeiraVenda = DB::table('vendas')
            ->where('user_id', $user->id)
            ->where('empresa_id', $user->empresa_id)
            ->orderBy('created_at')
            ->first(['created_at', 'operadora']);

        return [
            'tipo' => 'vendedor',
            'nome' => $primeiroNome,
            'primeiraVendaData' => $primeiraVenda?->created_at
                ? \Illuminate\Support\Carbon::parse($primeiraVenda->created_at)
                    ->setTimezone('America/Sao_Paulo')
                    ->format('d/m/Y')
                : null,
            'primeiraVendaOperadora' => $primeiraVenda->operadora,
            'totalVendas' => (int) $estatisticas->total_vendas,
            'totalVidas' => (int) $estatisticas->total_vidas,
        ];
    }

    public function marcarVisto(User $user): void
    {
        DB::table('users')->where('id', $user->id)->update([
            'agradecimento_5anos_em' => now(),
        ]);
    }
}
