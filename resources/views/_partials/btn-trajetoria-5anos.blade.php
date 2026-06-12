{{-- Botão de replay do agradecimento dos 5 anos (autocontido para
     funcionar em qualquer dashboard sem depender do SCSS da página) --}}
<style>
    .lk5-trajetoria-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        margin-top: 0.85rem;
        padding: 0.5rem 1.15rem;
        border-radius: 999px;
        border: 1px solid rgba(212, 175, 55, 0.45);
        background: linear-gradient(135deg, rgba(212, 175, 55, 0.14), rgba(124, 58, 237, 0.1));
        font-weight: 600;
        font-size: 0.85rem;
        letter-spacing: 0.03em;
        color: #b8860b;
        text-decoration: none;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .lk5-trajetoria-btn:hover {
        color: #9a7b1e;
        border-color: rgba(212, 175, 55, 0.8);
        box-shadow: 0 6px 22px rgba(212, 175, 55, 0.25);
        transform: translateY(-1px);
    }

    .lk5-trajetoria-btn .lk5-trajetoria-faisca {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: #d4af37;
        box-shadow: 0 0 10px rgba(212, 175, 55, 0.8);
        animation: lk5-trajetoria-pulsa 2.4s ease-in-out infinite;
    }

    .dark-style .lk5-trajetoria-btn {
        color: #f6e27a;
    }

    .dark-style .lk5-trajetoria-btn:hover {
        color: #fff3c4;
    }

    @keyframes lk5-trajetoria-pulsa {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.4; transform: scale(0.7); }
    }
</style>
<a href="{{ route('agradecimento-5anos.rever') }}" class="lk5-trajetoria-btn">
    <span class="lk5-trajetoria-faisca"></span>
    Sua trajetória até aqui →
</a>
