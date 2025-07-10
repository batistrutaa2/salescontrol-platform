<?php

namespace App\Models\People;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Pessoa extends Model
{
    use HasFactory;

    protected $connection = 'people_db'; // usa o banco secundário
    protected $table = 'pessoas';

        protected $fillable = [
        'cpf',
        'nome',
        'data_nascimento',
        'sexo',
        'nome_mae',
        'falecido',
        'situacao_cpf',
        'renda',
        'ocupacao',
        'data_consulta',
    ];

        public function celulares(): HasMany
    {
        return $this->hasMany(Celular::class);
    }

    public function fixos(): HasMany
    {
        return $this->hasMany(Fixo::class);
    }

    public function emails(): HasMany
    {
        return $this->hasMany(Email::class);
    }

    public function enderecos(): HasMany
    {
        return $this->hasMany(Endereco::class);
    }

    public function carros(): HasMany
    {
        return $this->hasMany(Carro::class);
    }

    public function vinculos(): HasMany
    {
        return $this->hasMany(Vinculo::class);
    }

    public function riscosCredito(): HasMany
    {
        return $this->hasMany(RiscoCredito::class);
    }

    public function participacoesSocietarias(): HasMany
    {
        return $this->hasMany(ParticipacaoSocietaria::class);
    }

}
