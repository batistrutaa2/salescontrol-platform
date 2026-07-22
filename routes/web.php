<?php


use App\Http\Controllers\Auth\Auth;
use App\Http\Controllers\pages\comissionamento\Comissionamento;
use App\Http\Controllers\pages\ranking\RankingVendas;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\pages\HomePage;
use App\Http\Controllers\pages\DashboardController;
use App\Http\Controllers\manager\Manager;
use App\Http\Controllers\pages\pabx\Pabx;
use App\Http\Controllers\pages\vendas\Vendas;
use App\Http\Controllers\pages\mailing\Mailing;
use App\Http\Controllers\pages\manager\Empresa;
use App\Http\Controllers\pages\manager\Usuarios;
use App\Http\Controllers\pages\comercial\Comercial;
use App\Http\Controllers\pages\comercial\ReciclagemLeads;
use App\Http\Controllers\authentications\LoginBasic;
use App\Http\Controllers\pages\backoffice\Backoffice;
use App\Http\Controllers\pages\backoffice\CredenciaisAcessoController;
use App\Http\Controllers\pages\backoffice\LiminarController;
use App\Http\Controllers\pages\backoffice\PainelProcessosController;
use App\Http\Controllers\pages\backoffice\PosVendaDemandas;
use App\Http\Controllers\pages\backoffice\ProcessosVendaController;

use App\Http\Controllers\pages\relatorios\Relatorios;
use App\Http\Controllers\pages\relatorios\RelatorioAproveitamento;
use App\Http\Controllers\pages\comercial\ReunioesComercial;
use App\Http\Controllers\pages\financeiro\Financeiro;
use App\Http\Controllers\pages\comercial\ConsultaController;
use App\Http\Controllers\pages\comercial\EnvioCotacaoController;
use App\Http\Controllers\pages\estudo\Estudo;
use App\Http\Controllers\pages\escola\EscolaController;
use App\Http\Controllers\pages\escola\EscolaAdminController;

//ROUTE
Route::get('/', [LoginBasic::class, 'index'])->name('login');


Route::post('/logout', [LoginBasic::class, 'logout'])->name('logout');
Route::post('autentication', [Auth::class, 'login'])->name('login.autentication');


Route::get('/visualizar-estudo/{uuid}', [Estudo::class, 'showStudy'])->name('estudo.show');

// Rotas públicas para TV Comercial
Route::get('/tv-comercial/painel', [\App\Http\Controllers\TvComercialController::class, 'painelTv'])->name('tv-comercial.painel');
Route::get('/tv-comercial/dados', [\App\Http\Controllers\TvComercialController::class, 'getDadosTv'])->name('tv-comercial.dados');
Route::get('/tv-comercial/ranking', [\App\Http\Controllers\TvComercialController::class, 'getRankingTv'])->name('tv-comercial.ranking');

Route::middleware(['auth'])->group(function () {

  /** MANAGER */
  Route::get('manager/changeCompany/{companyId}', [Manager::class, 'changeCompany'])->name('manager.changeCompany');
  Route::post('manager/switch-module', [Manager::class, 'switchModule'])->name('manager.switchModule');

  Route::get('/notificacoes/novas', function () {
    $notifications = auth()->user()->unreadNotifications
      ->filter(fn($n) => !isset($n->data['agendado_por']) || $n->data['agendado_por'] == auth()->user()->id);

    return response()->json($notifications->values());
  })->middleware('auth')->name('notificacoes.novas');


  /** PAGINA INICIAL */
  Route::get('dashboard', [HomePage::class, 'index'])->name('home.dashboard');
  Route::get('dashboard-vendedor', [DashboardController::class, 'index'])->name('dashboard.vendedor');
  Route::get('dashboard-vendedor/metrics', [DashboardController::class, 'getMetrics'])->name('dashboard.vendedor.metrics');
  Route::get('searchMetrics/{month}/{year}', [HomePage::class, 'searchMetrics'])->name('home.searchMetrics');

  /** CADASTRO DE EMPRESAS */
  Route::get('/empresas', [Empresa::class, 'index'])->name('empresa.empresa');
  Route::get('/empresas/getAllCompanies', [Empresa::class, 'getAllCompanies'])->name('empresa.getAllCompanies');
  Route::post('/empresas/createCompanies', [Empresa::class, 'createCompanies'])->name('empresa.createCompanies');

  /** TV COMERCIAL - GERENCIAMENTO DE METAS */
  Route::get('/tv-comercial/gerenciar', [\App\Http\Controllers\TvComercialController::class, 'gerenciar'])->name('tv-comercial.gerenciar');
  Route::get('/tv-comercial/listar-metas', [\App\Http\Controllers\TvComercialController::class, 'listarMetas'])->name('tv-comercial.listar-metas');
  Route::post('/tv-comercial/salvar-metas', [\App\Http\Controllers\TvComercialController::class, 'salvarMetas'])->name('tv-comercial.salvar-metas');
  Route::post('/tv-comercial/atualizar-cotacoes', [\App\Http\Controllers\TvComercialController::class, 'atualizarCotacoes'])->name('tv-comercial.atualizar-cotacoes');
  Route::post('/tv-comercial/atualizar-meta', [\App\Http\Controllers\TvComercialController::class, 'atualizarMeta'])->name('tv-comercial.atualizar-meta');
  Route::post('/tv-comercial/deletar-meta', [\App\Http\Controllers\TvComercialController::class, 'deletarMeta'])->name('tv-comercial.deletar-meta');
  Route::get('/tv-comercial/ranking-cotacoes', [\App\Http\Controllers\TvComercialController::class, 'rankingCotacoes'])->name('tv-comercial.ranking-cotacoes');

  /** CADASTRO DE USUARIOS */
  Route::get('/usuarios', [Usuarios::class, 'index'])->name('usuarios.index');
  Route::get('/usuarios/editar-usuario/{idUser}', [Usuarios::class, 'editUser'])->name('usuarios.editUser');
  Route::get('/usuarios/getUsers', [Usuarios::class, 'getUsers'])->name('usuarios.getUsers');
  Route::get('/usuarios/stats', [Usuarios::class, 'getStats'])->name('usuarios.getStats');
  Route::post('/usuarios/createUser', [Usuarios::class, 'createUser'])->name('usuarios.createUser');
  Route::post('/usuarios/editUser', [Usuarios::class, 'updateUser'])->name('usuarios.updateUser');
  Route::post('/usuarios/resetar-senha', [Usuarios::class, 'resetPassword'])->name('usuarios.resetPassword');
  Route::post('/usuarios/{id}/toggle-status', [Usuarios::class, 'toggleStatus'])->name('usuarios.toggleStatus');
  Route::post('/usuarios/{user}/contas/salvar', [Usuarios::class, 'save'])
    ->name('contasPagamento.save')
    ->middleware('auth');


  /** MAILING */
  Route::get('/mailing/importar', [Mailing::class, 'index'])->name('mailing.importMailing');
  Route::get('/mailing/visualizar-leads', [Mailing::class, 'viewLeads'])->name('mailing.viewLeads');
  Route::get('/mailing/visualizar-leads-legacy', [Mailing::class, 'viewLeadslegacy'])->name('mailing.viewLeadslegacy');
  Route::get('/mailing/getLeads', [Mailing::class, 'getLeads'])->name('mailing.getLeads');
  Route::get('/mailing/getLeadsLegacy/{idMailing}', [Mailing::class, 'getLeadsLegacy'])->name('mailing.getLeadsLegacy');
  Route::post('/mailing/importaMailing', [Mailing::class, 'importaMailing'])->name('mailing.uploadBase');
  Route::get('/mailing/excluir-lead/{id}', [Mailing::class, 'deleteMailing'])->name('mailing.deleteMailing');
  Route::get('/comercial/leads-ads', [Mailing::class, 'contactsAdvertisement'])->name('mailing.contactsAdvertisement');
  Route::get('/comercial/preditiva', action: [Mailing::class, 'preditiva'])->name('mailing.preditiva');
  Route::get('/comercial/preditiva/importar', [Mailing::class, 'indexImportarPreditiva'])->name('preditiva.importar');
  Route::post('/comercial/preditiva/importar', [Mailing::class, 'importarParaPreditiva'])->name('preditiva.upload');
  Route::get('/getPreditiva', [Mailing::class, 'getPreditiva'])->name('mailing.getPreditiva');
  Route::post('/comercial/preditiva/desativar/{id}', [Mailing::class, 'desativarLeadPreditiva'])->name('preditiva.desativar');
  Route::post('/comercial/preditiva/excluir/{id}', [Mailing::class, 'excluirLeadPreditiva'])->name('preditiva.excluir');
  Route::post('/comercial/preditiva/remover/{id}', [Mailing::class, 'removerDaPreditiva'])->name('preditiva.remover');
  Route::get('/comercial/preditiva/tabulacoes', [Mailing::class, 'getTabulacoesDistintas'])->name('preditiva.tabulacoes');
  Route::post('/comercial/preditiva/limpar-logs', [Mailing::class, 'limparLogsPreditiva'])->name('preditiva.limparLogs');
  Route::get('/comercial/preditiva/regras', [Mailing::class, 'regrasIndex'])->name('preditiva.regras.index');
  Route::post('/comercial/preditiva/regras', [Mailing::class, 'regrasStore'])->name('preditiva.regras.store');
  Route::put('/comercial/preditiva/regras/{id}', [Mailing::class, 'regrasUpdate'])->name('preditiva.regras.update');
  Route::delete('/comercial/preditiva/regras/{id}', [Mailing::class, 'regrasDestroy'])->name('preditiva.regras.destroy');
  Route::post('/comercial/preditiva/regras/{id}/toggle', [Mailing::class, 'regrasToggle'])->name('preditiva.regras.toggle');
  Route::post('/comercial/preditiva/regras/reordenar', [Mailing::class, 'regrasReordenar'])->name('preditiva.regras.reordenar');
  Route::get('/comercial/preditiva/regras/campos', [Mailing::class, 'getCamposDisponiveis'])->name('preditiva.regras.campos');
  Route::get('/comercial/preditiva/regras/valores/{campo}', [Mailing::class, 'getValoresUnicos'])->name('preditiva.regras.valores');
  Route::get('/comercial/preditiva/configuracoes', [Mailing::class, 'getConfiguracaoPreditiva'])->name('preditiva.configuracoes.get');
  Route::post('/comercial/preditiva/configuracoes', [Mailing::class, 'salvarConfiguracaoPreditiva'])->name('preditiva.configuracoes.save');
  Route::post('/comercial/preditiva/tabulacoes-hard', [Mailing::class, 'storeTabulacaoHard'])->name('preditiva.tabulacoes-hard.store');

  // Reciclagem de leads frios -> preditiva
  Route::get('/comercial/reciclagem-leads', [ReciclagemLeads::class, 'index'])->name('comercial.reciclagem.index');
  Route::get('/comercial/reciclagem-leads/elegiveis', [ReciclagemLeads::class, 'getElegiveis'])->name('comercial.reciclagem.elegiveis');
  Route::get('/comercial/reciclagem-leads/resumo', [ReciclagemLeads::class, 'resumo'])->name('comercial.reciclagem.resumo');
  Route::post('/comercial/reciclagem-leads/enviar', [ReciclagemLeads::class, 'enviar'])->name('comercial.reciclagem.enviar');
  Route::get('/comercial/reciclagem-leads/config', [ReciclagemLeads::class, 'getConfig'])->name('comercial.reciclagem.config.get');
  Route::post('/comercial/reciclagem-leads/config', [ReciclagemLeads::class, 'salvarConfig'])->name('comercial.reciclagem.config.save');
  Route::get('/comercial/reciclagem-leads/historico', [ReciclagemLeads::class, 'historicoEnvios'])->name('comercial.reciclagem.historico');
  Route::delete('/comercial/preditiva/tabulacoes-hard/{id}', [Mailing::class, 'destroyTabulacaoHard'])->name('preditiva.tabulacoes-hard.destroy');
  Route::get('/mailing/leads-descartados', [Mailing::class, 'leadDescartados'])->name('mailing.leadDescartados');
  Route::get('/mailing/get-leads-descartados', [Mailing::class, 'getLeadsDescartados'])->name('comercial.getLeadsDescartados');
  Route::get('/mailing/getComentariosLead/{id}', [Mailing::class, 'getComentariosLead'])->name('comercial.getComentariosLead');
  Route::post('/mailing/excluir-lead-descartado/{id}', [Mailing::class, 'deleteMailingLeadsDescarted'])->name('mailing.deleteMailingLeadsDescarted');
  Route::post('/mailing/send-descartado-preditiva', [Mailing::class, 'sendDiscardedLeadToPreditiva'])->name('mailing.sendDiscardedToPreditiva');
  Route::post('/mailing/send-multiple-descartados-preditiva', [Mailing::class, 'sendMultipleDiscardedLeadsToPreditiva'])->name('mailing.sendMultipleDiscardedToPreditiva');
  Route::get('/mailing/getAllLeadsServerSide', [Mailing::class, 'getAllLeadsServerSide'])->name('mailing.getAllLeadsServerSide');
  Route::get('/mailing/getLeadKPIs', [Mailing::class, 'getLeadKPIs'])->name('mailing.getLeadKPIs');
  Route::post('/mailing/reactivate-lead', [Mailing::class, 'reactivateLead'])->name('mailing.reactivateLead');
  Route::post('/mailing/bulk-reactivate-leads', [Mailing::class, 'bulkReactivateLeads'])->name('mailing.bulkReactivateLeads');
  Route::post('/mailing/bulk-delete-leads', [Mailing::class, 'bulkDeleteLeads'])->name('mailing.bulkDeleteLeads');
  Route::post('/mailing/bulk-discard-leads', [Mailing::class, 'bulkDiscardLeads'])->name('mailing.bulkDiscardLeads');
  Route::post('/mailing/discard-lead', [Mailing::class, 'discardLead'])->name('mailing.discardLead');

  /** COMERCIAL */
  Route::get('/comercial/kanban', [Comercial::class, 'index'])->name('comercial.kanban');
  Route::get('/comercial/getClientComercial', [Comercial::class, 'getClientComercial'])->name('comercial.getClientComercial');
  Route::post('/changeStatusLead/kanban/changeStatusLead', [Comercial::class, 'changeStatusLead'])->name('comercial.changeStatusLead');
  Route::post('/comercial/saveNoteMailing', [Comercial::class, 'saveNoteMailing'])->name('comercial.saveNoteMailing');
  Route::get('/comercial/getCommentsLead/{id_mailing}', [Comercial::class, 'getCommentsLead'])->name('comercial.getCommentsLead');
  Route::get('/comercial/abrir-cliente/{id_mailing}', [Comercial::class, 'openClient'])->name('comercial.openClient');
  Route::post('/comercial/updateClient', [Comercial::class, 'updateClient'])->name('comercial.updateClient');
  Route::post('/comercial/updateClientDependecies', [Comercial::class, 'updateClientDependecies'])->name('comercial.updateClientDependecies');
  Route::post('/comercial/saveComment', [Comercial::class, 'saveComment'])->name('comercial.saveComment');
  Route::post('/comercial/comentarios/{id}/fixar', [Comercial::class, 'togglePinComment'])->name('comercial.togglePinComment');
  Route::put('/comercial/comentarios/{id}', [Comercial::class, 'updateComment'])->name('comercial.updateComment');
  Route::post('/comercial/analisar-cliente-ia', [Comercial::class, 'analisarClienteComIA'])->name('comercial.analisarClienteIA');
  Route::post('/comercial/abrir-cliente/{id_mailing}/cotacoes', [Comercial::class, 'uploadCotacao'])->name('comercial.uploadCotacao');
  Route::delete('/comercial/abrir-cliente/{id_mailing}/cotacoes/{filename}', [Comercial::class, 'deleteCotacao'])->name('comercial.deleteCotacao');
  Route::get('/comercial/remarketing', [Comercial::class, 'remarketing'])->name('comercial.remarketing');
  Route::get('/comercial/getRemarketingLeads', [Comercial::class, 'getRemarketingLeads'])->name('comercial.getRemarketingLeads');
  Route::get('/comercial/abrir-remarketing/{idMailing}', [Comercial::class, 'openLeadRemarketing'])->name('comercial.openLeadRemarketing');
  Route::get('/comercial/criar-cliente', [Comercial::class, 'createClient'])->name('comercial.createClient');
  Route::post('/comercial/transferContact', [Comercial::class, 'transferContact'])->name('comercial.transferContact');
  Route::post('/comercial/transferContactInNulk', [Comercial::class, 'transferContactInNulk'])->name('comercial.transferContactInNulk');
  Route::get('/comercial/getCommentsLegacy/{cpf}', [Comercial::class, 'getCommentsLegacy'])->name('comercial.getCommentsLegacy');
  Route::post('/comercial/saveCommentsLegacy', [Comercial::class, 'saveCommentsLegacy'])->name('comercial.saveCommentsLegacy');
  Route::post('/comercial/criar-venda', [Comercial::class, 'createSale'])->name('comercial.createSale');
  Route::get('/comercial/cliente/{contato_id}/nova-proposta', [Comercial::class, 'novaProposta'])->name('comercial.novaProposta');
  Route::post('/comercial/createLead', [Comercial::class, 'createLead'])->name('comercial.createLead');
  Route::post('/comercial/sendRemaketing', [Comercial::class, 'sendRemaketing'])->name('comercial.sendRemaketing');
  Route::get('/comercial/marketing', [Comercial::class, 'indexMarketing'])->name('comercial.indexMarketing');
  Route::get('/comercial/getLeadsmarketing', [Comercial::class, 'getLeadsmarketing'])->name('comercial.getLeadsmarketing');
  Route::post('/comercial/sendLeadMarketing', [Comercial::class, 'sendLeadMarketing'])->name('comercial.sendLeadMarketing');
  Route::post('/comercial/sendLeadPredictive', [Comercial::class, 'sendLeadPredictive'])->name('comercial.sendLeadPredictive');
  Route::post('/comercial/sendMultipleLeadsPredictive', [Comercial::class, 'sendMultipleLeadsPredictive'])->name('comercial.sendMultipleLeadsPredictive');
  Route::post('/comercial/getClientesPreditiva', [Comercial::class, 'getClientesPreditiva'])->name('comercial.getClientesPreditiva');
  Route::post('/comercial/descartarClientePreditiva', [Comercial::class, 'descartarClientePreditiva'])->name('comercial.descartarClientePreditiva');
  Route::post('/comercial/converterClientePreditiva', [Comercial::class, 'converterClientePreditiva'])->name('comercial.converterClientePreditiva');
  Route::post('/comercial/descartar-cliente/{id}', [Comercial::class, 'descartarCliente'])->name('comercial.descartar');
  Route::post('/comercial/descartar-multiplos-leads', [Comercial::class, 'discardMultipleLeads'])->name('comercial.discardMultipleLeads');
  Route::get('/comercial/getPlansByOperator/{operadora_id}', [Comercial::class, 'getPlansByOperator'])->name('comercial.getPlansByOperator');
  Route::get('/comercial/demandas', [Comercial::class, 'demands'])->name('comercial.demands');
  Route::post('/comercial/deletar-dependente', [Comercial::class, 'deletarDependente'])->name('comercial.deletar.depedente');
  Route::get('/comercial/demandas/list', [Comercial::class, 'list'])->name('demandas.list');
  Route::post('/comercial/demandas', [Comercial::class, 'store'])->name('demandas.store');
  Route::put('/comercial/demandas/{id}', [Comercial::class, 'update'])->name('demandas.update');
  Route::patch('/comercial/demandas/{id}/status', [Comercial::class, 'updateStatus'])->name('demandas.status');
  Route::delete('/comercial/demandas/{id}', [Comercial::class, 'destroy'])->name('demandas.destroy');

  /** COMERCIAL - Demandas de Pós-venda (vendedor) */
  Route::get('/comercial/minhas-demandas', [Comercial::class, 'demandasVendedor'])->name('comercial.demandasVendedor');
  Route::get('/comercial/minhas-demandas/list', [Comercial::class, 'listMinhasDemandas'])->name('comercial.minhasDemandas.list');
  Route::get('/comercial/minhas-demandas/contratos', [Comercial::class, 'buscarContratosImplantados'])->name('comercial.minhasDemandas.contratos');
  Route::post('/comercial/minhas-demandas', [Comercial::class, 'storeDemandaVendedor'])->name('comercial.minhasDemandas.store');

  // Envio de cotação por e-mail (remetente = e-mail do próprio vendedor)
  Route::get('/comercial/enviar-cotacao', [EnvioCotacaoController::class, 'index'])->name('comercial.envioCotacao');
  Route::post('/comercial/enviar-cotacao', [EnvioCotacaoController::class, 'enviar'])->name('comercial.envioCotacao.enviar');

  Route::get('/comercial/calendario-reunioes', [ReunioesComercial::class, 'index'])->name('comercialReunioes.index');
  Route::get('/reunioes/data', [ReunioesComercial::class, 'getReunioes']);
  Route::get('/reunioes/stats', [ReunioesComercial::class, 'getStats']);
  Route::get('/reunioes/seller-contacts', [ReunioesComercial::class, 'getSellerContacts']);
  Route::post('/reunioes', [ReunioesComercial::class, 'store']);
  Route::put('/reunioes/{id}', [ReunioesComercial::class, 'update']);
  Route::delete('/reunioes/{id}', [ReunioesComercial::class, 'destroy']);
  Route::get('/available-slots/{managerId}/{date}', [ReunioesComercial::class, 'getAvailableSlots']);
  Route::get('/notificacoes/ler', function () {
    auth()->user()->unreadNotifications->markAsRead();
    return back();
  })->name('notificacoes.marcar-como-lidas');

  /** COMERCIAL- AGENDAMENTO */
  Route::post('/comercial/sendSchedule', [Comercial::class, 'sendSchedule'])->name('comercial.sendSchedule');
  Route::post('/comercial/voltar-fila', [Comercial::class, 'backQueue'])->name('comercial.backqueue');
  Route::get('/comercial/agendamentos', [Comercial::class, 'schedules'])->name('comercial.schedules');
  Route::get('/comercial/getSchedules', [Comercial::class, 'getSchedules'])->name('comercial.getSchedules');
  Route::get('/comercial/searchPendingAppointments', [Comercial::class, 'searchPendingAppointments'])->name('comercial.searchPendingAppointments');
  Route::get('/comercial/sugestao-contato', [Comercial::class, 'sugestaoContato'])->name('comercial.sugestaoContato');
  Route::get('/comercial/avisos-mascote', [Comercial::class, 'avisosMascote'])->name('comercial.avisosMascote');
  Route::post('/comercial/avisos-mascote/{id}/lido', [Comercial::class, 'marcarAvisoLido'])->name('comercial.marcarAvisoLido');

  /** BACKOFFICE */
  Route::get(uri: '/back-office/fila-contratos', action: [Backoffice::class, 'index'])->name(name: 'backoffice.index');
  Route::get(uri: '/back-office/lista-contratos', action: [Backoffice::class, 'listContract'])->name(name: 'backoffice.listContracts');
  Route::get(uri: '/back-office/abrir-contrato/{idContrato}', action: [Backoffice::class, 'openContract'])->name(name: 'backoffice.openContract');
  Route::get(uri: '/back-office/lista-vendas-filtro', action: [Backoffice::class, 'listSalesFilter'])->name('backoffice.listSalesFilter');
  Route::post(uri: '/back-office/atualizar-contrato', action: [Backoffice::class, 'updateSale'])->name('backoffice.updateSale');
  Route::post(uri: '/back-office/alterar-status-contrato', action: [Backoffice::class, 'alterStatusContract'])->name('backoffice.alterStatusContract');
  Route::post(uri: '/back-office/quick-status-change', action: [Backoffice::class, 'quickStatusChange'])->name('backoffice.quickStatusChange');
  Route::get(uri: '/back-office/comprovante/{id}', action: [Backoffice::class, 'downloadPaymentProof'])->name('backoffice.downloadPaymentProof');
  Route::get(uri: '/back-office/deletar-contrato/{id}', action: [Backoffice::class, 'deleteContract'])->name('backoffice.deleteContract');
  // Operadoras e Planos — tela única (as antigas cadastrar-planos/operadora redirecionam pra cá)
  Route::get(uri: '/back-office/cadastrar-planos', action: [Backoffice::class, 'planos'])->name('backoffice.planos');
  Route::get(uri: '/back-office/cadastrar-operadora', action: [Backoffice::class, 'operadoras'])->name('backoffice.operadoras');
  Route::get('/back-office/operadoras-planos', [Backoffice::class, 'operadorasPlanos'])->name('backoffice.operadorasPlanos');
  Route::get('/back-office/operadoras-planos/data', [Backoffice::class, 'getOperadorasComPlanos'])->name('backoffice.operadorasPlanos.data');
  Route::post(uri: '/back-office/createOperation', action: [Backoffice::class, 'createOperation'])->name('backoffice.createOperation');
  Route::post(uri: '/back-office/createPlan', action: [Backoffice::class, 'createPlan'])->name('backoffice.createPlan');
  Route::patch('/back-office/operadoras/{id}/status', [Backoffice::class, 'toggleOperadoraStatus'])->name('backoffice.operadoras.toggleStatus');
  Route::patch('/back-office/planos/{id}/status', [Backoffice::class, 'togglePlanoStatus'])->name('backoffice.planos.toggleStatus');
  Route::get(uri: '/back-office/getOperators', action: [Backoffice::class, 'getOperators'])->name('backoffice.getOperators');
  Route::get(uri: '/back-office/getPlans', action: [Backoffice::class, 'getPlans'])->name('backoffice.getPlans');
  Route::put('/backoffice/titulares/{id}', [Backoffice::class, 'updateTitular'])->name('backoffice.titulares.update');
  Route::delete('/backoffice/titulares/{id}', [Backoffice::class, 'destroyTitular'])->name('backoffice.titulares.destroy');
  Route::post('/backoffice/titulares', [Backoffice::class, 'storeTitular'])->name('backoffice.titulares.store');
  Route::post('/backoffice/titulares-pme', [Backoffice::class, 'storeTitularPME'])->name('backoffice.titulares.storePME');
  Route::put('/backoffice/titulares-pme/{id}', [Backoffice::class, 'updateTitularPME'])->name('backoffice.titulares.updatePME');
  Route::put('/backoffice/dependentes-pme/{id}', [Backoffice::class, 'updateDependentePME'])->name('backoffice.dependentes.updatePME');
  Route::post('/backoffice/dependentes-pme', [Backoffice::class, 'storeDependentePME'])->name('backoffice.dependentes.storePME');
  Route::delete('/backoffice/dependentes-pme/{id}', [Backoffice::class, 'destroyDependentePME'])->name('backoffice.dependentes.destroyPME');
  Route::post('/backoffice/portabilidades-pme', [Backoffice::class, 'storePortabilidadePME'])->name('backoffice.portabilidades.storePME');
  Route::put('/backoffice/portabilidades-pme/{id}', [Backoffice::class, 'updatePortabilidadePME'])->name('backoffice.portabilidades.updatePME');
  Route::delete('/backoffice/portabilidades-pme/{id}', [Backoffice::class, 'destroyPortabilidadePME'])->name('backoffice.portabilidades.destroyPME');
  Route::get('/back-office/verificar-recebiveis/{vendaId}', [Backoffice::class, 'verificarRecebiveis'])->name('backoffice.verificarRecebiveis');
  Route::post('/back-office/gerar-recebivel/{vendaId}', [Backoffice::class, 'gerarRecebivelContrato'])->name('backoffice.gerarRecebivelContrato');
  Route::get('/back-office/pipeline-data', [Backoffice::class, 'getPipelineData'])->name('backoffice.pipelineData');
  Route::get('/back-office/demandas-pendentes-kanban', [Backoffice::class, 'getDemandasPendentesKanban'])->name('backoffice.demandasPendentesKanban');
  Route::get('/back-office/contratos-por-status/{tabulacaoId}', [Backoffice::class, 'getContratosPorStatus'])->name('backoffice.contratosPorStatus');
  Route::get('/back-office/historico/{vendaId}', [Backoffice::class, 'getHistorico'])->name('backoffice.historico');
  Route::post('/back-office/assumir-contrato', [Backoffice::class, 'assumirContrato'])->name('backoffice.assumirContrato');
  Route::post('/back-office/reatribuir-contrato', [Backoffice::class, 'reatribuirContrato'])->name('backoffice.reatribuirContrato');

  // Acessos Empresa
  Route::get('/back-office/acessos-empresa/{vendaId}', [Backoffice::class, 'getAcessosEmpresa'])->name('backoffice.getAcessosEmpresa');
  Route::post('/back-office/acessos-empresa', [Backoffice::class, 'storeAcessoEmpresa'])->name('backoffice.storeAcessoEmpresa');
  Route::put('/back-office/acessos-empresa/{id}', [Backoffice::class, 'updateAcessoEmpresa'])->name('backoffice.updateAcessoEmpresa');
  Route::delete('/back-office/acessos-empresa/{id}', [Backoffice::class, 'deleteAcessoEmpresa'])->name('backoffice.deleteAcessoEmpresa');

  // Demandas de Contrato
  Route::get('/back-office/demandas-contrato/{vendaId}', [Backoffice::class, 'getDemandasContrato'])->name('backoffice.getDemandasContrato');
  Route::post('/back-office/demandas-contrato', [Backoffice::class, 'storeDemandaContrato'])->name('backoffice.storeDemandaContrato');
  Route::put('/back-office/demandas-contrato/{id}', [Backoffice::class, 'updateDemandaContrato'])->name('backoffice.updateDemandaContrato');
  Route::patch('/back-office/demandas-contrato/{id}/toggle', [Backoffice::class, 'toggleStatusDemandaContrato'])->name('backoffice.toggleStatusDemandaContrato');
  Route::delete('/back-office/demandas-contrato/{id}', [Backoffice::class, 'destroyDemandaContrato'])->name('backoffice.destroyDemandaContrato');

  // Acompanhamento de Processos da Venda (tela única com abas no detalhe do contrato)
  Route::get('/back-office/processos/buscar', [ProcessosVendaController::class, 'buscar'])->name('backoffice.processos.buscar');
  Route::get('/back-office/processos/{vendaId}', [ProcessosVendaController::class, 'dados'])->whereNumber('vendaId')->name('backoffice.processos.dados');
  Route::patch('/back-office/processos/cancelamento/{id}', [ProcessosVendaController::class, 'atualizarCancelamento'])->name('backoffice.processos.cancelamento');
  Route::patch('/back-office/processos/portabilidade/{id}/fase', [ProcessosVendaController::class, 'fasePortabilidade'])->name('backoffice.processos.fasePortabilidade');
  Route::post('/back-office/processos/{vendaId}/emails', [ProcessosVendaController::class, 'storeEmailCriado'])->name('backoffice.processos.emails.store');
  Route::patch('/back-office/processos/emails/{id}', [ProcessosVendaController::class, 'updateEmailCriado'])->name('backoffice.processos.emails.update');
  Route::delete('/back-office/processos/emails/{id}', [ProcessosVendaController::class, 'destroyEmailCriado'])->name('backoffice.processos.emails.destroy');

  // Painel operacional de processos (visão do gestor, cross-contrato)
  Route::get('/back-office/painel-processos', [PainelProcessosController::class, 'index'])->name('backoffice.painelProcessos');
  Route::get('/back-office/painel-processos/data', [PainelProcessosController::class, 'data'])->name('backoffice.painelProcessos.data');
  Route::post('/back-office/painel-processos/atribuir', [PainelProcessosController::class, 'atribuir'])->name('backoffice.painelProcessos.atribuir');
  Route::post('/back-office/painel-processos/concluir', [PainelProcessosController::class, 'concluir'])->name('backoffice.painelProcessos.concluir');
  Route::post('/back-office/painel-processos/portabilidade/fase', [PainelProcessosController::class, 'fasePortabilidade'])->name('backoffice.painelProcessos.fasePortabilidade');

  // Demandas de Pós-Venda (workspace de check-list por contrato)
  Route::get('/back-office/pos-venda-demandas', [PosVendaDemandas::class, 'index'])->name('backoffice.posVendaDemandas');
  Route::get('/back-office/pos-venda-demandas/data', [PosVendaDemandas::class, 'data'])->name('backoffice.posVendaDemandas.data');
  Route::get('/back-office/pos-venda-demandas/templates', [PosVendaDemandas::class, 'getTemplates'])->name('backoffice.posVendaDemandas.templates');
  Route::get('/back-office/pos-venda-demandas/buscar-contratos', [PosVendaDemandas::class, 'buscarContratos'])->name('backoffice.posVendaDemandas.buscarContratos');
  Route::get('/back-office/pos-venda-demandas/metricas', [PosVendaDemandas::class, 'metricas'])->name('backoffice.posVendaDemandas.metricas');
  Route::post('/back-office/pos-venda-demandas/{vendaId}/concluir-todas', [PosVendaDemandas::class, 'concluirTodas'])->name('backoffice.posVendaDemandas.concluirTodas');

  // Pós-Venda
  Route::get('/back-office/pos-venda', [Backoffice::class, 'posVenda'])->name('backoffice.posVenda');
  Route::get('/back-office/pos-venda/data', [Backoffice::class, 'getPosVendaData'])->name('backoffice.getPosVendaData');

  // Anotações Pós-Venda
  Route::get('/back-office/pos-venda/anotacoes/{vendaId}', [Backoffice::class, 'getAnotacoesPosVenda'])->name('backoffice.getAnotacoesPosVenda');
  Route::post('/back-office/pos-venda/anotacoes', [Backoffice::class, 'storeAnotacaoPosVenda'])->name('backoffice.storeAnotacaoPosVenda');
  Route::post('/back-office/pos-venda/data-implantacao', [Backoffice::class, 'updateDataImplantacao'])->name('backoffice.updateDataImplantacao');
  Route::post('/back-office/pos-venda/boas-vindas', [Backoffice::class, 'marcarBoasVindas'])->name('backoffice.marcarBoasVindas');
  Route::post('/back-office/pos-venda/boas-vindas/preview-email', [Backoffice::class, 'previewEmailBoasVindas'])->name('backoffice.previewEmailBoasVindas');
  Route::get('/back-office/pos-venda/beneficiarios/{vendaId}', [Backoffice::class, 'getBeneficiariosParaBoasVindas'])->name('backoffice.getBeneficiariosParaBoasVindas');
  Route::get('/back-office/configuracoes/whatsapp-token', [Backoffice::class, 'getWhatsappConfig'])->name('backoffice.getWhatsappConfig');
  Route::post('/back-office/configuracoes/whatsapp-token', [Backoffice::class, 'updateWhatsappToken'])->name('backoffice.updateWhatsappToken');

  // Cancelamento via Liminar
  Route::get('/back-office/liminar', [LiminarController::class, 'index'])->name('backoffice.liminar.index');
  Route::get('/back-office/liminar/dados', [LiminarController::class, 'dados'])->name('backoffice.liminar.dados');
  Route::post('/back-office/liminar', [LiminarController::class, 'store'])->name('backoffice.liminar.store');
  Route::get('/back-office/liminar/{id}', [LiminarController::class, 'show'])->name('backoffice.liminar.show');
  Route::put('/back-office/liminar/{id}', [LiminarController::class, 'update'])->name('backoffice.liminar.update');
  Route::delete('/back-office/liminar/{id}', [LiminarController::class, 'destroy'])->name('backoffice.liminar.destroy');
  Route::post('/back-office/liminar/{id}/mover', [LiminarController::class, 'mover'])->name('backoffice.liminar.mover');
  Route::get('/back-office/liminar-buscar-contratos', [LiminarController::class, 'buscarContratos'])->name('backoffice.liminar.buscarContratos');
  Route::get('/back-office/liminar-buscar-concluidas', [LiminarController::class, 'buscarConcluidas'])->name('backoffice.liminar.buscarConcluidas');
  Route::post('/back-office/liminar/{id}/documentos', [LiminarController::class, 'uploadDocumento'])->name('backoffice.liminar.uploadDocumento');
  Route::delete('/back-office/liminar/{id}/documentos/{docId}', [LiminarController::class, 'destroyDocumento'])->name('backoffice.liminar.destroyDocumento');
  Route::get('/back-office/liminar/{id}/documentos/{docId}/download', [LiminarController::class, 'downloadDocumento'])->name('backoffice.liminar.downloadDocumento');
  Route::get('/back-office/pos-venda/liminar/beneficiarios/{vendaId}', [LiminarController::class, 'getBeneficiarios'])->name('backoffice.liminar.getBeneficiarios');

  // Credenciais de Acesso (cofre de logins das empresas nas operadoras)
  Route::get('/back-office/credenciais', [CredenciaisAcessoController::class, 'index'])->name('backoffice.credenciais.index');
  Route::get('/back-office/credenciais/data', [CredenciaisAcessoController::class, 'getData'])->name('backoffice.credenciais.data');
  Route::post('/back-office/credenciais/importar/preview', [CredenciaisAcessoController::class, 'importPreview'])->name('backoffice.credenciais.import.preview');
  Route::post('/back-office/credenciais/importar', [CredenciaisAcessoController::class, 'import'])->name('backoffice.credenciais.import');
  Route::post('/back-office/credenciais', [CredenciaisAcessoController::class, 'store'])->name('backoffice.credenciais.store');
  Route::get('/back-office/credenciais/{id}', [CredenciaisAcessoController::class, 'show'])->whereNumber('id')->name('backoffice.credenciais.show');
  Route::put('/back-office/credenciais/{id}', [CredenciaisAcessoController::class, 'update'])->whereNumber('id')->name('backoffice.credenciais.update');
  Route::delete('/back-office/credenciais/{id}', [CredenciaisAcessoController::class, 'destroy'])->whereNumber('id')->name('backoffice.credenciais.destroy');
  Route::get('/back-office/credenciais/{id}/historico', [CredenciaisAcessoController::class, 'historico'])->whereNumber('id')->name('backoffice.credenciais.historico');

  // Carteira de Clientes — contém valores/faturamento; restrito a ADMINISTRATIVO e DEVELOPER.
  Route::middleware('role:' . \App\Enums\UserRole::ADMINISTRATIVO . ',' . \App\Enums\UserRole::DEVELOPER)->group(function () {
    Route::get('/back-office/carteira-clientes', [Backoffice::class, 'carteiraClientes'])->name('backoffice.carteiraClientes');
    Route::get('/back-office/carteira-clientes/data', [Backoffice::class, 'getCarteiraClientesData'])->name('backoffice.getCarteiraClientesData');
    Route::get('/back-office/carteira-clientes/detalhe/{cnpj}', [Backoffice::class, 'getDetalheClienteCarteira'])->name('backoffice.getDetalheClienteCarteira');
  });

  // FAQs (Back-office)
  Route::get('/back-office/faqs', [Backoffice::class, 'faqs'])->name('backoffice.faqs');
  Route::get('/back-office/getFaqs', [Backoffice::class, 'getFaqs'])->name('backoffice.getFaqs');
  Route::post('/back-office/createFaq', [Backoffice::class, 'createFaq'])->name('backoffice.createFaq');
  Route::post('/back-office/updateFaq/{id}', [Backoffice::class, 'updateFaq'])->name('backoffice.updateFaq');
  Route::delete('/back-office/deleteFaq/{id}', [Backoffice::class, 'deleteFaq'])->name('backoffice.deleteFaq');

  // FAQs (Vendedor - visualização)
  Route::get('/comercial/faqs', [Comercial::class, 'faqsVendedor'])->name('comercial.faqs');

  /** VENDAS */
  Route::get('/vendas/lista-vendas', [Vendas::class, 'index'])->name('sale.listSale');
  Route::get('/vendas/lista-vendas-mes', [Vendas::class, 'salesOfTheMonth'])->name('sale.salesOfTheMonth');
  Route::get('/vendas/detalhes/{id}', [Vendas::class, 'detalhesVenda'])->name('sale.details');
  Route::get('/vendas/analitico', [Vendas::class, 'analyticalSales'])->name('sale.analyticalSales');
  Route::get('/vendas/vendasAnalitico', [Vendas::class, 'getSalesAnalytical'])->name('sale.getSalesAnalytical');
  Route::get('/vendas/filtro-vendas-mes/{nome_corretor?}', [Vendas::class, 'monthlySalesFilter'])->name('sale.monthlySalesFilter');
  Route::get('/vendas/getResultsBroker', [Vendas::class, 'getResultsBroker'])->name('sale.getResultsBroker');

  Route::get('/vendas/dados', [Vendas::class, 'dados'])->name('sale.dados');
  Route::get('/vendas/listar', [Vendas::class, 'listarVendas'])->name('sale.listarVendas');
  Route::get('/vendas/exportar', [Vendas::class, 'exportar'])->name('sale.exportar');
  Route::get('/vendas/boletos/{id}', [Vendas::class, 'downloadBoleto'])->name('sale.downloadBoleto');

  // Estorno — vendedor visualiza, corrige (todos os campos) e reenvia para o backoffice
  Route::get('/vendas/meus-estornos', [Vendas::class, 'meusEstornos'])->name('sale.meusEstornos');
  Route::get('/vendas/meus-estornos/dados', [Vendas::class, 'meusEstornosDados'])->name('sale.meusEstornosDados');
  Route::get('/vendas/estorno/{id}/editar', [Vendas::class, 'editEstorno'])->name('sale.editEstorno');
  Route::post('/vendas/estorno/{id}/reenviar', [Vendas::class, 'reenviarEstorno'])->name('sale.reenviarEstorno');

  /** PABX */
  Route::get('/pabx/cadastro-ramais', [Pabx::class, 'index'])->name('index.createRamal');
  Route::get('/pabx/getRamais', [Pabx::class, 'getRamais'])->name('pabx.getRamais');
  Route::post('/pabx/createramal', [Pabx::class, 'createramal'])->name('pabx.createramal');
  Route::post('/pabx/clickToCall', [Pabx::class, 'clickToCall'])->name('pabx.clickToCall');

  /** RELATORIOS */
  Route::get('/relatorios/distribuicao-leads', [Relatorios::class, 'distribuicaoLeads'])->name('relatorios.distribuicaoLeads');
  Route::get('/relatorios/distribuicao-leads/dados', [Relatorios::class, 'distribuicaoLeadsData'])->name('relatorios.distribuicaoLeads.dados');
  Route::get('/relatorios/ligacoes', [Relatorios::class, 'index'])->name('pabx.getLigacoess');
  Route::get('/relatorios/getList/{id_user}/{data_inicial}/{data_final}', [Relatorios::class, 'getLigacoes'])->name('pabx.getLigacoes');
  Route::get('/relatorios/preditiva', [Relatorios::class, 'predictiveReport'])->name('relatorios.preditiva.predictiveReport');
  Route::post('/relatorios/buscar', [Relatorios::class, 'get'])->name('relatorios.preditiva.buscar');
  Route::get('/relatorios/atividade', [Relatorios::class, 'performanceVendedor'])->name('relatorios.performanceVendedor');
  Route::get('/relatorios/atividade/dados', [Relatorios::class, 'performanceVendedorData'])->name('relatorios.performanceVendedor.dados');
  Route::get('/relatorios/implantacoes', [Relatorios::class, 'implantacoes'])->name('relatorios.implantacoes');
  Route::get('/relatorios/implantacoes/dados', [Relatorios::class, 'implantacoesData'])->name('relatorios.implantacoes.dados');
  Route::get('/relatorios/implantacoes/listar', [Relatorios::class, 'implantacoesList'])->name('relatorios.implantacoes.listar');
  Route::get('/relatorios/desempenho-anual', [Relatorios::class, 'desempenhoAnual'])->name('relatorios.desempenhoAnual');
  Route::get('/relatorios/desempenho-anual/dados', [Relatorios::class, 'desempenhoAnualData'])->name('relatorios.desempenhoAnual.dados');

  // Relatório de Aproveitamento com IA
  Route::get('/relatorios/aproveitamento', [RelatorioAproveitamento::class, 'index'])->name('relatorios.aproveitamento');
  Route::get('/relatorios/aproveitamento/dados', [RelatorioAproveitamento::class, 'getDados'])->name('relatorios.aproveitamento.dados');
  Route::post('/relatorios/aproveitamento/analise', [RelatorioAproveitamento::class, 'gerarAnalise'])->name('relatorios.aproveitamento.analise');

  /** RANKING DE VENDAS */
  Route::get('/ranking', [RankingVendas::class, 'index'])->name('ranking.index');
  Route::get('/ranking/configuracao/edit/{id}', [RankingVendas::class, 'edit'])->name('ranking.edit');
  Route::get('/ranking/configuracao', [RankingVendas::class, 'config'])->name('ranking.config');
  // Ranking de vendas desativado temporariamente
  // Route::get('/ranking-vendas', [RankingVendas::class, 'rankingVendas'])->name('ranking.rankingVendas');
  // Route::get('/rankingVendasData', [RankingVendas::class, 'rankingVendasData'])->name('ranking.rankingVendasData');
  // Route::get('/vendas/valores-mensais', [RankingVendas::class, 'valoresMensais'])->name('ranking.valoresMensais');

  /** COMISSIONAMENTO */
  Route::get('/comissionamento', [Comissionamento::class, 'index'])->name('comissionamento.index');
  Route::get('/comissionamento/getCommissioning', [Comissionamento::class, 'getCommissioning'])->name('comissionamento.getCommissioning');
  Route::post('/comissionamento', [Comissionamento::class, 'store'])->name('comissionamento.store');
  Route::put('/comissionamento/{id}', [Comissionamento::class, 'update'])->name('comissionamento.update');
  Route::delete('/comissionamento/{id}', [Comissionamento::class, 'destroy'])->name('comissionamento.destroy');
  Route::get('/comissionamento/faturar', [Comissionamento::class, 'invoiceCommission'])->name('comissionamento.invoiceCommission');
  Route::get('/comissionamento/faturamento', [Comissionamento::class, 'getFaturamentoComissionamento'])->name('comissionamento.faturamento');
  Route::get('/comissionamento/vendedores', [Comissionamento::class, 'getVendedores'])->name('comissionamento.vendedores');

  Route::get('/comissionamento-vendedor', [Comissionamento::class, 'sellerCommission'])->name('comissionamento.vendedor');
  Route::get('/comissionamento/getCommissioningBySeller', [Comissionamento::class, 'getCommissioningBySeller'])->name('comissionamento.getCommissioningBySeller');
  Route::get('/comissionamento/pdf', [Comissionamento::class, 'getCommissioningBySellerPdf'])->name('comissionamento.vendedor.pdf');
  Route::post('/comissionamento/pagar', [Comissionamento::class, 'pagarVendedor'])->name('comissionamento.pagar');

  Route::get('/comissionamento/pagamento/{pagamento}/pdf', [Comissionamento::class, 'pdfPagamento'])->name('comissionamento.pagamento.pdf');

  Route::get('/comissionamento/pagamentos', [Comissionamento::class, 'pagamentosIndex'])->name('comissionamento.pagamentos');
  Route::get('/comissionamento/pagamentos/data', [Comissionamento::class, 'pagamentosData'])->name('comissionamento.pagamentos.data');
  Route::post('/comissionamento/pagamentos/{id}/estornar', [Comissionamento::class, 'pagamentosEstornar'])->name('comissionamento.pagamentos.estornar');
  Route::post('/comissionamento/lancamentos',[Comissionamento::class, 'storeLancamentoDebitoCredito'])->name('comissionamento.ajuste.store');
  Route::delete('/comissionamento/lancamentos/{id}',[Comissionamento::class, 'deleteLancamentoDebitoCredito'])->name('comissionamento.ajuste.delete');

  // contas do usuário
  Route::get('/contas-pagamento', [Comissionamento::class, 'byUser'])->name('contas.byUser');
  // pagar
  Route::post('/comissao-pagamentos/{id}/pagar', [Comissionamento::class, 'pagar'])->name('comissao.pagar');

  /** ESTUDO */
  Route::get('/estudo-lista', [Estudo::class, 'index'])->name('estudo.index');
  Route::get('/estudo-criar', [Estudo::class, 'create'])->name('estudo.create');
  Route::get('/editar-estudo/{idEstudo}', [Estudo::class, 'edit'])->name('estudo.edit');
  Route::get('/estudos/{id}', [Estudo::class, 'show']);      
  Route::put('/estudos/{id}', [Estudo::class, 'update']);     
  Route::get('/estudo/getListStudies', [Estudo::class, 'getListStudies'])->name('estudo.getListStudies');

  Route::get('/planos/{operadoraId}', [Estudo::class, 'getByOperadora']);
  Route::post('/estudos', [Estudo::class, 'store'])->name('comissionamento.store');
  Route::delete('/estudo-delete/{id}', [Estudo::class, 'delete'])->name('comissionamento.delete');


  /**FINANCEIRO */
  Route::get('/financeiro/regras-recebimentos', [Financeiro::class, 'regrasRecebimentos'])
      ->name('financeiro.regras-recebimentos');

  // API das regras
  Route::prefix('financeiro/regras')->group(function () {
      Route::get('/', [Financeiro::class, 'regrasIndex'])->name('financeiro.regras.index');
      Route::post('/', [Financeiro::class, 'regrasStore'])->name('financeiro.regras.store');
      Route::put('/{id}', [Financeiro::class, 'regrasUpdate'])->name('financeiro.regras.update');
      Route::delete('/{id}', [Financeiro::class, 'regrasDestroy'])->name('financeiro.regras.destroy');

      // Parcelas
      Route::get('/{ruleId}/parcelas', [Financeiro::class, 'parcelasIndex'])->name('financeiro.regras.parcelas.index');
      Route::post('/parcelas', [Financeiro::class, 'parcelasStore'])->name('financeiro.regras.parcelas.store');
      Route::put('/parcelas/{id}', [Financeiro::class, 'parcelasUpdate'])->name('financeiro.regras.parcelas.update');
      Route::delete('/parcelas/{id}', [Financeiro::class, 'parcelasDestroy'])->name('financeiro.regras.parcelas.destroy');
  });

  Route::prefix('financeiro/recebiveis')->group(function () {
      // 📑 Listagem geral de recebíveis
      Route::get('/', [Financeiro::class, 'indexRecebiveis'])
          ->name('financeiro.recebiveis.index');

      // 📄 Resumo de contrato para atualização via AJAX
      Route::get('/contrato/{vendaId}/resumo', [Financeiro::class, 'getContratoResumo'])
          ->name('financeiro.recebiveis.contratoResumo');

      // 🔍 Filtro por contrato/venda específica
      Route::get('/contrato/{vendaId}', [Financeiro::class, 'showContratoRecebiveis'])
          ->name('financeiro.recebiveis.contrato');

    // Listar parcelas de uma venda específica (usado no modal)
    Route::get('/{venda}/parcelas', [Financeiro::class, 'getParcelas'])
        ->name('financeiro.recebiveis.parcelas');

    // Marcar uma parcela como paga
    Route::post('/parcelas/{id}/pagar', [Financeiro::class, 'pagarParcela'])
        ->name('financeiro.recebiveis.pagar');

    // Atualizar data de recebimento de uma parcela
    Route::put('/parcelas/{id}/data-recebimento', [Financeiro::class, 'atualizarDataRecebimento'])
        ->name('financeiro.recebiveis.atualizarData');

    // Recalcular valores com nova regra
    Route::post('/{vendaId}/recalcular', [Financeiro::class, 'recalcularRecebiveis'])
        ->name('financeiro.recebiveis.recalcular');

    // Excluir uma parcela específica
    Route::delete('/parcelas/{id}', [Financeiro::class, 'excluirParcela'])
        ->name('financeiro.recebiveis.excluirParcela');

    // Excluir múltiplas parcelas de uma vez
    Route::post('/parcelas/excluir-multiplas', [Financeiro::class, 'excluirMultiplasParcelas'])
        ->name('financeiro.recebiveis.excluirMultiplas');

    // Editar múltiplas parcelas de uma vez
    Route::put('/parcelas/editar-multiplas', [Financeiro::class, 'editarMultiplasParcelas'])
        ->name('financeiro.recebiveis.editarMultiplas');

    // Dar baixa em múltiplas parcelas de uma vez
    Route::post('/parcelas/dar-baixa-multiplas', [Financeiro::class, 'darBaixaMultiplasParcelas'])
        ->name('financeiro.recebiveis.darBaixaMultiplas');

    // Gerar recebíveis manualmente
    Route::post('/{vendaId}/gerar-manual', [Financeiro::class, 'gerarRecebiveisManuais'])
        ->name('financeiro.recebiveis.gerarManual');

    // Excluir todos os recebíveis de uma venda
    Route::delete('/{vendaId}/todos', [Financeiro::class, 'excluirTodosRecebiveis'])
        ->name('financeiro.recebiveis.excluirTodos');

    // Excluir lançamento completo (fixas + vitalícios)
    Route::delete('/{vendaId}/completo', [Financeiro::class, 'excluirLancamentoCompleto'])
        ->name('financeiro.recebiveis.excluirCompleto');

    // 📋 Vitalícios
    Route::get('/vitalicios', [Financeiro::class, 'indexVitalicios'])
        ->name('financeiro.vitalicios.index');

    Route::get('/vitalicios/{venda}/parcelas', [Financeiro::class, 'getParcelasVitalicias'])
        ->name('financeiro.vitalicios.parcelas');

    Route::delete('/vitalicios/{vendaId}/todos', [Financeiro::class, 'excluirTodosVitalicios'])
        ->name('financeiro.vitalicios.excluirTodos');

    Route::patch('/vitalicios/{vendaId}/toggle-ativo', [Financeiro::class, 'toggleVitalicioAtivo'])
        ->name('financeiro.vitalicios.toggleAtivo');

    // 📊 Relatório financeiro
    Route::get('/relatorio-financeiro', [Financeiro::class, 'relatorioFinanceiro'])
        ->name('financeiro.relatorio');

    Route::post('/relatorio-financeiro/fetch', [Financeiro::class, 'relatorioFinanceiroFetch'])
        ->name('financeiro.relatorio.fetch');
  });

  /** ESCOLA LK BROKERS */
  Route::prefix('escola')->name('escola.')->group(function () {
    // Área do aluno (todos os roles autenticados)
    Route::get('/', [EscolaController::class, 'index'])->name('index');
    Route::get('/modulos/{modulo}', [EscolaController::class, 'show'])->whereNumber('modulo')->name('modulos.show');
    Route::get('/aulas/{aula}', [EscolaController::class, 'assistir'])->whereNumber('aula')->name('aulas.assistir');
    Route::post('/aulas/{aula}/progresso', [EscolaController::class, 'salvarProgresso'])->whereNumber('aula')->name('aulas.progresso');
    Route::get('/materiais/{material}/download', [EscolaController::class, 'downloadMaterial'])->whereNumber('material')->name('materiais.download');

    // Área administrativa (ADMINISTRATIVO, DEVELOPER, SUPERVISOR — checado no controller)
    Route::prefix('gestao')->name('gestao.')->group(function () {
      Route::get('/', [EscolaAdminController::class, 'index'])->name('index');

      Route::post('/modulos', [EscolaAdminController::class, 'storeModulo'])->name('modulos.store');
      Route::post('/modulos/reordenar', [EscolaAdminController::class, 'reordenarModulos'])->name('modulos.reordenar');
      Route::put('/modulos/{modulo}', [EscolaAdminController::class, 'updateModulo'])->whereNumber('modulo')->name('modulos.update');
      Route::delete('/modulos/{modulo}', [EscolaAdminController::class, 'destroyModulo'])->whereNumber('modulo')->name('modulos.destroy');

      Route::get('/modulos/{modulo}/aulas', [EscolaAdminController::class, 'aulas'])->whereNumber('modulo')->name('aulas.index');
      Route::post('/modulos/{modulo}/aulas', [EscolaAdminController::class, 'storeAula'])->whereNumber('modulo')->name('aulas.store');
      Route::post('/aulas/reordenar', [EscolaAdminController::class, 'reordenarAulas'])->name('aulas.reordenar');
      Route::put('/aulas/{aula}', [EscolaAdminController::class, 'updateAula'])->whereNumber('aula')->name('aulas.update');
      Route::delete('/aulas/{aula}', [EscolaAdminController::class, 'destroyAula'])->whereNumber('aula')->name('aulas.destroy');

      Route::post('/upload/presign', [EscolaAdminController::class, 'presignUpload'])->name('upload.presign');
      Route::post('/aulas/{aula}/video/confirmar', [EscolaAdminController::class, 'confirmarVideo'])->whereNumber('aula')->name('aulas.video.confirmar');

      Route::post('/aulas/{aula}/materiais', [EscolaAdminController::class, 'storeMaterial'])->whereNumber('aula')->name('materiais.store');
      Route::delete('/materiais/{material}', [EscolaAdminController::class, 'destroyMaterial'])->whereNumber('material')->name('materiais.destroy');

      Route::get('/relatorio', [EscolaAdminController::class, 'relatorio'])->name('relatorio');
      Route::get('/relatorio/data', [EscolaAdminController::class, 'relatorioData'])->name('relatorio.data');

      Route::get('/acessos', [EscolaAdminController::class, 'acessos'])->name('acessos');
      Route::post('/acessos/{usuario}/toggle', [EscolaAdminController::class, 'toggleAcesso'])->whereNumber('usuario')->name('acessos.toggle');
    });
  });
});


Route::post('/consulta/pessoa', [ConsultaController::class, 'consultarPessoa'])->name('consulta.pessoa');
Route::post('/consulta/empresa', [ConsultaController::class, 'consultarEmpresa'])->name('consulta.empresa');
Route::post('/consulta/telefone', [ConsultaController::class, 'consultarTelefone'])->name('consulta.telefone');
Route::post('/consulta/email', [ConsultaController::class, 'consultarEmail'])->name('consulta.email');
Route::post('/consulta/nome-endereco', [ConsultaController::class, 'consultarNomeEndereco'])->name('consulta.nome-endereco');

/** ============================================================
 *  MÓDULO LK BENEFÍCIOS
 *  Seguro de Vida, Odontológico, Previdência, Patrimoniais
 *  ============================================================ */
Route::middleware(['auth', \App\Http\Middleware\SetBeneficiosMode::class])
    ->prefix('lk-beneficios')->name('lk-beneficios.')->group(function () {
    Route::get('/', [\App\Http\Controllers\pages\lk_beneficios\DashboardController::class, 'index'])
        ->name('dashboard');
    Route::get('/dashboard/metricas', [\App\Http\Controllers\pages\lk_beneficios\DashboardController::class, 'metricasJson'])
        ->name('dashboard.metricas');

    Route::get('/contratos', [\App\Http\Controllers\pages\lk_beneficios\ContratoController::class, 'index'])
        ->name('contratos.index');
    Route::get('/contratos/datatable', [\App\Http\Controllers\pages\lk_beneficios\ContratoController::class, 'datatable'])
        ->name('contratos.datatable');
    Route::get('/contratos/{id}', [\App\Http\Controllers\pages\lk_beneficios\ContratoController::class, 'show'])
        ->whereNumber('id')->name('contratos.show');
    Route::post('/contratos', [\App\Http\Controllers\pages\lk_beneficios\ContratoController::class, 'store'])
        ->name('contratos.store');
    Route::put('/contratos/{id}', [\App\Http\Controllers\pages\lk_beneficios\ContratoController::class, 'update'])
        ->whereNumber('id')->name('contratos.update');
    Route::delete('/contratos/{id}', [\App\Http\Controllers\pages\lk_beneficios\ContratoController::class, 'destroy'])
        ->whereNumber('id')->name('contratos.destroy');

    Route::get('/contratos/{contratoId}/beneficiarios', [\App\Http\Controllers\pages\lk_beneficios\BeneficiarioController::class, 'index'])
        ->whereNumber('contratoId')->name('beneficiarios.index');
    Route::post('/contratos/{contratoId}/beneficiarios', [\App\Http\Controllers\pages\lk_beneficios\BeneficiarioController::class, 'store'])
        ->whereNumber('contratoId')->name('beneficiarios.store');
    Route::delete('/contratos/{contratoId}/beneficiarios/{id}', [\App\Http\Controllers\pages\lk_beneficios\BeneficiarioController::class, 'destroy'])
        ->whereNumber('contratoId')->whereNumber('id')->name('beneficiarios.destroy');

    Route::post('/consulta-lemit/cpf', [\App\Http\Controllers\pages\lk_beneficios\ConsultaLemitController::class, 'cpf'])
        ->name('lemit.cpf');
    Route::post('/consulta-lemit/cnpj', [\App\Http\Controllers\pages\lk_beneficios\ConsultaLemitController::class, 'cnpj'])
        ->name('lemit.cnpj');

    // Kanban de leads
    Route::get('/leads', [\App\Http\Controllers\pages\lk_beneficios\KanbanController::class, 'index'])
        ->name('leads.kanban');
    Route::get('/leads/dados', [\App\Http\Controllers\pages\lk_beneficios\KanbanController::class, 'dados'])
        ->name('leads.dados');
    Route::post('/leads/mover', [\App\Http\Controllers\pages\lk_beneficios\KanbanController::class, 'mover'])
        ->name('leads.mover');

    Route::get('/leads/novo', [\App\Http\Controllers\pages\lk_beneficios\LeadController::class, 'novo'])
        ->name('leads.novo');
    Route::post('/leads', [\App\Http\Controllers\pages\lk_beneficios\LeadController::class, 'store'])
        ->name('leads.store');
    Route::get('/leads/{id}', [\App\Http\Controllers\pages\lk_beneficios\LeadController::class, 'show'])
        ->whereNumber('id')->name('leads.show');
    Route::delete('/leads/{id}', [\App\Http\Controllers\pages\lk_beneficios\LeadController::class, 'destroy'])
        ->whereNumber('id')->name('leads.destroy');

    Route::post('/leads/{id}/comentarios', [\App\Http\Controllers\pages\lk_beneficios\LeadController::class, 'storeComentario'])
        ->whereNumber('id')->name('leads.comentarios.store');
    Route::delete('/leads/{id}/comentarios/{comentarioId}', [\App\Http\Controllers\pages\lk_beneficios\LeadController::class, 'destroyComentario'])
        ->whereNumber('id')->whereNumber('comentarioId')->name('leads.comentarios.destroy');
    Route::put('/leads/{id}/informacao-fixada', [\App\Http\Controllers\pages\lk_beneficios\LeadController::class, 'updateInformacaoFixada'])
        ->whereNumber('id')->name('leads.informacao-fixada.update');

    // Conversão lead -> contrato
    Route::get('/leads/{id}/converter', [\App\Http\Controllers\pages\lk_beneficios\ConversaoController::class, 'form'])
        ->whereNumber('id')->name('leads.converter.form');
    Route::post('/leads/{id}/converter', [\App\Http\Controllers\pages\lk_beneficios\ConversaoController::class, 'submit'])
        ->whereNumber('id')->name('leads.converter.submit');

    // Base Saúde (fila de aquisição)
    Route::get('/base-saude', [\App\Http\Controllers\pages\lk_beneficios\BaseSaudeController::class, 'index'])
        ->name('base-saude.index');
    Route::get('/base-saude/datatable', [\App\Http\Controllers\pages\lk_beneficios\BaseSaudeController::class, 'datatable'])
        ->name('base-saude.datatable');
    Route::post('/base-saude/pegar', [\App\Http\Controllers\pages\lk_beneficios\BaseSaudeController::class, 'pegar'])
        ->name('base-saude.pegar');

    // Catálogo de produtos (administrativo)
    Route::get('/produtos', [\App\Http\Controllers\pages\lk_beneficios\ProdutoController::class, 'index'])
        ->name('produtos.index');
    Route::get('/produtos/datatable', [\App\Http\Controllers\pages\lk_beneficios\ProdutoController::class, 'datatable'])
        ->name('produtos.datatable');
    Route::post('/produtos', [\App\Http\Controllers\pages\lk_beneficios\ProdutoController::class, 'store'])
        ->name('produtos.store');
    Route::put('/produtos/{id}', [\App\Http\Controllers\pages\lk_beneficios\ProdutoController::class, 'update'])
        ->whereNumber('id')->name('produtos.update');
    Route::patch('/produtos/{id}/toggle', [\App\Http\Controllers\pages\lk_beneficios\ProdutoController::class, 'toggleAtivo'])
        ->whereNumber('id')->name('produtos.toggle');
    Route::delete('/produtos/{id}', [\App\Http\Controllers\pages\lk_beneficios\ProdutoController::class, 'destroy'])
        ->whereNumber('id')->name('produtos.destroy');
});

/** WHATSAPP — Webhook público da Evolution API (validado por token de instância) */
Route::post('/webhook/whatsapp/{instanceName}/{token}', [\App\Http\Controllers\Webhook\EvolutionWebhookController::class, 'handle'])
  ->middleware('throttle:240,1')
  ->name('whatsapp.webhook');

/** WHATSAPP — Módulo de conversas (feature individual do vendedor; nenhum outro perfil acessa) */
Route::middleware(['auth', 'role:' . \App\Enums\UserRole::VENDEDOR])->prefix('whatsapp')->group(function () {

  // Conexão da instância — somente vendedores (widget dentro do kanban)
  Route::middleware(['role:' . \App\Enums\UserRole::VENDEDOR])->group(function () {
    Route::get('/conexao', fn () => redirect()->route('whatsapp.kanban'))->name('whatsapp.conexao');
    Route::post('/conexao/conectar', [\App\Http\Controllers\pages\whatsapp\WhatsappConexaoController::class, 'conectar'])->name('whatsapp.conexao.conectar');
    Route::get('/conexao/status', [\App\Http\Controllers\pages\whatsapp\WhatsappConexaoController::class, 'status'])->name('whatsapp.conexao.status');
    Route::get('/conexao/qr', [\App\Http\Controllers\pages\whatsapp\WhatsappConexaoController::class, 'qr'])->name('whatsapp.conexao.qr');
    Route::post('/conexao/desconectar', [\App\Http\Controllers\pages\whatsapp\WhatsappConexaoController::class, 'desconectar'])->name('whatsapp.conexao.desconectar');
  });

  // Tutorial / central de ajuda do módulo
  Route::get('/ajuda', function () {
    return view('content.pages.whatsapp.ajuda', [
      'podeConectar' => (int) \Illuminate\Support\Facades\Auth::user()->user_role_id === \App\Enums\UserRole::VENDEDOR,
    ]);
  })->name('whatsapp.ajuda');

  // Kanban de conversas
  Route::get('/kanban', [\App\Http\Controllers\pages\whatsapp\WhatsappKanbanController::class, 'index'])->name('whatsapp.kanban');
  Route::get('/kanban/board', [\App\Http\Controllers\pages\whatsapp\WhatsappKanbanController::class, 'getBoardData'])->name('whatsapp.kanban.board');
  Route::post('/kanban/change-status', [\App\Http\Controllers\pages\whatsapp\WhatsappKanbanController::class, 'changeStatusConversa'])->name('whatsapp.kanban.changeStatus');

  // Chat
  Route::get('/chat/{conversaId?}', [\App\Http\Controllers\pages\whatsapp\WhatsappChatController::class, 'index'])->name('whatsapp.chat');
  Route::get('/conversas', [\App\Http\Controllers\pages\whatsapp\WhatsappChatController::class, 'getConversas'])->name('whatsapp.conversas');
  Route::post('/conversas/nova', [\App\Http\Controllers\pages\whatsapp\WhatsappChatController::class, 'novaConversa'])->middleware('role:' . \App\Enums\UserRole::VENDEDOR)->name('whatsapp.novaConversa');
  Route::post('/conversas/{conversaId}/descartar', [\App\Http\Controllers\pages\whatsapp\WhatsappChatController::class, 'descartarConversa'])->name('whatsapp.descartarConversa');
  Route::post('/conversas/{conversaId}/restaurar', [\App\Http\Controllers\pages\whatsapp\WhatsappChatController::class, 'restaurarConversa'])->name('whatsapp.restaurarConversa');
  Route::post('/conversas/{conversaId}/limpar', [\App\Http\Controllers\pages\whatsapp\WhatsappChatController::class, 'limparConversa'])->name('whatsapp.limparConversa');
  Route::delete('/conversas/{conversaId}', [\App\Http\Controllers\pages\whatsapp\WhatsappChatController::class, 'apagarConversa'])->name('whatsapp.apagarConversa');
  Route::get('/leads', [\App\Http\Controllers\pages\whatsapp\WhatsappChatController::class, 'buscarLeads'])->name('whatsapp.leads');
  Route::get('/conversas/{conversaId}/mensagens', [\App\Http\Controllers\pages\whatsapp\WhatsappChatController::class, 'getMensagens'])->name('whatsapp.mensagens');
  Route::post('/conversas/{conversaId}/enviar', [\App\Http\Controllers\pages\whatsapp\WhatsappChatController::class, 'enviarMensagem'])->name('whatsapp.enviar');
  Route::post('/conversas/{conversaId}/reenviar/{mensagemId}', [\App\Http\Controllers\pages\whatsapp\WhatsappChatController::class, 'reenviarMensagem'])->name('whatsapp.reenviar');
  Route::post('/conversas/{conversaId}/ler', [\App\Http\Controllers\pages\whatsapp\WhatsappChatController::class, 'marcarLida'])->name('whatsapp.ler');
  Route::post('/conversas/{conversaId}/vincular-contato', [\App\Http\Controllers\pages\whatsapp\WhatsappChatController::class, 'vincularContato'])->name('whatsapp.vincularContato');

  // Ações sobre o lead vinculado à conversa
  Route::get('/conversas/{conversaId}/lead/carteira', [\App\Http\Controllers\pages\whatsapp\WhatsappChatController::class, 'leadCarteira'])->name('whatsapp.leadCarteira');
  Route::get('/conversas/{conversaId}/lead/comentarios', [\App\Http\Controllers\pages\whatsapp\WhatsappChatController::class, 'leadComentarios'])->name('whatsapp.leadComentarios');
  Route::post('/conversas/{conversaId}/lead/temperatura', [\App\Http\Controllers\pages\whatsapp\WhatsappChatController::class, 'leadTemperatura'])->name('whatsapp.leadTemperatura');
});
