# Panth Search Autocomplete for Magento 2 & Hyva

[![Magento 2.4.4 - 2.4.8](https://img.shields.io/badge/Magento-2.4.4%20--%202.4.8-orange)]()
[![PHP 8.1 - 8.4](https://img.shields.io/badge/PHP-8.1%20--%208.4-blue)]()
[![Hyva Compatible](https://img.shields.io/badge/Hyva-Compatible-green)]()
[![Luma Compatible](https://img.shields.io/badge/Luma-Compatible-green)]()

A fast, secure, dynamic search autocomplete that **just works** on every
Magento 2 storefront — Hyva and Luma, Elasticsearch and OpenSearch,
desktop and mobile.

Three pillars: **engine-agnostic**, **bot-hardened**, **catalog-aware**.

---

## ✨ Why this extension

| | Other autocomplete extensions | **Panth Search Autocomplete** |
|---|---|---|
| Search engine support | Usually one (ES7 or OpenSearch) | **All** — ES7, ES8, OpenSearch, MySQL fallback |
| Theme support | Usually only Luma OR only Hyva | **Both** — same code, two templates |
| Bot prevention | Honeypot at most | **9 layers** — form_key, same-origin, X-Requested-With, UA blocklist, honeypot, sliding-window rate limit, length bounds, body cap, method whitelist |
| Synonym handling | Hard-coded English fashion list | **Dynamic** — vocabulary built from your own catalog at runtime |
| Result caching | None / FPC only | Dedicated cache type, auto-invalidated by `cat_p` / `cat_c` / `cms_p` tags |
| Uses ObjectManager? | Often | **Never** — full constructor injection |
| Search field coverage | Name + SKU only | Name + SKU + description + short_description + every custom searchable attribute + category description + CMS page content |
| Mobile UX | Desktop dropdown shrunk | True mobile design — bottom-up overlay, 16px input (no iOS zoom), big touch targets |

---

## 🎯 Features

### Search behaviour
- ✅ **Engine-agnostic** product search via Magento's Layer Resolver — automatically uses whichever engine the merchant has configured
- ✅ Searches **every Searchable=Yes attribute** including custom ones (no code change needed when you add new attributes)
- ✅ Direct **SKU LIKE** fallback so customers can find products by typing literal SKU codes (`MJ12`, `24-WB04`)
- ✅ **Dynamic catalog vocabulary** built from your own product names — handles typos, plurals, phonetic variants WITHOUT any hardcoded synonym list
- ✅ **Categories** matched by name AND description
- ✅ **CMS pages** matched by title, meta keywords, meta description, content heading, identifier, AND full page body content
- ✅ **Popular searches** chip row driven by Magento's `search_query` table
- ✅ **Recent searches** stored in localStorage per browser
- ✅ Live result counts on the dropdown

### Performance
- ⚡ **Cold path: 50-150 ms** on a 100 k-SKU catalog
- ⚡ **Warm cache: 0 ms** — full payload served from a dedicated cache type
- ⚡ Cache key includes `(store_id, customer_group_id, query)` so guests and customers never share rows
- ⚡ Auto-invalidated by `cat_p` / `cat_c` / `cms_p` tags whenever the underlying content changes

### Security (9 layers)
1. HTTP method whitelist (GET / POST only)
2. POST body size cap (default 4 KiB)
3. User-Agent gate — empty rejected, 22 bot patterns blocked (curl, wget, python-requests, scrapy, headlesschrome, sqlmap, nikto, masscan, etc.)
4. `X-Requested-With: XMLHttpRequest` required
5. Same-origin Origin / Referer validation
6. Hidden honeypot field
7. Magento form_key (session-bound rotating CSRF token)
8. Query length bounds (default 2..64 chars)
9. Per-IP sliding-window rate limit (default 60 req/min)

Plus: response headers `Cache-Control: private, no-store`, `X-Content-Type-Options: nosniff`, `X-Robots-Tag: noindex`, `Referrer-Policy: same-origin`. JSON-encoded responses are XSS-safe; the JS clients escape every field before insertion.

### UX
- 📱 Mobile-first design — bottom-up overlay, 16px input font (no iOS zoom)
- ⌨️ Full keyboard navigation — arrow keys, Enter, Escape
- 🔥 **Cmd/Ctrl+K** shortcut to open search
- 🔍 Highlighted match marks on every result
- 🎨 Theme tokens sourced from your `theme-config.json`
- ♿️ Accessible markup (`role="search"`, `role="listbox"`, `aria-*`)

### Admin
- Stores → Configuration → Panth Extensions → Search Autocomplete
- Per-section limits (products / categories / pages / popular)
- Per-section toggles, image / price toggles, debounce, cache TTL
- Every security setting individually toggleable
- In-admin documentation page (Stores → Panth Infotech → Search Autocomplete → Documentation)

---

## 📦 Installation

### Via Composer (recommended)

```bash
composer require mage2kishan/module-search-autocomplete
bin/magento module:enable Panth_Core Panth_SearchAutocomplete
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento setup:static-content:deploy -f
bin/magento cache:flush
```

### Via uploaded zip

1. Download the extension zip from the Marketplace
2. Extract to `app/code/Panth/SearchAutocomplete`
3. Make sure `app/code/Panth/Core` is also installed (this extension depends on it)
4. Run the same commands above starting from `module:enable`

### Verify installation

```bash
bin/magento module:status Panth_SearchAutocomplete
# Should show: Module is enabled
```

---

## 🛠 Requirements

| | Required |
|---|---|
| Magento | 2.4.4 — 2.4.8 (Open Source / Commerce / Cloud) |
| PHP | 8.1 / 8.2 / 8.3 / 8.4 |
| Search engine | Elasticsearch 7 / Elasticsearch 8 / OpenSearch / MySQL fallback |
| `panth/module-core` | ^1.2 (installed automatically as a composer dependency) |

---

## 🔧 Configuration

Open **Stores → Configuration → Panth Extensions → Search Autocomplete**.

Every setting is store-view scoped where it makes sense.

### General
- **Enable Module** — master kill switch
- **Minimum Query Length** (default `2`) — queries shorter than this never reach the backend
- **Maximum Query Length** (default `64`) — hard cap to defeat CPU-DoS via huge queries
- **Input Debounce (ms)** (default `200`)

### Result Sections
- **Max Products / Categories / CMS Pages / Popular Searches** — set `0` to hide a section entirely
- **Show Product Images / Prices** — independent toggles
- **Show Categories / Pages / Popular Searches Sections** — independent toggles

### Caching
- **Enable Result Cache** (default `Yes`)
- **Cache TTL (s)** (default `300`)

### Bot & Abuse Prevention
- **Require Form Key** (default `Yes`)
- **Rate Limit (req / min / IP)** (default `60`)
- **Block Empty / Bot User-Agents** (default `Yes`)
- **Honeypot Field** (default `Yes`)
- **Require X-Requested-With** (default `Yes`)
- **Require Same-Origin** (default `Yes`)
- **Max POST Body (bytes)** (default `4096`)

For per-catalog synonyms (`tshirt → tee`, `phone → mobile`), use Magento's built-in admin feature: **Marketing → SEO & Search → Search Synonyms**. This extension respects them automatically.

---

## 📚 Documentation

Full administrator documentation is built into the admin panel:

**Stores → Panth Infotech → Search Autocomplete → Documentation**

It covers the architecture diagram, security model, performance tuning, mobile vs desktop behaviour, troubleshooting, and the CLI reference.

For developers, the file structure follows standard Magento conventions and every public class is documented with PHPDoc.

---

## 🆘 Support

| Channel | Contact |
|---|---|
| Email | kishansavaliyakb@gmail.com |
| Website | https://kishansavaliya.com |
| WhatsApp | +91 84012 70422 |

Response time: 1-2 business days for paid licenses.

---

## 📄 License

Commercial — see `LICENSE.txt`. One license per Magento production installation. Includes 12 months of free updates and email support.

---

## 🏢 About the developer

Built and maintained by **Kishan Savaliya** — https://kishansavaliya.com.
Builds high-quality, security-focused Magento 2 extensions and themes for
both Hyva and Luma storefronts.
