<!DOCTYPE html>
@php
$menuFixed = ($configData['layout'] === 'vertical') ? ($menuFixed ?? '') : (($configData['layout'] === 'front') ? '' : $configData['headerType']);
$navbarType = ($configData['layout'] === 'vertical') ? $configData['navbarType']: (($configData['layout'] === 'front') ? 'layout-navbar-fixed': '');
$isFront = ($isFront ?? '') == true ? 'Front' : '';
$contentLayout = (isset($container) ? (($container === 'container-xxl') ? "layout-compact" : "layout-wide") : "");
@endphp

<html lang="{{ session()->get('locale') ?? app()->getLocale() }}" class="{{ $configData['style'] }}-style {{($contentLayout ?? '')}} {{ ($navbarType ?? '') }} {{ ($menuFixed ?? '') }} {{ $menuCollapsed ?? '' }} {{ $menuFlipped ?? '' }} {{ $menuOffcanvas ?? '' }} {{ $footerFixed ?? '' }} {{ $customizerHidden ?? '' }}" dir="{{ $configData['textDirection'] }}" data-theme="{{ $configData['theme'] }}" data-assets-path="{{ asset('/assets') . '/' }}" data-base-url="{{url('/')}}" data-framework="laravel" data-template="{{ $configData['layout'] . '-menu-' . $configData['themeOpt'] . '-' . $configData['styleOpt'] }}" data-style="{{$configData['styleOptVal']}}">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

  <title>@yield('title') |
    {{ config('variables.templateName') ? config('variables.templateName') : 'TemplateName' }} -
    {{ config('variables.templateSuffix') ? config('variables.templateSuffix') : 'TemplateSuffix' }}</title>
  <meta name="description" content="{{ config('variables.templateDescription') ? config('variables.templateDescription') : '' }}" />
  <meta name="keywords" content="{{ config('variables.templateKeyword') ? config('variables.templateKeyword') : '' }}">
  <!-- laravel CRUD token -->
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <!-- Canonical SEO -->
  <link rel="canonical" href="{{ config('variables.productPage') ? config('variables.productPage') : '' }}">
  <!-- Favicon -->
  <link rel="icon" type="image/svg+xml" href="{{ asset('assets/img/branding/salescontrol-mark.svg') }}" />


  <!-- Include Styles -->
  <!-- $isFront is used to append the front layout styles only on the front layout otherwise the variable will be blank -->
  @include('layouts/sections/styles' . $isFront)

  <!-- Include Scripts for customizer, helper, analytics, config -->
  <!-- $isFront is used to append the front layout scriptsIncludes only on the front layout otherwise the variable will be blank -->
  @include('layouts/sections/scriptsIncludes' . $isFront)
</head>

<body>
  @if (request()->routeIs('login'))
  <!--
  THESIS: O acesso começa no trabalho real do corretor; recusa a tela institucional genérica centrada na marca de uma única empresa.
  OWN-WORLD: Fotografia editorial quente, tinta violeta profunda, papel claro e controles precisos com o símbolo modular do SalesControl.
  STORY: O corretor reconhece seu contexto de trabalho, entende que está no SalesControl e entra com as credenciais já conhecidas.
  FIRST VIEWPORT: Corretor em atendimento ocupa o campo visual à esquerda; mensagem curta sobre a rotina comercial repousa no espaço escuro; formulário permanece inteiro e dominante à direita.
  FORM: Retrato de trabalho editorial dentro do mundo visual estabelecido; direção fixada pelo usuário e registrada sob a seed fb3b32c9.
  FINISH: unreviewed and undocumented is unfinished; this build ends with the finish review, the verdict, DESIGN.md, and every shipping raster carrying its provenance
  -->
  @endif
  @if (request()->routeIs('dashboard.vendedor'))
  <!--
  THESIS: O desempenho comercial funciona como uma temporada legível; recusa o mosaico de KPIs sem hierarquia.
  OWN-WORLD: Violeta profundo, papel frio, verde de confirmação e âmbar de marco; placares tipográficos, evolução mensal e linhas precisas.
  STORY: O vendedor alterna ano, mês e trimestre, reconhece sua posição e entende de onde vem o resultado.
  FIRST VIEWPORT: Filtro global de período, ranking central, total válido à esquerda e maior venda à direita.
  FORM: Temporada Comercial, sexta estrutura da lista; seed 6704db12.
  FINISH: unreviewed and undocumented is unfinished; this build ends with the finish review, the verdict, DESIGN.md, and every shipping raster carrying its provenance
  -->
  @endif
  @if (request()->routeIs('manager.funis.*'))
  <!--
  THESIS: Cada corretora enxerga e governa um único fluxo operacional; recusa a configuração abstrata que esconde o tenant e os efeitos da mudança.
  OWN-WORLD: Papel frio, tinta violeta, linhas precisas e um campo violeta de autoridade; etapas são uma sequência, não um mosaico de cartões.
  STORY: O gestor confirma a empresa ativa, entende quais etapas sustentam regras do sistema e configura nomes, prazos, atividade e ordem com segurança.
  FIRST VIEWPORT: Identidade da empresa e ação de criar etapa à esquerda; resumo de segurança à direita; os dois fluxos aparecem abaixo como ledgers ordenados.
  FORM: Ledger operacional dentro do mundo visual estabelecido; extensão direta registrada sob a seed f4c9382a.
  FINISH: unreviewed and undocumented is unfinished; this build ends with the finish review, the verdict, DESIGN.md, and every shipping raster carrying its provenance
  -->
  @endif

  <!-- Layout Content -->
  @yield('layoutContent')
  <!--/ Layout Content -->

  {{-- remove while creating package --}}
  {{-- remove while creating package end --}}

  <!-- Include Scripts -->
  <!-- $isFront is used to append the front layout scripts only on the front layout otherwise the variable will be blank -->
  @include('layouts/sections/scripts' . $isFront)

</body>

</html>
