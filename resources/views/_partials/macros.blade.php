@php
$label = $label ?? 'Licas Consultoria Seguros';
$full = $full ?? false;
@endphp
<span class="licas-brand {{ $full ? 'licas-brand--full' : 'licas-brand--mark' }}">
  <img src="{{ asset('assets/img/branding/licas-consultoria.jpg') }}"
    width="1280" height="1280" alt="{{ $label }}" decoding="async">
</span>
