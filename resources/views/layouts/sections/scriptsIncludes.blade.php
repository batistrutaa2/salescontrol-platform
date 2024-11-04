@php
    use Illuminate\Support\Facades\Vite;

    $menuCollapsed = $configData['menuCollapsed'] === 'layout-menu-collapsed' ? json_encode(true) : false;
@endphp
<!-- laravel style -->
@vite(['resources/assets/vendor/js/helpers.js'])
<!-- beautify ignore:start -->
@if ($configData['hasCustomizer'])
<!--! Template customizer & Theme config files MUST be included after core stylesheets and helpers.js in the <head> section -->
  <!--? Template customizer: To hide customizer set displayCustomizer value false in config.js.  -->
  @vite(['resources/assets/vendor/js/template-customizer.js', 'resources/assets/vendor/libs/toastr/toastr.js', 'resources/assets/vendor/libs/toastr/toastr.scss'])
@endif

  <!--? Config:  Mandatory theme config file contain global vars & default theme options, Set your preferred theme option in this file.  -->
  @vite(['resources/assets/js/config.js'])

@if ($configData['hasCustomizer'])
<script type="module">
    document.addEventListener('DOMContentLoaded', function() {
        toastr.options = {
            closeButton: true,
            progressBar: true,
            positionClass: "toast-top-right",
            timeOut: 0, // Define como 0 para não fechar automaticamente
            extendedTimeOut: 0 // Define como 0 para permanecer até o clique
        };

        function checkNewAgendamentos() {
            fetch('/comercial/searchPendingAppointments')
                .then(response => response.json())
                .then(data => {
                    if (data.length >= 1) {
                        toastr.success('Hora de vender, você tem um novo agendamento disponivel..');
                    }
                })
                .catch(error => {
                    console.error('Erro ao verificar novos agendamentos:', error);
                });
        }
        checkNewAgendamentos();
        setInterval(checkNewAgendamentos, 30000);
    });



    window.templateCustomizer = new TemplateCustomizer({
        cssPath: '',
        themesPath: '',
        defaultStyle: "{{ $configData['styleOpt'] }}",
        defaultShowDropdownOnHover: "{{ $configData['showDropdownOnHover'] }}", // true/false (for horizontal layout only)
        displayCustomizer: "{{ $configData['displayCustomizer'] }}",
        lang: '{{ app()->getLocale() }}',
        pathResolver: function(path) {
            var resolvedPaths = {
                // Core stylesheets
                @foreach (['core'] as $name)
                    '{{ $name }}.scss': '{{ Vite::asset('resources/assets/vendor/scss' . $configData['rtlSupport'] . '/' . $name . '.scss') }}',
                    '{{ $name }}-dark.scss': '{{ Vite::asset('resources/assets/vendor/scss' . $configData['rtlSupport'] . '/' . $name . '-dark.scss') }}',
                @endforeach

                // Themes
                @foreach (['default', 'bordered', 'semi-dark'] as $name)
                    'theme-{{ $name }}.scss': '{{ Vite::asset('resources/assets/vendor/scss' . $configData['rtlSupport'] . '/theme-' . $name . '.scss') }}',
                    'theme-{{ $name }}-dark.scss': '{{ Vite::asset('resources/assets/vendor/scss' . $configData['rtlSupport'] . '/theme-' . $name . '-dark.scss') }}',
                @endforeach
            }
            return resolvedPaths[path] || path;
        },
        'controls': <?php echo json_encode($configData['customizerControls']); ?>,
    });
</script>
@endif
