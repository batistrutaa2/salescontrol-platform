<!-- BEGIN: Theme CSS-->
<!-- Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&ampdisplay=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;600;700&family=DM+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@500;600&display=swap" rel="stylesheet">

@vite([
  'resources/assets/vendor/fonts/remixicon/remixicon.scss',
  'resources/assets/vendor/fonts/flag-icons.scss',
  'resources/assets/vendor/libs/node-waves/node-waves.scss'
])
<!-- Core CSS -->
@vite([
  'resources/assets/vendor/scss'.$configData['rtlSupport'].'/core' .($configData['style'] !== 'light' ? '-' . $configData['style'] : '') .'.scss',
  'resources/assets/vendor/scss'.$configData['rtlSupport'].'/' .$configData['theme'] .($configData['style'] !== 'light' ? '-' . $configData['style'] : '') .'.scss',
  'resources/assets/css/demo.css'
])

<!-- Vendor Styles -->
@vite([
  'resources/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.scss',
  'resources/assets/vendor/libs/typeahead-js/typeahead.scss'
])
@yield('vendor-style')

<!-- Custom Sidebar Styles -->
@vite(['resources/assets/vendor/scss/pages/sidebar-custom.scss'])

<!-- Page Styles -->
@yield('page-style')
