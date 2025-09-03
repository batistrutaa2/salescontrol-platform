<?php


use App\Http\Controllers\Auth\Auth;
use App\Http\Controllers\pages\comissionamento\Comissionamento;
use App\Http\Controllers\pages\ranking\RankingVendas;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\pages\HomePage;
use App\Http\Controllers\manager\Manager;
use App\Http\Controllers\pages\pabx\Pabx;
use App\Http\Controllers\pages\vendas\Vendas;
use App\Http\Controllers\pages\mailing\Mailing;
use App\Http\Controllers\pages\manager\Empresa;
use App\Http\Controllers\pages\manager\Usuarios;
use App\Http\Controllers\pages\comerical\Comercial;
use App\Http\Controllers\authentications\LoginBasic;
use App\Http\Controllers\pages\backoffice\Backoffice;
use App\Http\Controllers\pages\relatorios\Relatorios;
use App\Http\Controllers\pages\comercial\ReunioesComercial;
use App\Http\Controllers\pages\comercial\ConsultaController;


Route::get('/', [LoginBasic::class, 'index'])->name('login');


Route::post('/logout', [LoginBasic::class, 'logout'])->name('logout');
Route::post('autentication', [Auth::class, 'login'])->name('login.autentication');


Route::middleware(['auth'])->group(function () {

  /** MANAGER */
  Route::get('manager/changeCompany/{companyId}', [Manager::class, 'changeCompany'])->name('manager.changeCompany');

  Route::get('/notificacoes/novas', function () {
    $notifications = auth()->user()->unreadNotifications
      ->filter(fn($n) => !isset($n->data['agendado_por']) || $n->data['agendado_por'] == auth()->user()->id);

    return response()->json($notifications->values());
  })->middleware('auth')->name('notificacoes.novas');


  /** PAGINA INICIAL */
  Route::get('dashboard', [HomePage::class, 'index'])->name('home.dashboard');
  Route::get('searchMetrics/{month}/{year}', [HomePage::class, 'searchMetrics'])->name('home.searchMetrics');

  /** CADASTRO DE EMPRESAS */
  Route::get('/empresas', [Empresa::class, 'index'])->name('empresa.empresa');
  Route::get('/empresas/getAllCompanies', [Empresa::class, 'getAllCompanies'])->name('empresa.getAllCompanies');
  Route::post('/empresas/createCompanies', [Empresa::class, 'createCompanies'])->name('empresa.createCompanies');

  /** CADASTRO DE USUARIOS */
  Route::get('/usuarios', [Usuarios::class, 'index'])->name('usuarios.index');
  Route::get('/usuarios/editar-usuario/{idUser}', [Usuarios::class, 'editUser'])->name('usuarios.editUser');
  Route::get('/usuarios/getUsers', [Usuarios::class, 'getUsers'])->name('usuarios.getUsers');
  Route::post('/usuarios/createUser', [Usuarios::class, 'createUser'])->name('usuarios.createUser');
  Route::post('/usuarios/editUser', [Usuarios::class, 'updateUser'])->name('usuarios.updateUser');

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
  Route::get('/getPreditiva', [Mailing::class, 'getPreditiva'])->name('mailing.getPreditiva');
  Route::get('/mailing/leads-descartados', [Mailing::class, 'leadDescartados'])->name('mailing.leadDescartados');
  Route::get('/mailing/get-leads-descartados', [Mailing::class, 'getLeadsDescartados'])->name('comercial.getLeadsDescartados');
  Route::get('/mailing/getComentariosLead/{id}', [Mailing::class, 'getComentariosLead'])->name('comercial.getComentariosLead');
  Route::post('/mailing/excluir-lead-descartado/{id}', [Mailing::class, 'deleteMailingLeadsDescarted'])->name('mailing.deleteMailingLeadsDescarted');

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

  Route::get('/comercial/calendario-reunioes', [ReunioesComercial::class, 'index'])->name('comercialReunioes.index');
  Route::get('/reunioes/data', [ReunioesComercial::class, 'getReunioes']);
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

  /** BACKOFFICE */
  Route::get(uri: '/back-office/fila-contratos', action: [Backoffice::class, 'index'])->name(name: 'backoffice.index');
  Route::get(uri: '/back-office/lista-contratos', action: [Backoffice::class, 'listContract'])->name(name: 'backoffice.listContracts');
  Route::get(uri: '/back-office/abrir-contrato/{idContrato}', action: [Backoffice::class, 'openContract'])->name(name: 'backoffice.openContract');
  Route::get(uri: '/back-office/lista-vendas-filtro', action: [Backoffice::class, 'listSalesFilter'])->name('backoffice.listSalesFilter');
  Route::post(uri: '/back-office/atualizar-contrato', action: [Backoffice::class, 'updateSale'])->name('backoffice.updateSale');
  Route::post(uri: '/back-office/alterar-status-contrato', action: [Backoffice::class, 'alterStatusContract'])->name('backoffice.alterStatusContract');
  Route::get(uri: '/back-office/comprovante/{id}', action: [Backoffice::class, 'downloadPaymentProof'])->name('backoffice.downloadPaymentProof');
  Route::get(uri: '/back-office/deletar-contrato/{id}', action: [Backoffice::class, 'deleteContract'])->name('backoffice.deleteContract');
  Route::get(uri: '/back-office/cadastrar-planos', action: [Backoffice::class, 'planos'])->name('backoffice.planos');
  Route::get(uri: '/back-office/cadastrar-operadora', action: [Backoffice::class, 'operadoras'])->name('backoffice.operadoras');
  Route::post(uri: '/back-office/createOperation', action: [Backoffice::class, 'createOperation'])->name('backoffice.createOperation');
  Route::post(uri: '/back-office/createPlan', action: [Backoffice::class, 'createPlan'])->name('backoffice.createPlan');
  Route::get(uri: '/back-office/getOperators', action: [Backoffice::class, 'getOperators'])->name('backoffice.getOperators');
  Route::get(uri: '/back-office/getPlans', action: [Backoffice::class, 'getPlans'])->name('backoffice.getPlans');
  Route::put('/backoffice/titulares/{id}', [Backoffice::class, 'updateTitular'])->name('backoffice.titulares.update');
  Route::post('/backoffice/titulares', [Backoffice::class, 'storeTitular'])->name('backoffice.titulares.store');


  /** VENDAS */
  Route::get('/vendas/lista-vendas', [Vendas::class, 'index'])->name('sale.listSale');
  Route::get('/vendas/lista-vendas-mes', [Vendas::class, 'salesOfTheMonth'])->name('sale.salesOfTheMonth');
  Route::get('/vendas/analitico', [Vendas::class, 'analyticalSales'])->name('sale.analyticalSales');
  Route::get('/vendas/vendasAnalitico', [Vendas::class, 'getSalesAnalytical'])->name('sale.getSalesAnalytical');
  Route::get('/vendas/filtro-vendas-mes/{nome_corretor?}', [Vendas::class, 'monthlySalesFilter'])->name('sale.monthlySalesFilter');
  Route::get('/vendas/getResultsBroker', [Vendas::class, 'getResultsBroker'])->name('sale.getResultsBroker');

  Route::get('/vendas/dados', [Vendas::class, 'dados'])->name('sale.dados');
  Route::get('/vendas/listar', [Vendas::class, 'listarVendas'])->name('sale.listarVendas');
  Route::get('/vendas/exportar', [Vendas::class, 'exportar'])->name('sale.exportar');
  Route::get('/vendas/boletos/{id}', [Vendas::class, 'downloadBoleto'])->name('sale.downloadBoleto');

  /** PABX */
  Route::get('/pabx/cadastro-ramais', [Pabx::class, 'index'])->name('index.createRamal');
  Route::get('/pabx/getRamais', [Pabx::class, 'getRamais'])->name('pabx.getRamais');
  Route::post('/pabx/createramal', [Pabx::class, 'createramal'])->name('pabx.createramal');
  Route::post('/pabx/clickToCall', [Pabx::class, 'clickToCall'])->name('pabx.clickToCall');

  /** RELATORIOS */
  Route::get('/relatorios/ligacoes', [Relatorios::class, 'index'])->name('pabx.getLigacoess');
  Route::get('/relatorios/getList/{id_user}/{data_inicial}/{data_final}', [Relatorios::class, 'getLigacoes'])->name('pabx.getLigacoes');
  Route::get('/relatorios/preditiva', [Relatorios::class, 'predictiveReport'])->name('relatorios.preditiva.predictiveReport');
  Route::post('/relatorios/buscar', [Relatorios::class, 'get'])->name('relatorios.preditiva.buscar');
  Route::get('/relatorios/atividade', [Relatorios::class, 'activityReport'])->name('relatorios.activityReport');
  Route::get('/relatorios/atividade-dados/{dataInicial}/{dataFinal}/{leadsMes?}/{idVendedor?}', [Relatorios::class, 'activityReportData'])->name('relatorios.activityReportData');
  Route::get('/relatorios/implantacoes', [Relatorios::class, 'implantacoes'])->name('relatorios.implantacoes');
  Route::get('/relatorios/implantacoes/dados', [Relatorios::class, 'implantacoesData'])->name('relatorios.implantacoes.dados');
  Route::get('/relatorios/implantacoes/listar', [Relatorios::class, 'implantacoesList'])->name('relatorios.implantacoes.listar');

  /** RANKING DE VENDAS */
  Route::get('/ranking', [RankingVendas::class, 'index'])->name('ranking.index');
  Route::get('/ranking/configuracao/edit/{id}', [RankingVendas::class, 'edit'])->name('ranking.edit');
  Route::get('/ranking/configuracao', [RankingVendas::class, 'config'])->name('ranking.config');
  Route::get('/ranking-vendas', [RankingVendas::class, 'rankingVendas'])->name('ranking.rankingVendas');
  Route::get('/rankingVendasData', [RankingVendas::class, 'rankingVendasData'])->name('ranking.rankingVendasData');
  Route::get('/vendas/valores-mensais', [RankingVendas::class, 'valoresMensais'])->name('ranking.valoresMensais');



  /** COMISSIONAMENTO */
  Route::get('/comissionamento', [Comissionamento::class, 'index'])->name('comissionamento.index');
  Route::get('/comissionamento/getCommissioning', [Comissionamento::class, 'getCommissioning'])->name('comissionamento.getCommissioning');
  Route::post('/comissionamento', [Comissionamento::class, 'store'])->name('comissionamento.store');
  Route::delete('/comissionamento/{id}', [Comissionamento::class, 'destroy'])->name('comissionamento.destroy');
  Route::get('/comissionamento/faturar', [Comissionamento::class, 'invoiceCommission'])->name('comissionamento.invoiceCommission');
  Route::get('/comissionamento/faturamento', [Comissionamento::class, 'getFaturamentoComissionamento'])->name('comissionamento.faturamento');

});
Route::get('/relatorios/lead-comentarios/{leadId}', [Relatorios::class, 'getLeadComentarios'])->name('relatorios.leadComentarios');


Route::post('/consulta/pessoa', [ConsultaController::class, 'consultarPessoa'])->name('consulta.pessoa');
Route::post('/consulta/empresa', [ConsultaController::class, 'consultarEmpresa'])->name('consulta.empresa');
