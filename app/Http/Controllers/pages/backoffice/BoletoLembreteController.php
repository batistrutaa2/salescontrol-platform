<?php

namespace App\Http\Controllers\pages\backoffice;

use App\Http\Controllers\Controller;
use App\Models\BoletoAgenda;
use App\Models\BoletoLembrete;
use App\Models\Vendas;
use App\Services\BoletoLembreteService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

class BoletoLembreteController extends Controller
{
    public function __construct(private BoletoLembreteService $service) {}

    public function index(Request $request)
    {
        $filtros = $request->validate([
            'busca' => ['nullable', 'string', 'max:160'],
            'situacao' => ['nullable', 'in:todos,sem_cadastro,ativos,pausados'],
            'lembrete' => ['nullable', 'integer'],
            'quinzena' => ['nullable', 'in:1,2'],
            'mes' => ['nullable', 'date_format:Y-m'],
        ]);
        $empresaId = $this->tenantId();
        $periodo = function ($query, string $coluna) use ($filtros) {
            if (! empty($filtros['mes'])) {
                $inicio = CarbonImmutable::createFromFormat('!Y-m', $filtros['mes']);
                $query->whereBetween($coluna, [$inicio->toDateString(), $inicio->endOfMonth()->toDateString()]);
            }
            if (! empty($filtros['quinzena'])) {
                $query->whereDay($coluna, $filtros['quinzena'] === '1' ? '<=' : '>', 15);
            }
        };
        $base = $this->service->implantados($empresaId);
        $agendas = BoletoAgenda::where('empresa_id', $empresaId)->whereNotNull('venda_id')->select('venda_id');
        $contratos = (clone $base)
            ->when($filtros['busca'] ?? null, fn ($q, $busca) => $q->where(fn ($q) => $q->where('nome_contrato', 'like', '%'.$busca.'%')->orWhere('numero_proposta', 'like', '%'.$busca.'%')))
            ->when(($filtros['situacao'] ?? '') === 'sem_cadastro', fn ($q) => $q->whereNotIn('id', clone $agendas))
            ->when(($filtros['situacao'] ?? '') === 'ativos', fn ($q) => $q->whereIn('id', (clone $agendas)->where('ativo', true)))
            ->when(($filtros['situacao'] ?? '') === 'pausados', fn ($q) => $q->whereIn('id', (clone $agendas)->where('ativo', false)))
            ->when(! empty($filtros['mes']) || ! empty($filtros['quinzena']), fn ($q) => $q->whereIn('id', (clone $agendas)->where(fn ($a) => $periodo($a, 'proximo_vencimento'))))
            ->orderBy('nome_contrato')->paginate(20)->withQueryString();
        $configuracoes = BoletoAgenda::whereIn('venda_id', $contratos->pluck('id'))->get()->keyBy('venda_id');
        $pendentes = $this->service->pendentes($empresaId)->with('venda')
            ->where(fn ($q) => $periodo($q, 'vencimento'))
            ->when($filtros['lembrete'] ?? null, fn ($q, $id) => $q->whereKey($id))
            ->orderBy('vencimento')->paginate(10, ['*'], 'lembretes_page')->withQueryString();
        $historico = BoletoLembrete::where('empresa_id', $empresaId)->whereNotNull('tratado_em')
            ->with(['venda', 'tratadoPor'])->latest('tratado_em')->limit(10)->get();

        $contratoAnterior = $request->session()->has('errors') && is_scalar($request->old('boleto_venda'))
            ? (clone $base)->find((int) $request->old('boleto_venda'))
            : null;

        $manuais = BoletoAgenda::where('empresa_id', $empresaId)->whereNull('venda_id')
            ->where(fn ($q) => $periodo($q, 'proximo_vencimento'))
            ->when($filtros['busca'] ?? null, fn ($q, $busca) => $q->where(fn ($q) => $q->where('nome_cliente', 'like', '%'.$busca.'%')->orWhere('referencia', 'like', '%'.$busca.'%')))
            ->when(($filtros['situacao'] ?? '') === 'ativos', fn ($q) => $q->where('ativo', true))
            ->when(($filtros['situacao'] ?? '') === 'pausados', fn ($q) => $q->where('ativo', false))
            ->when(($filtros['situacao'] ?? '') === 'sem_cadastro', fn ($q) => $q->whereRaw('1 = 0'))
            ->orderBy('nome_cliente')->paginate(15, ['*'], 'clientes_page')->withQueryString();
        $manualAnterior = null;
        if ($request->session()->has('errors') && $request->old('boleto_manual') === '1') {
            $id = $request->old('boleto_agenda');
            $manualAnterior = is_scalar($id) && $id
                ? BoletoAgenda::where('empresa_id', $empresaId)->whereNull('venda_id')->find((int) $id)
                : new BoletoAgenda;
        }

        return view('content.pages.backoffice.boletos', [
            'contratoAnterior' => $contratoAnterior,
            'manualAnterior' => $manualAnterior,
            'manuais' => $manuais,
            'contratos' => $contratos,
            'configuracoes' => $configuracoes,
            'pendentes' => $pendentes,
            'historico' => $historico,
            'totalImplantados' => (clone $base)->count(),
            'semCadastro' => (clone $base)->whereNotIn('id', clone $agendas)->count(),
            'hoje' => CarbonImmutable::today('America/Sao_Paulo'),
        ]);
    }

    public function configurar(Request $request, Vendas $venda)
    {
        abort_unless((int) $venda->empresa_id === $this->tenantId(), 404);
        $request->merge(['boleto_venda' => $venda->id, 'boleto_manual' => '0']);
        $hoje = CarbonImmutable::today('America/Sao_Paulo')->toDateString();
        $dados = $request->validate([
            'dia_vencimento' => ['required', 'integer', 'between:1,31'],
            'proximo_vencimento' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.$hoje],
            'ativo' => ['required', 'boolean'],
        ]);
        $this->service->configurar($venda, $request->user(), $dados);
        $this->service->sincronizar($this->tenantId());

        return back()->with('boleto_status', 'Boleto salvo. O aviso é gerado 10 dias antes do vencimento.');
    }

    public function criarManual(Request $request)
    {
        return $this->salvarManual($request);
    }

    public function editarManual(Request $request, BoletoAgenda $agenda)
    {
        abort_unless((int) $agenda->empresa_id === $this->tenantId() && $agenda->venda_id === null, 404);

        return $this->salvarManual($request, $agenda);
    }

    private function salvarManual(Request $request, ?BoletoAgenda $agenda = null)
    {
        $request->merge(['boleto_manual' => '1', 'boleto_agenda' => $agenda?->id, 'boleto_venda' => null]);
        $dados = $request->validate([
            'nome_cliente' => ['required', 'string', 'max:160'],
            'referencia' => ['nullable', 'string', 'max:160'],
            'dia_vencimento' => ['required', 'integer', 'between:1,31'],
            'proximo_vencimento' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.CarbonImmutable::today('America/Sao_Paulo')->toDateString()],
            'ativo' => ['required', 'boolean'],
        ]);
        $this->service->configurarManual($agenda, $request->user(), $this->tenantId(), $dados);
        $this->service->sincronizar($this->tenantId());

        return redirect()->route('backoffice.boletos.index')->with('boleto_status', 'Vencimento mensal salvo para o cliente.');
    }

    public function excluirAgenda(BoletoAgenda $agenda)
    {
        abort_unless((int) $agenda->empresa_id === $this->tenantId(), 404);
        $this->service->excluirAgenda($agenda);

        return back()->with('boleto_status', 'Cadastro excluído. Os avisos futuros e pendentes foram cancelados; acompanhamentos tratados foram preservados.');
    }

    public function editarLembrete(Request $request, BoletoLembrete $lembrete)
    {
        abort_unless((int) $lembrete->empresa_id === $this->tenantId(), 404);
        $request->merge(['boleto_lembrete' => $lembrete->id]);
        $dados = $request->validate([
            'vencimento' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.$lembrete->data_notificacao->toDateString()],
            'referencia' => ['nullable', 'string', 'max:160'],
        ]);
        $this->service->alterarLembrete($lembrete, $dados);

        return back()->with('boleto_status', 'Boleto atualizado. A recorrência mensal permanece com as datas do cadastro.');
    }

    public function excluirLembrete(BoletoLembrete $lembrete)
    {
        abort_unless((int) $lembrete->empresa_id === $this->tenantId(), 404);
        $this->service->alterarLembrete($lembrete, null);

        return back()->with('boleto_status', 'Boleto excluído. Os próximos meses continuam programados.');
    }

    public function tratar(Request $request, BoletoLembrete $lembrete)
    {
        abort_unless((int) $lembrete->empresa_id === $this->tenantId(), 404);
        $request->merge(['boleto_lembrete' => $lembrete->id]);
        $dados = $request->validate(['observacao' => ['nullable', 'string', 'max:1000']]);
        $this->service->tratar($lembrete, $request->user(), $dados['observacao'] ?? null);

        return redirect()->route('backoffice.boletos.index')->with('boleto_status', 'Lembrete tratado. O histórico foi preservado.');
    }

    public function resumo()
    {
        $pendentes = $this->service->pendentes($this->tenantId());

        return response()->json([
            'total' => (clone $pendentes)->count(),
            'hoje' => (clone $pendentes)->whereDate('data_notificacao', CarbonImmutable::today('America/Sao_Paulo'))->count(),
            'url' => route('backoffice.boletos.index'),
        ]);
    }
}
