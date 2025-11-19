@php
$color = $color ?? '#9055FD';
@endphp
<span style="color:{{ $color }};">
  <img src="{{ asset('assets/img/branding/logo1.png') }}" alt="Logo" height="{{ $height }}">
</span>
