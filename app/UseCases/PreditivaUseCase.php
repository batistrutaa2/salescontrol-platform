<?php

namespace App\UseCases;

use App\Models\Contatos;
use App\Repositories\Contracts\ContatosCorretoresRepositoryInterface;
use App\Repositories\Contracts\LogPreditivaRepositoryInterface;
use App\Repositories\Contracts\PreditivaRepositoryInterface;
use App\Repositories\Eloquent\ContatosCorretoresRepository;
use App\Repositories\Eloquent\LogPreditivaRepository;
use App\Repositories\Eloquent\PreditivaRepository;
use Illuminate\Support\Facades\DB;

class PreditivaUseCase
{
    protected PreditivaRepository $previtivaRepository;

    protected LogPreditivaRepository $logPreditivaRepository;

    protected ContatosCorretoresRepository $contatosCorretoresRepository;

    public function __construct(
        PreditivaRepositoryInterface $preditivaRepositoryInterface,
        LogPreditivaRepositoryInterface $logPreditivaRepositoryInterface,
        ContatosCorretoresRepositoryInterface $contatosCorretoresRepositoryInterface)
    {
        $this->previtivaRepository = $preditivaRepositoryInterface;
        $this->logPreditivaRepository = $logPreditivaRepositoryInterface;
        $this->contatosCorretoresRepository = $contatosCorretoresRepositoryInterface;
    }

    public function sendMailingPredictive($id_mailing)
    {
        try {
            $empresaId = app(\App\Support\TenantContext::class)->id();

            if (! Contatos::where('id', $id_mailing)->where('empresa_id', $empresaId)->exists()) {
                return false;
            }

            DB::beginTransaction();

            // Reativar contato caso esteja descartado (status = 'N')
            Contatos::where('id', $id_mailing)
                ->where('empresa_id', $empresaId)
                ->where('status', 'N')
                ->update(['status' => 'Y']);

            // Remove de contatos_corretores (pode retornar 0 para leads já descartados — ok)
            $this->contatosCorretoresRepository->deleteMailing($id_mailing);

            // Remove registro antigo na preditiva para evitar duplicidade
            \App\Models\Preditiva::where('contato_id', $id_mailing)
                ->where('empresa_id', $empresaId)
                ->delete();

            $createMailingPreditiva = $this->previtivaRepository->create([
                'empresa_id' => $empresaId,
                'contato_id' => $id_mailing,
                'status' => 'Y',
            ]);

            if ($createMailingPreditiva) {
                DB::commit();

                return true;
            }
            DB::rollBack();

            return false;
        } catch (\Throwable $th) {
            DB::rollBack();

            return false;
        }
    }
}
