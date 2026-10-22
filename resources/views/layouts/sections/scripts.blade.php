<!-- BEGIN: Vendor JS-->

@vite([
  'resources/assets/vendor/libs/jquery/jquery.js',
  'resources/assets/vendor/libs/popper/popper.js',
  'resources/assets/vendor/js/bootstrap.js',
  'resources/assets/vendor/libs/node-waves/node-waves.js',
  'resources/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js',
  'resources/assets/vendor/libs/hammer/hammer.js',
  'resources/assets/vendor/libs/typeahead-js/typeahead.js',
  'resources/assets/vendor/js/menu.js'
])

@yield('vendor-script')
@if (Helper::temaEmpresa() === 'licas')
@vite(['resources/assets/js/licas-charts.js'])
@endif
<!-- END: Page Vendor JS-->
<!-- BEGIN: Theme JS-->
@vite(['resources/assets/js/main.js'])

<!-- END: Theme JS-->
<!-- Pricing Modal JS-->
@stack('pricing-script')
<!-- END: Pricing Modal JS-->
<!-- BEGIN: Page JS-->
@yield('page-script')
<!-- END: Page JS-->

@if (Helper::temaEmpresa() === 'licas')
<script>
  // Tema Licas: alterna claro/escuro pelo mesmo cookie "mode" que o Helper::appClasses lê.
  document.addEventListener('click', function (e) {
    const botao = e.target.closest('[data-licas-theme-toggle]');
    if (!botao) return;
    const proximo = document.documentElement.classList.contains('dark-style') ? 'light' : 'dark';
    if (window.templateCustomizer) {
      window.templateCustomizer.setStyle(proximo);
    } else {
      document.cookie = 'mode=' + proximo + ';path=/;max-age=31536000;SameSite=Lax';
      window.location.reload();
    }
  });
</script>
@endif
