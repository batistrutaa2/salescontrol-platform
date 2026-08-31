---
version: 1
slug: "s-views-content-pages-dashboard-vendedor-blade-php"
primary_target: "resources/views/content/pages/dashboard-vendedor.blade.php"
related_targets: ["resources/assets/js/dashboard-vendedor.js","resources/assets/vendor/scss/pages/dashboard-vendedor.scss"]
---

## Scope and mode

Seller dashboard at `dashboard-vendedor`; Operate mode.

## Audience and job

LK Brokers sellers need to compare their current year, month, and quarter, identify their company-wide position, and inspect the contracts behind each result.

## Content and actions

Lead with a single global selector for current year, month, or quarter. Apply it to valid value, rank, largest sale, monthly evolution, top plan, implantation health, leaders, reversals, and the sales ledger. Leaders show only positions and names; position changes receive one bounded motivational animation.

## Constraints

Preserve tenant scoping. Valid value is contract plus fundraising and excludes only DECLINIO; ESTORNO remains in totals but keeps danger semantics. Product aggregation is by normalized plan name, never plan plus operator. Desktop and mobile must remain horizontally contained and keyboard usable.

## Direction and memorable moment

Temporada Comercial: a precise professional season scoreboard. The centered period ranking is flanked by valid value and the period's largest sale; the evolution trail explains the selected horizon without introducing another filter.

## Unresolved decisions

None.
