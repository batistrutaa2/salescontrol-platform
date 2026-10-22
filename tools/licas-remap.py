#!/usr/bin/env python3
"""Gera resources/assets/vendor/scss/licas/_remap.scss.

Cada tela antiga define seus tokens num bloco :root próprio (--dash-primary,
--pv-card-bg, --kb-border-radius...). Em vez de reescrever ~65k linhas de SCSS,
este script lê todas essas definições e as redefine dentro de html.theme-licas
apontando para os tokens do DS Licas (licas/_tokens.scss). O seletor tem mais
especificidade que :root e .dark-style, então vale nos dois temas e só para a
empresa com tema Licas.

O mapeamento é por sufixo (primary, card-bg, border-radius...). Sufixo sem regra
fica de fora e aparece no relatório para decisão manual.

    python3 tools/licas-remap.py            # gera o arquivo
    python3 tools/licas-remap.py --check    # só lista o que ficaria de fora
"""

import pathlib
import re
import sys

ROOT = pathlib.Path(__file__).resolve().parent.parent
FONTES = [ROOT / "resources/assets/vendor/scss/pages", ROOT / "resources/views"]
SAIDA = ROOT / "resources/assets/vendor/scss/licas/_remap.scss"

# Prefixos que não são tokens de tela.
IGNORAR_PREFIXOS = {"bs", "color", "space", "radius", "shadow", "font", "chart"}

TRANSICAO = "color var(--ease), background-color var(--ease), border-color var(--ease), opacity var(--ease)"

ACCENT = "var(--color-accent)"
TEXTO = "var(--color-text)"
MUTED = "var(--color-text-muted)"
DIVISOR = "var(--color-divider)"

MAPA = {
    # marca: roxo, violeta, ciano e afins viram o marinho
    **{s: ACCENT for s in [
        "primary", "primary-light", "primary-dark", "primary-strong", "primary-ink",
        "info", "info-light", "purple", "purple-light", "violet", "violeta", "violeta-claro",
        "cyan", "teal", "teal-light", "accent-text", "link", "color", "color-main", "color-light",
        "status-primary", "status-info", "input-focus", "gradient-start", "gradient-end",
        "fase-start", "fase-end", "glow",
    ]},
    "primary-deep": "var(--color-accent-hover)",
    **{s: "var(--color-accent-rgb)" for s in [
        "primary-rgb", "info-rgb", "purple-rgb", "teal-rgb", "color-rgb", "rgb",
    ]},
    **{s: "var(--color-accent-tint)" for s in [
        "primary-soft", "info-soft", "selected-bg", "item-active", "status-primary-bg",
        "status-info-bg",
    ]},
    "bg-primary": "var(--color-accent)",

    # status
    **{s: "var(--color-success)" for s in ["success", "success-light", "emerald", "emerald-light", "emerald-dark", "green", "green-dark", "positive-text", "resolved"]},
    **{s: "var(--color-success-rgb)" for s in ["success-rgb", "emerald-rgb"]},
    "success-soft": "var(--color-success-100)",
    "success-ink": "var(--color-success-800)",
    **{s: "var(--color-warning)" for s in ["warning", "warning-light", "amber", "amber-light"]},
    **{s: "var(--color-warning-rgb)" for s in ["warning-rgb", "amber-rgb"]},
    "warning-text": "var(--color-warning-text)",
    "warning-soft": "var(--color-warning-100)",
    "warning-ink": "var(--color-warning-800)",
    **{s: "var(--color-danger)" for s in ["danger", "danger-light", "invalid"]},
    "danger-rgb": "var(--color-danger-rgb)",
    "danger-soft": "var(--color-danger-100)",
    "danger-ink": "var(--color-danger-800)",

    # temperatura de lead (DS 2.4): frio = marinho, morno = alerta, quente = perigo
    "temp-cold": ACCENT, "temp-cold-rgb": "var(--color-accent-rgb)",
    "temp-warm": "var(--color-warning)", "temp-warm-rgb": "var(--color-warning-rgb)",
    "temp-hot": "var(--color-danger)", "temp-hot-rgb": "var(--color-danger-rgb)",

    # texto
    **{s: TEXTO for s in ["text", "text-primary", "ink", "heading-color", "body-color", "bubble-text"]},
    **{s: MUTED for s in [
        "text-secondary", "text-muted", "text-soft", "text-2", "muted", "texto-suave",
        "secondary", "secondary-light", "subtle", "dialog-muted", "bubble-muted", "slate", "slate-light",
    ]},
    "text-on-color": "var(--color-on-accent)",
    "secondary-rgb": "var(--color-accent-rgb)",
    "slate-rgb": "var(--color-accent-rgb)",

    # superfícies
    **{s: "var(--color-bg)" for s in ["bg", "bg-app", "paper", "body-bg", "fundo", "fundo-profundo", "marfim", "section-bg", "bg-secondary", "bg-thread"]},
    **{s: "var(--color-card)" for s in [
        "card", "card-bg", "glass-bg", "vidro", "bg-elevated", "dialog-surface", "table-bg",
        "column-bg", "timeline-bg", "bubble-bg", "comentario-bg",
    ]},
    **{s: "var(--color-surface)" for s in [
        "surface", "surface-2", "surface-soft", "surface-subtle", "input-bg", "bg-composer",
        "comentario-fixado-bg", "scrollbar-track",
    ]},
    "card-hover": "var(--color-card-hover)",
    "bg-sidebar": "var(--color-sidebar)",
    **{s: "var(--color-hover)" for s in ["hover", "item-hover", "overlay-light"]},
    "table-hover": "var(--color-row-hover)",
    # DS 7.11: tabela sem zebra, só divisória e hover.
    "table-stripe": "transparent",

    # bordas
    **{s: DIVISOR for s in [
        "border", "border-color", "card-border", "line", "divider", "input-border",
        "table-border", "comentario-borda", "timeline-line", "border-strong",
    ]},
    **{s: "var(--color-divider-soft)" for s in ["border-soft", "line-soft"]},
    "input-border-hover": "color-mix(in srgb, var(--color-text) 45%, transparent)",
    "border-hover": ACCENT,
    "scrollbar-thumb": "var(--color-neutral-400)",

    # forma (DS 4): cards 14, controles 8, tags 6
    **{s: "var(--radius-xl)" for s in ["border-radius", "border-radius-lg", "radius", "radius-lg", "radius-xl"]},
    **{s: "var(--radius-md)" for s in ["border-radius-sm", "radius-sm", "radius-md"]},
    **{s: "var(--radius-sm)" for s in ["border-radius-xs", "radius-xs"]},
    "control-height": "var(--control-h)",

    # sombra: card em repouso não tem; flutuante usa sm/md; dialog lg
    **{s: "none" for s in ["shadow-sm", "shadow-card", "shadow-glow", "input-shadow", "bubble-shadow", "shadow"]},
    **{s: "var(--shadow-md)" for s in ["shadow-md", "shadow-hover", "shadow-elevated"]},
    **{s: "var(--shadow-lg)" for s in ["shadow-lg", "shadow-xl", "modal-shadow"]},
    "input-shadow-focus": "0 0 0 2px color-mix(in srgb, var(--color-accent) 25%, transparent)",
    "glass-blur": "0px",
    "blur": "0px",

    # movimento (DS 6): 120ms ease-out, sem bounce
    **{s: TRANSICAO for s in ["transition", "transition-fast", "transition-slow", "trans"]},
}

# slate-50..900 → rampa neutra
for passo, neutro in {"50": 100, "100": 100, "200": 200, "300": 300, "400": 400, "500": 500,
                      "600": 600, "700": 700, "800": 800, "900": 900}.items():
    MAPA[f"slate-{passo}"] = f"var(--color-neutral-{neutro})"

DEF = re.compile(r"^\s*--([a-z0-9]+)-([a-z0-9-]+)\s*:")
ESCURO = re.compile(r"^(html)?\.(dark|light)-style\s*")
GLOBAIS = {"", ":root", "html", "body"}


def seletor_atual(pilha):
    """Junta a pilha de seletores SCSS (resolvendo &) num seletor CSS."""
    atual = [""]
    for bloco in pilha:
        partes = [p.strip() for p in bloco.split(",") if p.strip()]
        novo = []
        for pai in atual:
            for parte in partes:
                if "&" in parte:
                    novo.append(parte.replace("&", pai))
                else:
                    novo.append(f"{pai} {parte}".strip())
        atual = novo
    return atual


def normalizar(sel):
    """Tira o .dark-style da frente: o tema Licas cuida do escuro pelos próprios tokens."""
    sel = ESCURO.sub("", sel.strip())
    return sel.replace(":root", "").strip()


def coletar():
    """{seletor: {(prefixo, sufixo)}} com seletor '' para os tokens globais."""
    por_seletor = {}
    for base in FONTES:
        for arq in base.rglob("*"):
            if arq.suffix not in {".scss", ".php"}:
                continue
            pilha = []
            for linha in arq.read_text(encoding="utf-8", errors="ignore").splitlines():
                limpa = linha.split("//")[0] if arq.suffix == ".scss" else linha
                m = DEF.match(limpa)
                if m and m.group(1) not in IGNORAR_PREFIXOS:
                    for sel in seletor_atual(pilha) or [""]:
                        sel = normalizar(sel)
                        if sel.startswith("@") or "@" in sel:
                            continue
                        chave = "" if sel in GLOBAIS else sel
                        por_seletor.setdefault(chave, set()).add(m.groups())
                abre, fecha = limpa.count("{"), limpa.count("}")
                if abre and "{" in limpa:
                    sel = limpa.split("{")[0].strip()
                    for _ in range(abre):
                        pilha.append(sel if sel and not sel.startswith("@media") else "")
                        sel = ""
                for _ in range(fecha):
                    if pilha:
                        pilha.pop()
    return por_seletor


def bloco(seletores, nomes):
    linhas = [",\n".join(seletores) + " {"]
    for prefixo, sufixo in sorted(nomes):
        linhas.append(f"  --{prefixo}-{sufixo}: {MAPA[sufixo]};")
    linhas.append("}")
    return linhas


def main():
    por_seletor = coletar()
    fora = sorted({f"--{p}-{s}" for nomes in por_seletor.values() for p, s in nomes if s not in MAPA})
    total = 0
    linhas = [
        "// GERADO por tools/licas-remap.py. Não editar à mão: ajuste o MAPA no script e rode de novo.",
        "// Redefine os tokens das telas antigas para o DS Licas, só dentro de html.theme-licas.",
        "",
    ]
    for sel in sorted(por_seletor, key=lambda x: (x != "", x)):
        nomes = {n for n in por_seletor[sel] if n[1] in MAPA}
        if not nomes:
            continue
        total += len(nomes)
        if sel == "":
            seletores = ["html.theme-licas", "html.theme-licas.dark-style"]
        else:
            seletores = [f"html.theme-licas {s.strip()}" for s in sel.split(",")]
        linhas += bloco(seletores, nomes) + [""]

    if "--check" not in sys.argv:
        SAIDA.write_text("\n".join(linhas), encoding="utf-8")
        print(f"{total} definições remapeadas em {len(por_seletor)} seletores -> {SAIDA.relative_to(ROOT)}")
    print(f"{len(fora)} sem regra (ficam como estão):")
    print("  " + " ".join(fora))


if __name__ == "__main__":
    main()
