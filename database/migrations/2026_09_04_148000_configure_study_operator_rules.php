<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('operadoras', function (Blueprint $table) {
            $table->decimal('iof_percentual', 5, 2)->default(0)->after('angariacao_padrao');
            $table->string('cor_marca', 7)->default('#334155')->after('iof_percentual');
            $table->string('logo_path', 500)->nullable()->after('cor_marca');
        });

        Schema::table('estudo_itens', function (Blueprint $table) {
            $table->foreignId('operadora_id')->nullable()->after('estudo_id')->constrained('operadoras')->nullOnDelete();
            $table->foreignId('plano_id')->nullable()->after('operadora_id')->constrained('planos')->nullOnDelete();
        });

        $this->migrarConfiguracoesHistoricas();
        $this->vincularItensHistoricos();
    }

    private function migrarConfiguracoesHistoricas(): void
    {
        // Conversão única dos comportamentos históricos. A aplicação passa a ler
        // exclusivamente a configuração da operadora pertencente ao tenant.
        DB::table('operadoras')
            ->where(function ($query) {
                $query->whereRaw('UPPER(nome) LIKE ?', ['%BRADESCO%'])
                    ->orWhereRaw('UPPER(nome) LIKE ?', ['%PORTO%'])
                    ->orWhereRaw('UPPER(nome) LIKE ?', ['%SULAMERICA%']);
            })
            ->update(['iof_percentual' => 2.38]);

        $regrasVisuais = [
            ['termo' => 'AMIL', 'cor' => '#0066B3', 'logo' => 'assets/img/logos-operadoras/amil.png'],
            ['termo' => 'PORTO SEGURO', 'cor' => '#009CDE', 'logo' => 'assets/img/logos-operadoras/porto-seguro.png'],
            ['termo' => 'BRADESCO', 'cor' => '#CC092F', 'logo' => 'assets/img/logos-operadoras/bradesco.png'],
            ['termo' => 'SULAMERICA', 'cor' => '#002776', 'logo' => 'assets/img/logos-operadoras/sulamerica.png'],
            ['termo' => 'UNIMED', 'cor' => '#006837', 'logo' => 'assets/img/logos-operadoras/unimed.png'],
            ['termo' => 'ALICE', 'cor' => '#FF4A4A', 'logo' => 'assets/img/logos-operadoras/alice.png'],
            ['termo' => 'OMINT', 'cor' => '#002147', 'logo' => 'assets/img/logos-operadoras/omint.png'],
            ['termo' => 'QUALICORP', 'cor' => '#003D79', 'logo' => null],
            ['termo' => 'PLENA', 'cor' => '#009639', 'logo' => 'assets/img/logos-operadoras/plenasaude.png'],
            ['termo' => 'HAPVIDA', 'cor' => '#005BAB', 'logo' => null],
            ['termo' => 'MEDSENIOR', 'cor' => '#F58220', 'logo' => null],
            ['termo' => 'PREVENTSENIOR', 'cor' => '#0A3D91', 'logo' => null],
            ['termo' => 'TRASMONTANO', 'cor' => '#006837', 'logo' => null],
            ['termo' => 'AMPLA', 'cor' => '#009739', 'logo' => null],
            ['termo' => 'BLUE', 'cor' => '#0072CE', 'logo' => null],
        ];

        foreach ($regrasVisuais as $regra) {
            DB::table('operadoras')
                ->whereRaw('UPPER(nome) LIKE ?', ['%'.$regra['termo'].'%'])
                ->update(['cor_marca' => $regra['cor'], 'logo_path' => $regra['logo']]);
        }
    }

    private function vincularItensHistoricos(): void
    {
        DB::table('estudo_itens')
            ->join('estudos', 'estudos.id', '=', 'estudo_itens.estudo_id')
            ->select('estudo_itens.id', 'estudo_itens.operadora_plano', 'estudos.empresa_id')
            ->orderBy('estudo_itens.id')
            ->get()
            ->each(function ($item) {
                $operadoras = DB::table('operadoras')
                    ->where('empresa_id', $item->empresa_id)
                    ->get(['id', 'nome'])
                    ->sortByDesc(fn ($operadora) => mb_strlen($operadora->nome));

                foreach ($operadoras as $operadora) {
                    $prefixo = $operadora->nome.' - ';
                    if (! Str::startsWith(Str::lower($item->operadora_plano), Str::lower($prefixo))) {
                        continue;
                    }

                    $nomePlano = trim(mb_substr($item->operadora_plano, mb_strlen($prefixo)));
                    $plano = DB::table('planos')
                        ->where('empresa_id', $item->empresa_id)
                        ->where('operadora_id', $operadora->id)
                        ->whereRaw('LOWER(nome) = ?', [Str::lower($nomePlano)])
                        ->first(['id']);

                    if ($plano) {
                        DB::table('estudo_itens')->where('id', $item->id)->update([
                            'operadora_id' => $operadora->id,
                            'plano_id' => $plano->id,
                        ]);
                    }

                    break;
                }
            });
    }

    public function down(): void
    {
        Schema::table('estudo_itens', function (Blueprint $table) {
            $table->dropConstrainedForeignId('plano_id');
            $table->dropConstrainedForeignId('operadora_id');
        });

        Schema::table('operadoras', function (Blueprint $table) {
            $table->dropColumn(['iof_percentual', 'cor_marca', 'logo_path']);
        });
    }
};
