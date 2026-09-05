---
version: 1
slug: "views-content-authentications-auth-login-blade-php"
primary_target: "resources/views/content/authentications/auth-login.blade.php"
related_targets: ["resources/assets/vendor/scss/pages/page-auth-modern.scss","resources/views/_partials/macros.blade.php","resources/views/layouts/commonMaster.blade.php","config/variables.php","public/assets/img/auth/broker-login-hero.png","public/assets/img/branding/salescontrol-mark.svg"]
---

## Scope and mode

- Primary target: `resources/views/content/authentications/auth-login.blade.php`
- Related visual implementation: `resources/assets/vendor/scss/pages/page-auth-modern.scss`
- Mode: Operate, on the unauthenticated login route.
- Visual evidence: `.impeccable/review/desktop.png` and `.impeccable/review/mobile.png`.
- Direction seed: `fb3b32c9`, fixed by the user.

This brief governs the login surface as an extension of the visual world already established for SalesControl. The root `DESIGN.md` remains the authority for the shared system; this surface does not replace or merge that document or its `.impeccable/design.json` sidecar.

## Audience and job

Insurance brokers and brokerage operations staff need to recognize their real work context, confirm that they are entering SalesControl and authenticate without distraction. The page must feel like the entrance to a commercial workspace, not a marketing landing page and not the institutional page of one specific brokerage.

## Direction and memorable moment

**Editorial work portrait.** A credible broker-client conversation carries the contextual field while a quiet, exact authentication panel carries the task. The memorable moment is recognizing the broker's daily scene before entering credentials.

The visual world combines a warm editorial photograph, deep violet ink, cool paper and precise controls. A dark directional overlay protects white text and leads the eye from the message toward the person in the photograph; the subject's face remains unobstructed. The modular SalesControl symbol and name are the only brand lockup. The language is direct and operational: context first, access second.

## Composition and responsive behavior

On large screens, the page fills the viewport and divides into two fields. The contextual photograph occupies seven of twelve columns and the form occupies five; from the extra-large breakpoint onward, the balance becomes eight to four. The left field aligns its SalesControl lockup, large statement and supporting copy near the lower edge. The right field centers a single form column with a maximum width of `27rem`, generous lateral padding and no competing secondary action.

Below `992px`, the layout becomes a vertical sequence: compact photographic hero first, authentication form second. The hero keeps the main statement, moves the photograph crop to preserve the broker-client exchange and removes the lockup and supporting paragraph that would crowd the image. Its height ranges from `17rem` to `24rem`; below `576px`, it settles at `15.5rem`. The SalesControl lockup reappears above the form, so product recognition is preserved before the heading and fields. The form remains a single, full-width column with comfortable side padding and no horizontal page overflow.

**The Context Before Credentials Rule.** Mobile must keep the compact photographic hero before the form; it may simplify supporting content, but it must not collapse into a logo-only login.

**The Quiet Panel Rule.** The authentication panel contains one task and one dominant action. Do not add promotional cards, tenant campaigns or competing calls to action.

## Primary action and interaction behavior

The primary action is submitting e-mail and password through the existing authentication route. The implementation preserves CSRF protection, the previously entered e-mail after validation failure, required native fields, the e-mail error alert, remember-me control and the password visibility toggle. The submit label is explicit: “Entrar no SalesControl”.

Controls share a comfortable minimum height of `3.4rem`. The password field and visibility control behave as one outlined group; focus within the group changes its border and adds a violet halo. The primary button uses a restrained violet lift on hover, returns to rest on active press and remains typographically direct rather than adopting an all-caps marketing voice.

## Accessibility and motion

- The two regions are semantic sections labelled by their visible headings; the page uses a single `main` landmark.
- The photograph has descriptive alternative text identifying the broker, client and proposal-review context.
- E-mail and password retain visible labels, appropriate autocomplete values and required semantics. E-mail receives initial focus; the password toggle is a real button with an accessible label and a decorative icon hidden from assistive technology.
- Authentication errors use an alert role. The remember-me checkbox is paired with its label.
- Buttons, inputs, checkbox, password toggle and the mobile brand link receive explicit violet `focus-visible` treatment. The password toggle keeps a visible inner outline without breaking the merged control.
- Dark overlays maintain legibility over the photograph, while supporting form copy has a dedicated dark-theme color.
- The photograph uses a short settle animation and the primary button uses brief state transitions. Under `prefers-reduced-motion: reduce`, the photo animation and button transition are removed without hiding any state or content.

## Shared identity and tenant boundary

The shared shell names only SalesControl and uses the neutral SalesControl symbol. It must not display LK Brokers or any other brokerage name, logo, slogan or campaign. Tenant identity must not be inferred from the photograph or login copy.

Tenant-specific branding for e-mails, generated documents and specialized modules is explicitly outside the scope of this surface. Those channels require a separate configurable multi-client strategy; this login brief neither defines nor authorizes that work.

## Guardrails

### Do

- Preserve the editorial broker-client scene as meaningful context on desktop and mobile.
- Keep the form complete and immediately understandable after the hero.
- Preserve the existing authentication behavior and accessible focus/error treatments.
- Use `.impeccable/review/desktop.png` and `.impeccable/review/mobile.png` as the delivered references for crop, hierarchy and responsive sequence.

### Don't

- Don't replace the shared SalesControl identity with a brokerage-specific brand.
- Don't place copy over the subject's face or remove the mobile context hero.
- Don't add tenant-specific e-mail, document or module behavior to this surface brief.
- Don't promote this page-specific composition into the root design system without an explicit product decision.
