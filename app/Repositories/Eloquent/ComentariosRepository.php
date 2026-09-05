<?php

namespace App\Repositories\Eloquent;

use App\Enums\UserRole;
use App\Models\Comentarios;
use App\Repositories\Contracts\ComentariosRepositoryInterface;
use Illuminate\Support\Facades\Auth;

class ComentariosRepository implements ComentariosRepositoryInterface
{
    protected $model;

    public function __construct(Comentarios $model)
    {
        $this->model = $model;
    }

    public function clearComments(array $data)
    {
        try {
            $empresaId = (int) app(\App\Support\TenantContext::class)->id();
            $leadIds = explode(',', $data['selectedLeadIds']);
            array_map(function ($leadId) use ($empresaId) {
                $this->model->where('contato_id', $leadId)->where('empresa_id', $empresaId)->update([
                    'visivel' => 'N',
                ]);
            }, $leadIds);

            return true;
        } catch (\Throwable $th) {
            return false;
        }
    }

    public function clearCommentsOne($idMailing)
    {
        try {
            $this->model->where('contato_id', $idMailing)
                ->where('empresa_id', app(\App\Support\TenantContext::class)->id())
                ->update([
                    'visivel' => 'N',
                ]);

            return true;
        } catch (\Throwable $th) {
            return false;
        }
    }

    public function createComment($empresa_id, $user_id, $comment, $contato_id)
    {
        try {
            $supervisao = 'N';
            if (Auth::user()->user_role_id != UserRole::VENDEDOR) {
                $supervisao = 'Y';
            }

            $commentModel = new $this->model;
            $commentModel->empresa_id = $empresa_id;
            $commentModel->user_id = $user_id;
            $commentModel->contato_id = $contato_id;
            $commentModel->anotacao = $comment;
            $commentModel->supervisao = $supervisao;

            return $commentModel->save();
        } catch (\Throwable $th) {
            return false;
        }
    }

    public function getCommentsMailing($contato_id)
    {
        $user = Auth::user();

        return $this->model->where('contato_id', $contato_id)
            ->where('empresa_id', app(\App\Support\TenantContext::class)->id())
            ->when($user->user_role_id == UserRole::VENDEDOR, function ($query) use ($user) {
                $query->where(function ($subQuery) use ($user) {
                    $subQuery->where(function ($q) use ($user) {
                        $q->where('comentarios.visivel', 'Y')
                            ->where('comentarios.user_id', $user->id);
                    })
                        ->orWhere(function ($q) {
                            $q->where('comentarios.supervisao', 'Y')
                                ->where('comentarios.visivel', 'Y');
                        });
                });
            })
            ->orderBy('created_at', 'desc')->get();
    }

    public function getCommentsMailingAll($contato_id)
    {
        $user = Auth::user();

        $comentarios = $this->model->leftJoin('users', function ($join) {
            $join->on('users.id', '=', 'comentarios.user_id')
                ->where(function ($visibility) {
                    $visibility->whereColumn('users.empresa_id', 'comentarios.empresa_id')
                        ->orWhere('users.is_platform_admin', true);
                });
        })
            ->leftJoin('user_roles', 'users.user_role_id', '=', 'user_roles.id')
            ->where('comentarios.contato_id', $contato_id)
            ->where('comentarios.empresa_id', app(\App\Support\TenantContext::class)->id())
            ->when($user->user_role_id == UserRole::VENDEDOR, function ($query) use ($user) {
                $query->where(function ($subQuery) use ($user) {
                    $subQuery->where(function ($q) use ($user) {
                        $q->where('comentarios.visivel', 'Y')
                            ->where('comentarios.user_id', $user->id);
                    })
                        ->orWhere(function ($q) {
                            $q->where('comentarios.supervisao', 'Y')
                                ->where('comentarios.visivel', 'Y');
                        });
                });
            })
            ->select(
                'comentarios.id',
                'comentarios.user_id',
                'comentarios.anotacao',
                'comentarios.editado_em',
                'comentarios.created_at',
                'users.name',
                'user_roles.tipo_usuario'
            )
          // fixado so vale para o proprio autor: comentario fixado por outro usuario aparece como comum
            ->selectRaw("CASE WHEN comentarios.fixado = 'Y' AND comentarios.user_id = ? THEN 1 ELSE 0 END as fixado_proprio", [$user->id])
            ->orderByDesc('fixado_proprio')
            ->orderBy('comentarios.created_at', 'desc')
            ->get();

        return $comentarios;
    }

    public function togglePinComment($comentarioId)
    {
        $user = Auth::user();

        $comentario = $this->model
            ->where('id', $comentarioId)
            ->where('empresa_id', app(\App\Support\TenantContext::class)->id())
            ->where('user_id', $user->id)
            ->first();

        if (! $comentario) {
            return false;
        }

        $fixar = $comentario->fixado !== 'Y';
        $comentario->fixado = $fixar ? 'Y' : 'N';
        $comentario->fixado_em = $fixar ? now() : null;
        $comentario->save();

        return $comentario;
    }

    public function updateOwnComment($comentarioId, $anotacao)
    {
        $user = Auth::user();

        $comentario = $this->model
            ->where('id', $comentarioId)
            ->where('empresa_id', app(\App\Support\TenantContext::class)->id())
            ->where('user_id', $user->id)
            ->first();

        if (! $comentario) {
            return false;
        }

        $comentario->anotacao = $anotacao;
        $comentario->editado_em = now();
        $comentario->save();

        return $comentario;
    }
}
