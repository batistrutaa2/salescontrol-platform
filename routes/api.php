<?php

use App\Http\Controllers\api\Contatos;
use Illuminate\Support\Facades\Route;


Route::post('webhook/leads-ads', [Contatos::class, 'collectCustomer']);
