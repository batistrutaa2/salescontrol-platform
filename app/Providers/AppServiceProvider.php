<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use App\Models\Agendamento;
use App\Repositories\Contracts\LigacoesRepositoryInterface;
use App\Repositories\Contracts\LogPreditivaRepositoryInterface;
use App\Repositories\Contracts\PreditivaRepositoryInterface;
use App\Repositories\Contracts\RamaisRepositoryInterface;
use App\Repositories\Contracts\TransferenciaContatoRepositoryInterface;
use App\Repositories\Eloquent\LigacoesRepository;
use App\Repositories\Eloquent\LogPreditivaRepository;
use App\Repositories\Eloquent\PreditivaRepository;
use App\Repositories\Eloquent\RamaisRepository;
use App\Repositories\Eloquent\TransferenciaContatoRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\ServiceProvider;
use App\Repositories\Eloquent\VendasRepository;
use App\Repositories\Eloquent\EmpresaRepository;
use App\Repositories\Eloquent\ContatosRepository;
use App\Repositories\Eloquent\UsuariosRepository;
use App\Repositories\Eloquent\TabulacoesRepository;
use App\Repositories\Eloquent\AgendamentoRepository;
use App\Repositories\Eloquent\BaseLegaceRespository;
use App\Repositories\Eloquent\ComentariosRepository;
use App\Repositories\Eloquent\LeadAtividadeRepository;
use App\Repositories\Contracts\VendasRepositoryInterface;
use App\Repositories\Contracts\EmpresaRepositoryInterface;
use App\Repositories\Contracts\ContatosRepositoryInterface;
use App\Repositories\Contracts\UsuariosRepositoryInterface;
use App\Repositories\Eloquent\ComentariosLegadosRepository;
use App\Repositories\Eloquent\ContatosCorretoresRepository;
use App\Repositories\Contracts\TabulacoesRepositoryInterface;
use App\Repositories\Contracts\AgendamentoRepositoryInterface;
use App\Repositories\Contracts\BaseLegaceRespositoryInterface;
use App\Repositories\Contracts\ComentariosRepositoryInterface;
use App\Repositories\Contracts\LeadAtividadeRepositoryInterface;
use App\Repositories\Contracts\ComentariosLegadosRepositoryInterface;
use App\Repositories\Contracts\ContatosCorretoresRepositoryInterface;
use App\Repositories\Contracts\PreditivaRegraRepositoryInterface;
use App\Repositories\Eloquent\PreditivaRegraRepository;
use App\Repositories\Contracts\ResumoOperacionalRepositoryInterface;
use App\Repositories\Eloquent\ResumoOperacionalRepository;
use App\Repositories\Contracts\WhatsappInstanciaRepositoryInterface;
use App\Repositories\Contracts\WhatsappConversaRepositoryInterface;
use App\Repositories\Contracts\WhatsappMensagemRepositoryInterface;
use App\Repositories\Eloquent\WhatsappInstanciaRepository;
use App\Repositories\Eloquent\WhatsappConversaRepository;
use App\Repositories\Eloquent\WhatsappMensagemRepository;
use Illuminate\Http\Request;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(EmpresaRepositoryInterface::class, EmpresaRepository::class);
        $this->app->bind(UsuariosRepositoryInterface::class, UsuariosRepository::class);
        $this->app->bind(ContatosRepositoryInterface::class, ContatosRepository::class);
        $this->app->bind(ContatosCorretoresRepositoryInterface::class, ContatosCorretoresRepository::class);
        $this->app->bind(TabulacoesRepositoryInterface::class, TabulacoesRepository::class);
        $this->app->bind(ComentariosRepositoryInterface::class, ComentariosRepository::class);
        $this->app->bind(ComentariosLegadosRepositoryInterface::class, ComentariosLegadosRepository::class);
        $this->app->bind(VendasRepositoryInterface::class, VendasRepository::class);
        $this->app->bind(BaseLegaceRespositoryInterface::class, BaseLegaceRespository::class);
        $this->app->bind(LeadAtividadeRepositoryInterface::class, LeadAtividadeRepository::class);
        $this->app->bind(AgendamentoRepositoryInterface::class, AgendamentoRepository::class);
        $this->app->bind(RamaisRepositoryInterface::class, RamaisRepository::class);
        $this->app->bind(TransferenciaContatoRepositoryInterface::class, TransferenciaContatoRepository::class);

        $this->app->bind(LigacoesRepositoryInterface::class, LigacoesRepository::class);
        $this->app->bind(PreditivaRepositoryInterface::class, PreditivaRepository::class);
        $this->app->bind(LogPreditivaRepositoryInterface::class, LogPreditivaRepository::class);
        $this->app->bind(PreditivaRegraRepositoryInterface::class, PreditivaRegraRepository::class);
        $this->app->bind(ResumoOperacionalRepositoryInterface::class, ResumoOperacionalRepository::class);

        $this->app->bind(WhatsappInstanciaRepositoryInterface::class, WhatsappInstanciaRepository::class);
        $this->app->bind(WhatsappConversaRepositoryInterface::class, WhatsappConversaRepository::class);
        $this->app->bind(WhatsappMensagemRepositoryInterface::class, WhatsappMensagemRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Forçar HTTPS se vier através de proxy (Nginx)
        if (
            isset($_SERVER['HTTP_X_FORWARDED_PROTO']) &&
            $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https'
        ) {
            URL::forceScheme('https');
        }

        // Remover esse bloco de redirecionamento forçado
        // Isso impede que funcione em outros domínios
        /*
        if (
            app()->environment('production') &&
            request()->getHost() !== 'brsolution.tech' &&
            !app()->runningInConsole()
        ) {
            redirect()->to('https://brsolution.tech' . request()->getRequestUri())->send();
            exit;
        }
        */

        // Em ambiente não-produção, redireciona todos os e-mails para um único
        // endereço de testes (config mail.dev_redirect / MAIL_DEV_REDIRECT).
        $devRedirect = config('mail.dev_redirect');
        if ($devRedirect && ! app()->environment('production')) {
            Mail::alwaysTo($devRedirect);
        }

        // Marca o login para o mascote saudar (bom dia/tarde/noite) na 1ª página.
        // Consumido via session()->pull('mascote_saudar') no partial do mascote.
        Event::listen(Login::class, function () {
            session()->put('mascote_saudar', true);
        });

        View::composer('*', function ($view) {
            if (Auth::check()) {
                $modelAgendamento = new Agendamento();
                $repositoryAgendamento = new AgendamentoRepository($modelAgendamento);
                $agendamentosAtrasados = $repositoryAgendamento->LateAppointments();

                $quantidade = $agendamentosAtrasados->count();

                $view->with([
                    'agendamentos' => $agendamentosAtrasados,
                    'isNotification' => $quantidade >= 1
                ]);
            }
        });

        Vite::useStyleTagAttributes(function (?string $src, string $url, ?array $chunk, ?array $manifest) {
            if ($src !== null) {
                return [
                    'class' => preg_match("/(resources\/assets\/vendor\/scss\/(rtl\/)?core)-?.*/i", $src) ? 'template-customizer-core-css' : (preg_match("/(resources\/assets\/vendor\/scss\/(rtl\/)?theme)-?.*/i", $src) ? 'template-customizer-theme-css' : '')
                ];
            }
            return [];
        });
    }


    /**
     * Configurar trusted proxies para Docker + Apache
     */
    private function configureTrustedProxies(): void
    {
        // Definir proxies confiáveis (Apache local)
        $proxies = ['127.0.0.1', 'localhost'];

        // Headers que o Apache vai enviar
        $headers = Request::HEADER_X_FORWARDED_FOR |
            Request::HEADER_X_FORWARDED_HOST |
            Request::HEADER_X_FORWARDED_PORT |
            Request::HEADER_X_FORWARDED_PROTO;

        Request::setTrustedProxies($proxies, $headers);
    }
}