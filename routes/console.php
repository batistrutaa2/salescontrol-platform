<?php

use Carbon\Carbon;
use Illuminate\Support\Facades\Schedule;

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

Schedule::command('preditiva:reciclar-frios')
    ->timezone('America/Sao_Paulo')
    ->dailyAt('07:00')
    ->withoutOverlapping()
    ->onOneServer();

// WhatsApp — saúde das instâncias e reconciliação de mensagens
Schedule::command('whatsapp:monitor')
    ->everyTwoMinutes()
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command('whatsapp:resync')
    ->everyTenMinutes()
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command('whatsapp:redownload-media')
    ->everyThirtyMinutes()
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command('documentos:limpar-temporarios')
    ->dailyAt('02:30')
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command('documentos:sincronizar-diretorios')
    ->everyTenMinutes()
    ->withoutOverlapping()
    ->onOneServer();
