<?php

namespace App\Modules\LkBeneficios\Models;

use App\Models\People\Empresa as EmpresaLemit;
use App\Models\People\Pessoa;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    use HasFactory, SoftDeletes;

    protected static function newFactory()
    {
        return \Database\Factories\LkBeneficios\LeadFactory::new();
    }

    protected $table = 'lk_beneficios_leads';

    protected $fillable = [
        'empresa_id',
        'user_id',
        'produto_interesse_id',
        'pessoa_id',
        'empresa_lemit_id',
        'cliente_tipo',
        'cpf_cnpj',
        'nome',
        'email',
        'telefone',
        'status',
        'origem',
        'ordem_kanban',
        'data_ultima_movimentacao',
        'observacoes',
        'informacao_fixada',
        'convertido_em',
    ];

    protected $casts = [
        'data_ultima_movimentacao' => 'datetime',
        'convertido_em' => 'datetime',
        'ordem_kanban' => 'integer',
    ];

    public function produtoInteresse()
    {
        return $this->belongsTo(Produto::class, 'produto_interesse_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function pessoa()
    {
        return $this->belongsTo(Pessoa::class, 'pessoa_id');
    }

    public function empresaLemit()
    {
        return $this->belongsTo(EmpresaLemit::class, 'empresa_lemit_id');
    }

    public function historico()
    {
        return $this->hasMany(LeadHistorico::class, 'lead_id')->orderByDesc('created_at');
    }

    public function contratos()
    {
        return $this->hasMany(Contrato::class, 'lead_id');
    }

    public function comentarios()
    {
        return $this->hasMany(LeadComentario::class, 'lead_id')->orderByDesc('created_at');
    }

    public function scopeAtivos($query)
    {
        return $query->whereNull('convertido_em');
    }

    public function scopeDaEmpresa($query, int $empresaId)
    {
        return $query->where('empresa_id', $empresaId);
    }
}
