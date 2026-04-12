# Changelog

All notable changes to this extension are documented here. The format
is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/).

## [1.0.0] — Initial release

### Added
- **Engine-agnostic product search** via `Magento\Catalog\Model\Layer\Resolver`
  — automatically uses whichever engine the merchant has configured
  (Elasticsearch 7, Elasticsearch 8, OpenSearch, or MySQL fallback).
- **Direct SKU LIKE fallback** so customers can find products by typing
  literal SKU codes (`MJ12`, `24-WB04`).
- **Dynamic catalog vocabulary** built from the merchant's own product
  names — substring containment, Levenshtein typo tolerance, and
  metaphone phonetic matching for any catalog vocabulary, no hard-coded
  synonyms.
- **Category search** by name AND description (two-pass design).
- **CMS page search** by title, meta keywords, meta description,
  content heading, identifier, AND full page body content.
- **Popular searches** chip row driven by `search_query`.
- **Recent searches** in localStorage.
- **Highlighted match marks** on every result.
- **Dedicated cache type** `panth_search_autocomplete` with auto-
  invalidation by `cat_p` / `cat_c` / `cms_p` tags.
- **9 security layers**: form_key, same-origin, X-Requested-With, UA
  blocklist, honeypot, sliding-window per-IP rate limit, length bounds,
  POST body cap, method whitelist.
- Response headers: `Cache-Control: private, no-store`,
  `X-Content-Type-Options: nosniff`, `X-Robots-Tag: noindex`,
  `Referrer-Policy: same-origin`.
- **Hyva theme** template (vanilla JS, no Alpine timing issues, sized
  via Tailwind container).
- **Luma theme** template — dual-mode bootstrap. If Panth_ThemeCustomizer
  is enabled, the autocomplete attaches to its existing search bar; if
  not, the standalone trigger + popup overlay activates automatically.
- **Cmd / Ctrl + K** keyboard shortcut.
- **Mobile-first design** — bottom-up overlay, 16px input font, big
  touch targets.
- **Admin configuration** page with every knob (limits, debounce,
  cache TTL, all 9 security toggles).
- **In-admin documentation** page (Stores → Panth Infotech → Search
  Autocomplete → Documentation).
- Magento admin **Search Synonyms** integration — the engine respects
  merchant-configured synonyms automatically with no code changes.
- **Auto-extends to any custom attribute** the merchant marks
  Searchable=Yes — no code change required.

### Compatibility
- Magento Open Source / Commerce / Cloud 2.4.4 → 2.4.8
- PHP 8.1, 8.2, 8.3, 8.4
- Elasticsearch 7, Elasticsearch 8, OpenSearch, MySQL fallback
- Hyva theme (any version) and Luma theme (any version)

---

## Support

For all questions, bug reports, or feature requests:

- **Email:** kishansavaliyakb@gmail.com
- **Website:** https://kishansavaliya.com
- **WhatsApp:** +91 84012 70422
