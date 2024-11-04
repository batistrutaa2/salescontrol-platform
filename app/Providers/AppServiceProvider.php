<?php

namespace App\Providers;

use App\Repositories\Contracts\AgendamentoRepositoryInterface;
use App\Repositories\Contracts\BaseLegaceRespositoryInterface;
use App\Repositories\Contracts\ComentariosLegadosRepositoryInterface;
use App\Repositories\Contracts\ComentariosRepositoryInterface;
use App\Repositories\Contracts\ContatosCorretoresRepositoryInterface;
use App\Repositories\Contracts\ContatosRepositoryInterface;
use App\Repositories\Contracts\LeadAtividadeRepositoryInterface;
use App\Repositories\Eloquent\AgendamentoRepository;
use App\Repositories\Eloquent\BaseLegaceRespository;
use App\Repositories\Eloquent\LeadAtividadeRepository;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Vite;
use App\Repositories\Contracts\EmpresaRepositoryInterface;
use App\Repositories\Contracts\TabulacoesRepositoryInterface;
use App\Repositories\Contracts\UsuariosRepositoryInterface;
use App\Repositories\Contracts\VendasRepositoryInterface;
use App\Repositories\Eloquent\ComentariosLegadosRepository;
use App\Repositories\Eloquent\ComentariosRepository;
use App\Repositories\Eloquent\ContatosCorretoresRepository;
use App\Repositories\Eloquent\ContatosRepository;
use App\Repositories\Eloquent\EmpresaRepository;
use App\Repositories\Eloquent\TabulacoesRepository;
use App\Repositories\Eloquent\UsuariosRepository;
use App\Repositories\Eloquent\VendasRepository;

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

  }

  /**
   * Bootstrap any application services.
   */
  public function boot(): void
  {
    Vite::useStyleTagAttributes(function (?string $src, string $url, ?array $chunk, ?array $manifest) {
      if ($src !== null) {
        return [
          'class' => preg_match("/(resources\/assets\/vendor\/scss\/(rtl\/)?core)-?.*/i", $src) ? 'template-customizer-core-css' : (preg_match("/(resources\/assets\/vendor\/scss\/(rtl\/)?theme)-?.*/i", $src) ? 'template-customizer-theme-css' : '')
        ];
      }
      return [];
    });
  }
}
