<?php

use Illuminate\Support\Facades\Schedule;
use Carbon\Carbon;

Schedule::command('verificar:agendamentos')
    ->everyMinute()
    ->timezone('America/Sao_Paulo')
    ->when(function () {
        $hora = Carbon::now('America/Sao_Paulo')->format('H:i');
        return $hora >= '08:00' && $hora <= '20:00';
    });

Schedule::command('birthdays:send')
    ->timezone('America/Sao_Paulo')
    ->dailyAt('09:00')
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command('resumo-diario:send')
    ->timezone('America/Sao_Paulo')
    ->weekdays()
    ->dailyAt('18:00')
    ->withoutOverlapping()
    ->onOneServer();
