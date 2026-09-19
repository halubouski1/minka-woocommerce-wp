# TECHNICAL HANDOFF: WordPress site → EspoCRM + Meta CAPI integration

**Prepared for:** AI agent setting up EspoCRM and its future integration
**Source of truth:** code inspection + live introspection of the running container
**Date:** 2026-09-03
**Rule applied:** only verified facts. Where the code cannot answer, it says so.

---

## 1. What the site is

MINKA — a **showroom/catalog site for natural mink fur coats** (Belarus, Russian-language, currency displayed as `$`).

**Critical for CRM design:** this is **not a transactional e-commerce site**. The owner confirmed there will be **no cart and no checkout**. WooCommerce is used purely as a **product catalog engine**. The conversion path is **lead generation via forms**, not orders:

- product card has only "Узнать наличие" (ask availability) and "Заказать примерку" (book a fitting) — **no add-to-cart anywhere in the theme** (verified: no `add_to_cart` / `WC()->cart` / `is_checkout` references in any theme file);
- `wc_get_orders()` returns **0 orders**; no order has ever existed;
- cart / checkout / my-account pages exist only because WooCommerce created them, and they currently render **empty** (see §10).

So the CRM object model should be built around **Lead → Contact → Opportunity driven by form submissions**, not around WooCommerce orders.

---

## 2. Technology stack — ALREADY EXISTS

| Component | Version / detail |
|---|---|
| WordPress | 7.0.2 |
| PHP | 8.3.33 |
| WooCommerce | 11.0.1, **HPOS enabled** (orders would live in `wp_wc_orders`, not `wp_posts`) |
| MariaDB | 11 (Docker) |
| Web | official `wordpress:latest` image, Apache |
| Local env | Docker Compose, site at `http://127.0.0.1:9000`, `siteurl` = `home` = same |
| Theme | custom, directory `custom-theme`, mounted from host `./theme` |

**Active plugins (7):** `gravityforms` 2.10.4, `advanced-custom-fields-pro` 6.8.4, `check-email`, `classic-editor`, `complianz-gdpr`, `woocommerce` 11.0.1, `wp-mail-smtp`.

**Front-end libraries** (vendored in theme, no build step, no npm/package.json): Swiper, Lenis (smooth scroll), AOS (scroll animations), JustValidate (form validation). No jQuery dependency in theme code (jQuery is loaded by WP/plugins).

**No build tooling:** plain CSS/JS files, cache-busted by `filemtime()` (`minka_asset_version()` in `functions.php`).

---

## 3. Architecture of the theme

Classic PHP theme, no blocks/FSE. Logic is split into `theme/inc/*.php`, all required from `functions.php`:

| File | Responsibility |
|---|---|
| `inc/forms.php` | **Gravity Forms integration — the main CRM hook point** |
| `inc/catalog.php` | catalog filters, sorting, AJAX grid, price bounds |
| `inc/product.php` | product card data, related products |
| `inc/search.php` | header search panel (AJAX), suggestions |
| `inc/favorites.php` | favorites (browser-side), AJAX card rendering |
| `inc/blog.php` | blog list, AJAX "load more" |
| `inc/consent.php` | hides Complianz's own banner, keeps layout banner |
| `inc/data.php`, `inc/about.php`, `inc/care.php`, `inc/contacts.php` | ACF-driven page content |
| `inc/acf-fields.php` | all ACF field groups registered **in code** (not in DB) |
| `inc/seed*.php` | one-time seeding of pages, demo products/posts, GF forms |
| `inc/slugs.php` | Cyrillic → Latin slug transliteration |

Templates: `front-page.php`, `archive-product.php` (catalog, URL `/catalog/`), `single-product.php`, `home.php` (blog `/blog/`), `single.php`, `404.php`, `template-{about,care,contacts,favorites,legal}.php`.

Content is editable through ACF options pages under a "MINKA" admin menu.

---

## 4. Where CRM-relevant logic lives — ALREADY EXISTS

### 4.1 Form submission pipeline (the integration point)

```
layout form (HTML in theme)
  → JustValidate client validation        theme/js/main.js  → submitForm()
  → POST admin-ajax.php  action=minka_form
  → minka_form_submit()                   theme/inc/forms.php
  → GFAPI::submit_form( $form_id, $values )
  → Gravity Forms entry + notification email
  → JSON { success: true, data: { entry: <entry_id> } }
  → JS shows the layout's "Спасибо" state
```

Key details:

- forms are **not rendered by Gravity Forms** — the layout markup is used, GF is only the storage/notification backend;
- form key travels in the `data-minka-form` attribute (`showroom`, `cta`, `contacts`, `gift`);
- form definitions live in `minka_form_definitions()` (`inc/forms.php`); each GF field carries `adminLabel` = the HTML field name, which is how the handler maps POST → GF field IDs;
- GF form IDs are stored in option **`minka_form_ids`** = `{"showroom":1,"cta":2,"contacts":3,"gift":4}`;
- values are passed to GF as `input_<id>` (checkbox as `input_<id>_1`).

### 4.2 AJAX endpoints (all `wp_ajax_` + `wp_ajax_nopriv_`)

| Action | File | Purpose |
|---|---|---|
| `minka_form` | `inc/forms.php` | **form submission — CRM-relevant** |
| `minka_search` | `inc/search.php` | product search suggestions/results |
| `minka_favorites` | `inc/favorites.php` | renders cards for saved favorites |

Two more endpoints are **not** admin-ajax but `template_redirect` handlers responding to `?minka_ajax=1` on `/catalog/` and `/blog/` (grid pagination). Not CRM-relevant.

### 4.3 WooCommerce hooks currently used

Only catalog-level ones — **nothing order/checkout related**:

```
woocommerce_update_product / woocommerce_new_product  → price-range cache flush
woocommerce_product_query                             → stock filter
woocommerce_layered_nav_default_query_type            → OR logic inside filter group
```

### 4.4 REST API

- Standard WP REST API is available at `/wp-json/` (pretty permalinks on).
- **NOT IMPLEMENTED:** no custom `register_rest_route()` anywhere in the theme. No API auth scheme (no Application Passwords, no JWT) is configured for machine access — the EspoCRM agent will need to choose and set one up if pull-style integration is wanted.

### 4.5 Email notifications

Each of the 4 GF forms has exactly one notification → `{admin_email}` with `{all_fields}`. **No customer-facing auto-reply exists.** `wp-mail-smtp` is installed but the mailer is still `mail` (PHP mail) — not production-ready.

---

## 5. Data currently collected — ALREADY EXISTS

### 5.1 Forms (Gravity Forms entries)

| Form (GF id) | Trigger | Fields |
|---|---|---|
| Записаться в шоурум (1) | showroom popup, any page | name, phone, date, time, comment, contact channel (Телефон/WhatsApp/Вайбер/Telegram), consent checkbox, **page URL** |
| Оставить заявку (2) | "Узнать наличие" on home + product page | name, phone, comment, contact channel, consent, **page URL** |
| Вопрос со страницы контактов (3) | contacts page inline form | name, phone, question, contact channel, consent, **page URL** |
| Намёк о подарке (4) | product page "gift" popup | recipient name, recipient **email**, sender name, sender **email**, consent, **product title**, **page URL** |

**Important for CAPI matching:** forms 1–3 collect **phone but no email**. Only form 4 collects emails (of two different people). So server-side Meta events for the main lead flow can only be hashed on `ph` (+ `client_ip_address`, `client_user_agent` if captured) — `em` will usually be absent.

Phone is free-text (`+375 29 111-22-33` style), **not normalized to E.164** — normalization will be needed both for EspoCRM dedupe and for Meta hashing.

The `page` field is captured client-side as `window.location.href` and is the only attribution-ish data currently stored. On the product page (forms 2 and 4) it identifies the model; form 4 additionally stores the product title.

### 5.2 WooCommerce data (products only)

12 demo products. Per product available: ID, title, slug, permalink, **SKU** (`ART-0001`…), regular price (integer), stock status (`instock`/`onbackorder`), featured image + gallery, short description, and attributes:

- global (filterable): `pa_fason`, `pa_color`, `pa_size`, `pa_length`
- custom per-product: `meh`, `kapyushon`, `podklad`, `razmernyy-ryad`, `strana-vydelki`
- ACF `related_products` (relationship, max 4)

Store currency: **USD**, symbol position `left_space`, 0 decimals (displayed as `$ 1200`). Whether USD is the real business currency is **unknown from code** — verify with the owner before mapping Opportunity amounts.

**NOT IMPLEMENTED:** orders, order IDs, order values, customer accounts (0 orders, no registered customers beyond admin).

---

## 6. Analytics & tracking — reality check

**NOT IMPLEMENTED — verified by full-text search of the theme** (`fbq`, `facebook`, `pixel`, `gtag`, `googletagmanager`, `dataLayer`, `utm_`, `fbclid`, `_fbp`, `_fbc`, `event_id`): **zero matches**.

Concretely, none of the following exist today:

- ❌ Meta Pixel (no `fbq`, no pixel ID anywhere)
- ❌ Meta Conversions API
- ❌ Google Analytics / GA4 / Google Tag Manager / dataLayer
- ❌ UTM capture, storage or persistence
- ❌ `fbclid` capture, `_fbp` / `_fbc` cookie reading
- ❌ `event_id` generation or any browser/server deduplication mechanism
- ❌ landing-page / referrer capture (only the *current* page URL at submit time)
- ❌ any tracking plugin (the 7 active plugins contain no analytics plugin)

**ALREADY EXISTS — consent infrastructure** (this matters a lot for CAPI):

- **Complianz GDPR** is active and functional. The plugin's own banner and its floating "manage consent" tab are hidden by CSS (`inc/consent.php`); the site shows the layout's own banner (`#cookie`) instead.
- `theme/js/consent.js` drives the plugin API: Принять → `cmplz_accept_all()`, Отклонить → `cmplz_deny_all()`, then `cmplz_set_banner_status('dismissed')`. Footer link "Настройки cookie" (`[data-consent-open]`) re-opens the banner.
- Consent state is readable server-side and client-side via cookies: `cmplz_functional`, `cmplz_preferences`, `cmplz_statistics`, `cmplz_marketing` (`allow`/`deny`), plus `cmplz_banner-status`, `cmplz_policy_id`, `cmplz_consented_services`. JS API: `cmplz_has_consent('marketing')`.
- Configuration was enabled **technically only** (region EU, wizard flag, "consent for anonymous statistics"). The **legal wizard has not been completed by the owner** — service list, cookie policy and region choice still need real answers.

**Implication:** Complianz has a script blocker. A Meta Pixel added later will be **blocked until `marketing` consent is granted**, and `_fbp` / `_fbc` will not exist for users who decline. Server-side CAPI events must therefore either (a) respect the same consent signal, or (b) be limited to first-party data the user knowingly submitted — an explicit decision the integration must make.

---

## 7. Best integration points WordPress → EspoCRM

Ranked by suitability:

1. **`gform_after_submission` (per form or global)** — the cleanest hook. Fires after GF has validated and stored the entry; gives the full `$entry` + `$form`. Recommended primary trigger for Lead creation. Independent of the theme's AJAX layer.
2. **`gform_entry_created`** — earlier, if the CRM push should happen before notifications.
3. **`minka_form_submit()` in `theme/inc/forms.php`** — the theme's own handler, right after `GFAPI::submit_form()` returns `is_valid`. Has the raw `$_POST` (browser-side values before GF mapping) — useful if attribution fields (utm/fbp/fbc/event_id) are later added to the POST body. Downside: it is theme code, so a refactor could move it; a dedicated hook is safer.
4. **A new mu-plugin** listening to the above hooks — **strongly recommended** so CRM/CAPI logic survives theme changes and stays out of the layout code.

Do **not** build the integration around WooCommerce order hooks: no orders will ever be created in the current business model.

For pull-style access from EspoCRM, the WP REST API exists but has **no custom endpoints and no configured machine auth** — treat it as unconfigured.

---

## 8. Usable events/hooks for CRM object lifecycle

| CRM action | Recommended WordPress trigger | Availability today |
|---|---|---|
| **Create Lead** | `gform_after_submission` for forms 1, 2, 3 (and 4 if gift hints count as leads) | ✅ available now |
| **Create/Update Contact** | same hook; dedupe key must be **phone** (email missing in 3 of 4 forms) | ✅ available now |
| **Qualified Lead** | no site-side signal exists — qualification happens inside EspoCRM (manual or rules) | ⚠️ no WP event |
| **Transfer an order** | — | ❌ not applicable, no orders |
| **Purchase / successful sale** | no online purchase exists; the "purchase" moment happens offline (showroom) | ❌ no WP event — must originate in EspoCRM (Opportunity → Closed Won) |

**Consequence for Meta CAPI:** browser-side `Lead` can be fired at form success in `main.js` (`submitForm().then(...)`). `Purchase` cannot come from WordPress at all — it must be sent **server-side from EspoCRM** when an Opportunity is won, using attribution data that WordPress passed along at lead time. That makes storing `fbp` / `fbc` / `event_id` / UTM **on the Lead record in EspoCRM** a hard requirement of the design.

If WooCommerce ever gains a checkout, the standard hooks would be `woocommerce_thankyou`, `woocommerce_order_status_completed`, `woocommerce_new_order` — none are wired today.

---

## 9. Existing data structures relevant to mapping

**Gravity Forms entry** (per form id from `minka_form_ids`): field values keyed by numeric field id; `adminLabel` on each field holds the machine name (`name`, `phone`, `comment`, `contact`, `agree`, `page`, `product`, `recipient_name`, `recipient_email`, `sender_name`, `sender_email`, `question`, `date`, `time`). Entry meta also includes GF's own `source_url`, `ip`, `user_agent`, `date_created` — **these are populated by GF and are a better attribution source than the theme's `page` field**.

Suggested WP → Espo mapping (proposal, not implemented):

| WP source | EspoCRM |
|---|---|
| `name` | Lead.firstName / lastName (single free-text field — needs a splitting rule) |
| `phone` | Lead.phoneNumber (**normalize to E.164**; primary dedupe key) |
| `recipient_email` / `sender_email` (form 4 only) | Lead.emailAddress |
| `contact` (Телефон/WhatsApp/Вайбер/Telegram) | Lead custom field "preferred channel" |
| `comment` / `question` | Lead.description |
| `date` + `time` (form 1) | Lead custom fields for requested fitting slot |
| `product` (form 4) / `page` (form 2 on product page) | Lead custom field "model of interest" → later Opportunity item |
| GF form id / `minka_form` key | Lead.source (`showroom`, `cta`, `contacts`, `gift`) |
| GF `source_url`, `ip`, `user_agent`, `date_created` | attribution + CAPI `client_ip_address`, `client_user_agent` |

**Product structure** (if models are mirrored into Espo): ID, SKU (`ART-XXXX`), title, permalink, price (int, USD), stock status, 4 filterable attributes + 5 descriptive ones.

**NOT IMPLEMENTED:** there is no customer identity on the site — no accounts, no persistent visitor ID, no session id, no first-party cookie other than favorites (`localStorage` key `minka_favorites`, product IDs only, never sent to the server except to render cards).

---

## 10. Pitfalls the EspoCRM agent must know

1. **No orders, ever (by design).** Do not model the pipeline around WooCommerce orders. Purchase/won happens offline.
2. **Email is mostly absent.** Three of four forms collect only a phone. Espo dedupe and Meta advanced matching must tolerate email-less leads.
3. **Phone is unnormalized free text.** Normalize before dedupe and before SHA-256 hashing for CAPI.
4. **Consent gate is real.** Complianz blocks marketing scripts until consent; `_fbp` / `_fbc` may be missing. Decide and document the lawful basis for server-side events.
5. **Complianz legal setup is incomplete.** Region was set to EU technically; the owner has not answered the wizard's legal questions. Cookie policy content is not finalized.
6. **Empty core templates.** `theme/page.php` and `theme/archive.php` are **0-byte files** → plain WP pages (including WooCommerce `/cart/`, `/my-account/`) and blog archives currently render an **empty body with HTTP 200**. `search.php` was removed, so `/?s=` falls back to `index.php`. If any integration relies on a WP page rendering (thank-you page, webhook landing, tracking page), create a real template first.
7. **Theme AJAX contract.** `minka_form` returns `wp_send_json_success(['entry' => id])` on success, `422` + message on GF validation failure. `main.js` shows "Спасибо" **only on success** — any CRM/CAPI call added here must not break that promise or block the UI.
8. **Gravity Forms rejects some emails.** GF's built-in spam check rejects `@example.com` addresses — use realistic addresses when testing form 4.
9. **Field mapping is `adminLabel`-based.** If someone renames or reorders GF fields in the admin, the theme's mapping (and any CRM mapping keyed on field ids) breaks. Prefer mapping via `adminLabel`, as the theme does.
10. **Seeded demo data.** 12 demo products, 11 demo posts, 10 test form entries. Clean before go-live; don't treat them as real records.
11. **Local/dev only.** Site runs at `http://127.0.0.1:9000`; mail is PHP `mail()`; no HTTPS, no production domain yet. Outbound HTTP from the WP container to EspoCRM will need to be verified once both run; container-to-container networking is not configured today (EspoCRM is not present in `docker-compose.yml`).
12. **HPOS is enabled** in WooCommerce — if orders are ever introduced, read them via `wc_get_orders()` / CRUD, not via `wp_posts` queries.
13. **Currency displayed is USD with 0 decimals** — confirm the real business currency before mapping Opportunity amounts and CAPI `value` / `currency`.
14. **Theme is not version-controlled beyond a single commit**; there is no staging environment. Prefer an mu-plugin for integration code so it is independent of theme edits.

---

## 11. Summary status board

**ALREADY EXISTS:** catalog site on WP + Woo (catalog-only), 4 lead forms wired to Gravity Forms via a custom AJAX handler, GF entries + admin notifications, product data with SKU/price/attributes, Complianz consent with a custom banner and readable consent state, AJAX search/filters/favorites, `/wp-json/` available.

**NOT IMPLEMENTED:** any analytics or tracking whatsoever (Pixel, GA/GTM, dataLayer), UTM/fbclid/_fbp/_fbc capture, event_id/dedup, CRM connection of any kind, custom REST endpoints, machine auth for the WP API, customer-facing emails, SMTP, orders/checkout, `page.php` / `archive.php` templates.

**PLANNED (per the brief, not started):** EspoCRM as a separate app; WP → Espo REST push of Leads/Contacts/Opportunities; Meta Pixel browser-side (Lead) and Meta CAPI server-side from WP and/or EspoCRM (Lead, Qualified Lead, Purchase) with `event_id` deduplication and attribution carried on the Lead record.
