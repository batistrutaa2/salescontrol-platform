# Orientações do projeto

## Interface: usar sempre o design system existente

Orientação explícita do usuário, reafirmada em 15/09/2026: toda nova feature ou alteração visual deve seguir o design system do projeto.

- Antes de editar a interface, ler a seção **Design System - SalesControl UI** de `CLAUDE.md` e inspecionar `resources/assets/vendor/scss/pages/dashboard-analytics.scss` e uma tela equivalente já implementada.
- Reutilizar componentes, tokens `--dash-*`, Plus Jakarta Sans, SVG inline, raios, sombras, gradientes, densidade e estados do SalesControl UI. O Cofre de Acessos é uma referência operacional.
- Bootstrap sozinho não representa o design system deste projeto. Não criar uma identidade visual isolada por rota nem substituir o sistema por preferências da skill.
- Direções específicas em `DESIGN.md`, como Temporada Comercial, valem apenas para a superfície documentada. Nas demais telas, prevalece o SalesControl UI.
- Verificar desktop e celular, temas claro e escuro, controles e estados com dados. Manter as regras de negócio durante ajustes visuais.
- Usar o wrapper `./dev` ou os containers do projeto para PHP, Composer, Node, npm, Artisan e testes, conforme `CLAUDE.md`.
