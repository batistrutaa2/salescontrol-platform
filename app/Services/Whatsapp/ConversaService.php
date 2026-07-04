<?php

namespace App\Services\Whatsapp;

use App\Models\WhatsappConversa;
use App\Models\WhatsappInstancia;
use Illuminate\Support\Facades\DB;

class ConversaService
{
    /**
     * Busca ou cria a conversa de um remoteJid para a instância.
     * Na criação: normaliza o número, tenta vincular lead do vendedor
     * e posiciona na primeira coluna do funil comercial.
     */
    public function resolverConversa(WhatsappInstancia $instancia, string $remoteJid, ?string $pushName = null): WhatsappConversa
    {
        $conversa = WhatsappConversa::where('instancia_id', $instancia->id)
            ->where('remote_jid', $remoteJid)
            ->first();

        if ($conversa) {
            if ($pushName && $conversa->nome_whatsapp !== $pushName) {
                $conversa->update(['nome_whatsapp' => $pushName]);
            }

            return $conversa;
        }

        $numero = PhoneMatcher::numeroDoJid($remoteJid);
        $numeroNormalizado = PhoneMatcher::normalizar($numero);

        $conversa = WhatsappConversa::firstOrCreate(
            [
                'instancia_id' => $instancia->id,
                'remote_jid' => $remoteJid,
            ],
            [
                'empresa_id' => $instancia->empresa_id,
                'user_id' => $instancia->user_id,
                'numero' => $numero,
                'numero_normalizado' => $numeroNormalizado,
                'nome_whatsapp' => $pushName,
                'tabulacao_id' => $this->primeiraTabulacaoComercial($instancia->empresa_id),
                'contato_id' => $numeroNormalizado
                  ? $this->buscarContatoDoVendedor($instancia->empresa_id, $instancia->user_id, $numeroNormalizado)
                  : null,
            ]
        );

        if ($conversa->wasRecentlyCreated) {
            \App\Jobs\Whatsapp\AtualizarFotoPerfilConversa::dispatch($conversa->id);
        }

        return $conversa;
    }

    /**
     * Primeira coluna do funil comercial (menor ordem_kanban) da empresa.
     */
    public function primeiraTabulacaoComercial(int $empresaId): ?int
    {
        return DB::table('tabulacoes')
            ->where('empresa_id', $empresaId)
            ->where('status', 'Y')
            ->where('tipo_tabulacao', 'C')
            ->orderBy('ordem_kanban')
            ->value('id');
    }

    /**
     * Procura um lead do vendedor cujo telefone1/2/3 bata com o número
     * normalizado (DDD + últimos 8 dígitos).
     */
    public function buscarContatoDoVendedor(int $empresaId, int $userId, string $numeroNormalizado): ?int
    {
        $match = 'CONCAT(LEFT(%1$s, 2), RIGHT(%1$s, 8)) = ?';

        return DB::table('contatos as c')
            ->join('contatos_corretores as cc', 'cc.contato_id', '=', 'c.id')
            ->where('cc.empresa_id', $empresaId)
            ->where('cc.user_id', $userId)
            ->where(function ($query) use ($match, $numeroNormalizado) {
                $query->whereRaw(sprintf($match, 'c.telefone1'), [$numeroNormalizado])
                    ->orWhereRaw(sprintf($match, 'c.telefone2'), [$numeroNormalizado])
                    ->orWhereRaw(sprintf($match, 'c.telefone3'), [$numeroNormalizado]);
            })
            ->orderByDesc('cc.id')
            ->value('c.id');
    }
}
