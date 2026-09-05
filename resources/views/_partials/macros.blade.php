@php
$color = $color ?? '#6845df';
$label = $label ?? 'SalesControl';
@endphp
<span class="salescontrol-brand-mark" style="color: {{ $color }}; line-height: 0;">
  <svg width="{{ $height }}" height="{{ $height }}" viewBox="0 0 48 48" role="img" aria-label="{{ $label }}">
    <rect width="48" height="48" rx="12" fill="currentColor" />
    <path d="M32.5 15.5H20.25a6.25 6.25 0 0 0 0 12.5h7.5a4.75 4.75 0 0 1 0 9.5H15.5"
      fill="none" stroke="#fff" stroke-width="4" stroke-linecap="round" />
    <circle cx="34" cy="34" r="2.5" fill="#fff" />
  </svg>
</span>
