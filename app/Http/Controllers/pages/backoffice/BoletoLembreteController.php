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
        ]);
        $empresaId = $this->tenantId();
        $base = $this->service->implantados($empresaId);
        $agendas = BoletoAgenda::where('empresa_id', $empresaId)->select('venda_id');
        $contratos = (clone $base)
            ->when($filtros['busca'] ?? null, fn ($q, $busca) => $q->where(fn ($q) => $q->where('nome_contrato', 'like', '%'.$busca.'%')->orWhere('numero_proposta', 'like', '%'.$busca.'%')))
            ->when(($filtros['situacao'] ?? '') === 'sem_cadastro', fn ($q) => $q->whereNotIn('id', clone $agendas))
            ->when(($filtros['situacao'] ?? '') === 'ativos', fn ($q) => $q->whereIn('id', (clone $agendas)->where('ativo', true)))
            ->when(($filtros['situacao'] ?? '') === 'pausados', fn ($q) => $q->whereIn('id', (clone $agendas)->where('ativo', false)))
            ->orderBy('nome_contrato')->paginate(20)->withQueryString();
        $configuracoes = BoletoAgenda::whereIn('venda_id', $contratos->pluck('id'))->get()->keyBy('venda_id');
        $pendentes = $this->service->pendentes($empresaId)->with('venda')
            ->when($filtros['lembrete'] ?? null, fn ($q, $id) => $q->whereKey($id))
            ->orderBy('vencimento')->paginate(10, ['*'], 'lembretes_page')->withQueryString();
        $historico = BoletoLembrete::where('empresa_id', $empresaId)->whereNotNull('tratado_em')
            ->with(['venda', 'tratadoPor'])->latest('tratado_em')->limit(10)->get();

        $contratoAnterior = $request->session()->has('errors') && is_scalar($request->old('boleto_venda'))
            ? (clone $base)->find((int) $request->old('boleto_venda'))
            : null;

        return view('content.pages.backoffice.boletos', [
            'contratoAnterior' => $contratoAnterior,
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
        $request->merge(['boleto_venda' => $venda->id]);
        $hoje = CarbonImmutable::today('America/Sao_Paulo')->toDateString();
        $dados = $request->validate([
            'dia_vencimento' => ['required', 'integer', 'between:1,31'],
            'proximo_vencimento' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.$hoje],
            'ativo' => ['required', 'boolean'],
        ]);
        $this->service->configurar($venda, $request->user(), $dados);
        $this->service->sincronizar($this->tenantId());

        return back()->with('boleto_status', 'Vencimento mensal salvo. O aviso será gerado no dia cadastrado.');
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
            'hoje' => (clone $pendentes)->whereDate('vencimento', CarbonImmutable::today('America/Sao_Paulo'))->count(),
            'url' => route('backoffice.boletos.index'),
        ]);
    }
}
