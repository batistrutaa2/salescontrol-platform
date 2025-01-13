<?php

use App\Http\Controllers\Auth\Auth;
use App\Http\Controllers\pages\backoffice\Backoffice;
use App\Http\Controllers\pages\pabx\Pabx;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\authentications\LoginBasic;
use App\Http\Controllers\pages\comerical\Comercial;
use App\Http\Controllers\pages\HomePage;
use App\Http\Controllers\pages\mailing\Mailing;
use App\Http\Controllers\pages\manager\Empresa;
use App\Http\Controllers\pages\manager\Usuarios;
use App\Http\Controllers\pages\vendas\Vendas;

Route::get('/login', [LoginBasic::class, 'index'])->name('login');
/**TESTE CELSO */

Route::get('/', function () {
  return redirect()->route('login');
});

Route::post('/logout', [LoginBasic::class, 'logout'])->name('logout');
Route::post('autentication', [Auth::class, 'login'])->name('login.autentication');


Route::middleware(['auth'])->group(function () {

  /** PAGINA INICIAL */
  Route::get('dashboard', [HomePage::class, 'index'])->name('home.dashboard');
  Route::get('searchMetrics/{month}/{year}', [HomePage::class, 'searchMetrics'])->name('home.dashboard');

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

  /** COMERCIAL */
  Route::get('/comercial/kanban', [Comercial::class, 'index'])->name('comercial.kanban');
  Route::get('/comercial/getClientComercial', [Comercial::class, 'getClientComercial'])->name('comercial.getClientComercial');
  Route::post('/changeStatusLead/kanban/changeStatusLead', [Comercial::class, 'changeStatusLead'])->name('comercial.changeStatusLead');
  Route::post('/comercial/saveNoteMailing', [Comercial::class, 'saveNoteMailing'])->name('comercial.saveNoteMailing');
  Route::get('/comercial/getCommentsLead/{id_mailing}', [Comercial::class, 'getCommentsLead'])->name('comercial.getCommentsLead');
  Route::get('/comercial/abrir-cliente/{id_mailing}', [Comercial::class, 'openClient'])->name('comercial.openClient');
  Route::post('/comercial/updateClient', [Comercial::class, 'updateClient'])->name('comercial.updateClient');
  Route::post('/comercial/saveComment', [Comercial::class, 'saveComment'])->name('comercial.saveComment');
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
  Route::get(uri: '/back-office/deletar-contrato/{id}', action: [Backoffice::class, 'deleteContract'])->name('backoffice.deleteContract');


  /** VENDAS */
  Route::get('/vendas/lista-vendas', [Vendas::class, 'index'])->name('sale.listSale');
  Route::get('/vendas/lista-vendas-mes', [Vendas::class, 'salesOfTheMonth'])->name('sale.salesOfTheMonth');
  Route::get('/vendas/analitico', [Vendas::class, 'analyticalSales'])->name('sale.analyticalSales');
  Route::get('/vendas/vendasAnalitico', [Vendas::class, 'getSalesAnalytical'])->name('sale.getSalesAnalytical');

  Route::get('/vendas/filtro-vendas-mes/{nome_corretor?}', [Vendas::class, 'monthlySalesFilter'])->name('sale.monthlySalesFilter');


  /** PABX */
  Route::get('/pabx/cadastro-ramais', [Pabx::class, 'index'])->name('index.createRamal');
  Route::get('/pabx/getRamais', [Pabx::class, 'getRamais'])->name('pabx.getRamais');
  Route::post('/pabx/createramal', [Pabx::class, 'createramal'])->name('pabx.createramal');
  Route::post('/pabx/clickToCall', [Pabx::class, 'clickToCall'])->name('pabx.clickToCall');
});
