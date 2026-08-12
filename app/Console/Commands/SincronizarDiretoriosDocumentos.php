<?php

namespace App\Console\Commands;

use App\Models\DocumentoDiretorio;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class SincronizarDiretoriosDocumentos extends Command
{
    protected $signature = 'documentos:sincronizar-diretorios';
    protected $description = 'Sincroniza o catálogo local das pastas de operadoras do servidor documental';

    public function handle(): int
    {
        $raiz = trim(config('documentos.root'), '/');
        $agora = now();

        try {
            $pastas = Storage::disk(config('documentos.disk'))->directories($raiz);
            foreach ($pastas as $caminho) {
                DocumentoDiretorio::updateOrCreate(
                    ['caminho' => $caminho],
                    ['nome' => basename($caminho), 'encontrado_em' => $agora]
                );
            }
            DocumentoDiretorio::where('encontrado_em', '<', $agora)->delete();
            Cache::forever('documentos:diretorios:sincronizado_em', $agora->toIso8601String());
            Cache::forget('documentos:diretorios:erro');
            $this->info(count($pastas).' pasta(s) sincronizada(s).');
            return self::SUCCESS;
        } catch (\Throwable $e) {
            Cache::put('documentos:diretorios:erro', mb_substr($e->getMessage(), 0, 500), now()->addDay());
            $this->error('Falha ao sincronizar o catálogo de pastas.');
            return self::FAILURE;
        }
    }
}
