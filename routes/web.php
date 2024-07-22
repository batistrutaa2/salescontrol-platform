<?php

use App\Http\Controllers\Auth\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\authentications\LoginBasic;
use App\Http\Controllers\pages\comerical\Comercial;
use App\Http\Controllers\pages\HomePage;
use App\Http\Controllers\pages\mailing\Mailing;
use App\Http\Controllers\pages\manager\Empresa;
use App\Http\Controllers\pages\manager\Usuarios;

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


  /** CADASTRO DE EMPRESAS */
  Route::get('/empresas', [Empresa::class, 'index'])->name('empresa.empresa');
  Route::get('/empresas/getAllCompanies', [Empresa::class, 'getAllCompanies'])->name('empresa.getAllCompanies');
  Route::post('/empresas/createCompanies', [Empresa::class, 'createCompanies'])->name('empresa.createCompanies');


  /** CADASTRO DE USUARIOS */
  Route::get('/usuarios', [Usuarios::class, 'index'])->name('usuarios.index');
  Route::post('/usuarios/createUser', [Usuarios::class, 'createUser'])->name('usuarios.createUser');
  Route::get('/usuarios/getUsers', [Usuarios::class, 'getUsers'])->name('usuarios.getUsers');


  /** MAILING */
  Route::get('/mailing/importar', [Mailing::class, 'index'])->name('mailing.importMailing');
  Route::post('/mailing/importaMailing', [Mailing::class, 'importaMailing'])->name('mailing.uploadBase');

  /** COMERCIAL */
  Route::get('/comercial/kanban', [Comercial::class, 'index'])->name('comercial.kanban');
  Route::get('/comercial/getClientComercial', [Comercial::class, 'getClientComercial'])->name('comercial.getClientComercial');
});
