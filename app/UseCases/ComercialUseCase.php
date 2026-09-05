<?php

namespace App\UseCases;

use App\Repositories\Contracts\ComentariosRepositoryInterface;
use App\Repositories\Contracts\ContatosCorretoresRepositoryInterface;
use App\Repositories\Contracts\ContatosRepositoryInterface;
use App\Repositories\Contracts\LeadAtividadeRepositoryInterface;
use App\Repositories\Eloquent\ComentariosRepository;
use App\Repositories\Eloquent\ContatosCorretoresRepository;
use App\Repositories\Eloquent\ContatosRepository;
use App\Repositories\Eloquent\LeadAtividadeRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ComercialUseCase
{
    protected ContatosCorretoresRepository $contatosCorretoresRepository;

    protected ContatosRepository $contatosRepository;

    protected ComentariosRepository $comentariosRepository;

    protected LeadAtividadeRepository $leadAtividadeRepository;

    public function __construct(
        ContatosRepositoryInterface $contatosRepositoryInterface,
        ContatosCorretoresRepositoryInterface $ContatosCorretoresRepositoryInterface,
        ComentariosRepositoryInterface $ComentariosRepositoryInterface,
        LeadAtividadeRepositoryInterface $leadAtividadeRepositoryInterface
    ) {
        $this->contatosCorretoresRepository = $ContatosCorretoresRepositoryInterface;
        $this->contatosRepository = $contatosRepositoryInterface;
        $this->comentariosRepository = $ComentariosRepositoryInterface;
        $this->leadAtividadeRepository = $leadAtividadeRepositoryInterface;
    }

    public function saveDataInfo($request, $idMailing, $telefone1, $telefone2, $telefone3, $comment, $temperature, $negotiationValue)
    {
        DB::beginTransaction();
        try {
            $this->leadAtividadeRepository->create($request->all());
            $this->contatosRepository->updateContact($idMailing, $telefone1, $telefone2, $telefone3, $negotiationValue);
            $this->contatosCorretoresRepository->updateLeadTemperature($idMailing, $temperature);
            if ($comment !== 'null') {
                $this->comentariosRepository->createComment(app(\App\Support\TenantContext::class)->id(), Auth::user()->id, $comment, $idMailing);
            }

            DB::commit();

            return response()->json(
                [
                    'error' => false,
                    'message' => 'Tabulação concluida com sucesso',
                ],
                201
            );
        } catch (\Throwable $th) {
            DB::rollBack();

            return response()->json(
                [
                    'error' => true,
                    'message' => $th->getMessage(),
                ],
                501
            );
        }
    }
}
