<?php

namespace App\Console\Commands;

use App\Models\ContatosCorretores;
use Illuminate\Console\Command;

class ClearDealNotClosed extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:TaskClearDealNotClosed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'limpa a fila de negocio não fechado e transfere para remaketing';

    /**
     * Execute the console command.
     */
    public function handle()
    {
      ContatosCorretores::where('tabulacao_id', 6)->update(['tabulacao_id' => 10]);
    }
}
