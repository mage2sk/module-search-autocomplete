<!-- SEO Meta -->
<!--
  Title: Magento 2 Search Autocomplete Extension: AJAX Instant Search, SKU Search & Bot Protection | Hyva + Luma | Panth Infotech
  Description: Panth Search Autocomplete adds a fast AJAX dropdown to the Magento 2 search box. Shows products, categories, CMS pages, and popular searches. Engine-agnostic (Elasticsearch 7/8, OpenSearch, MySQL). Includes 9 bot protection layers, a dedicated cache type, full keyboard navigation, and native Hyva + Luma templates. Built by Top Rated Plus Magento developer Kishan Savaliya.
  Keywords: magento 2 search autocomplete, magento 2 instant search, ajax search magento 2, magento 2 product suggestions, hyva search autocomplete, luma search autocomplete, magento 2 sku search, elasticsearch autocomplete magento, opensearch magento 2, magento 2 live search extension
  Author: Kishan Savaliya (Panth Infotech)
  Canonical: https://kishansavaliya.com/magento-2-search-autocomplete.html
-->

# Magento 2 Search Autocomplete Extension: AJAX Instant Search, SKU Search, Bot Protection (Hyva + Luma)

[![Magento 2.4.4 - 2.4.8](https://img.shields.io/badge/Magento-2.4.4%20--%202.4.8-orange?logo=magento&logoColor=white)](https://magento.com)
[![PHP 8.1 - 8.4](https://img.shields.io/badge/PHP-8.1%20--%208.4-blue?logo=php&logoColor=white)](https://php.net)
[![Hyva + Luma](https://img.shields.io/badge/Themes-Hyva%20%2B%20Luma-14b8a6)](https://www.hyva.io)
[![Live Demo & Details](https://img.shields.io/badge/Live%20Demo%20%26%20Details-magento--2--search--autocomplete-0D9488?style=flat)](https://kishansavaliya.com/magento-2-search-autocomplete.html)
[![Packagist](https://img.shields.io/badge/Packagist-mage2kishan%2Fmodule--search--autocomplete-orange?logo=packagist&logoColor=white)](https://packagist.org/packages/mage2kishan/module-search-autocomplete)
[![Upwork Top Rated Plus](https://img.shields.io/badge/Upwork-Top%20Rated%20Plus-14a800?logo=upwork&logoColor=white)](https://www.upwork.com/freelancers/~016dd1767321100e21)
[![Website](https://img.shields.io/badge/Website-kishansavaliya.com-0D9488)](https://kishansavaliya.com)

> **Give shoppers instant product suggestions as they type.** Panth Search Autocomplete replaces the stock Magento search box with a fast AJAX dropdown that shows products (with images and prices), categories, CMS pages, and popular searches. It works with any Magento search engine, blocks bots with 9 configurable security layers, caches results in a dedicated cache type, and ships with native templates for **Hyva (Alpine.js)** and **Luma**.

**Product page:** [kishansavaliya.com/magento-2-search-autocomplete.html](https://kishansavaliya.com/magento-2-search-autocomplete.html)

---

## Quick Answer

**What is Panth Search Autocomplete?** It is a Magento 2 search autocomplete extension that shows an AJAX dropdown with product suggestions, category links, CMS page links, and popular searches as shoppers type, without a full page reload.

**What does it add to my store?**

- An **AJAX autocomplete dropdown** on the search box, showing products with images and prices, category links, CMS page links, and popular searches.
- **SKU search** so B2B customers can find products by typing a full or partial SKU code.
- **9 bot protection layers** including form key validation, per-IP rate limiting, honeypot fields, user-agent blocking, and same-origin checks.
- A **dedicated cache type** (`panth_search_autocomplete`) that auto-invalidates when products, categories, or CMS pages change.
- **Full keyboard navigation** with arrow keys, Enter, and Escape, plus ARIA-accessible markup.

**Which themes are supported?** Both **Hyva** (Alpine.js, no jQuery) and **Luma** (KnockoutJS). The right template is picked for you based on the active theme.

**What does it need?** Magento 2.4.4 to 2.4.8, PHP 8.1 to 8.4, and the free `mage2kishan/module-core` package. Works with Elasticsearch 7/8, OpenSearch, and the MySQL fallback search engine.

---

## Need Custom Magento 2 Development?

> **Get a free quote for your project in 24 hours** for custom modules, Hyva themes, performance work, M1 to M2 migrations, and Adobe Commerce Cloud.

<p align="center">
  <a href="https://kishansavaliya.com/get-quote">
    <img src="https://img.shields.io/badge/Get%20a%20Free%20Quote%20%E2%86%92-Reply%20within%2024%20hours-DC2626?style=for-the-badge" alt="Get a Free Quote" />
  </a>
</p>

<table>
<tr>
<td width="50%" align="center">

### Kishan Savaliya
**Top Rated Plus on Upwork**

[![Hire on Upwork](https://img.shields.io/badge/Hire%20on%20Upwork-Top%20Rated%20Plus-14a800?style=for-the-badge&logo=upwork&logoColor=white)](https://www.upwork.com/freelancers/~016dd1767321100e21)

100% Job Success • 10+ Years Magento Experience
Adobe Certified • Hyva Specialist

</td>
<td width="50%" align="center">

### Panth Infotech Agency
**Magento Development Team**

[![Visit Agency](https://img.shields.io/badge/Visit%20Agency-Panth%20Infotech-14a800?style=for-the-badge&logo=upwork&logoColor=white)](https://www.upwork.com/agencies/1881421506131960778/)

Custom Modules • Theme Design • Migrations
Performance • SEO • Adobe Commerce Cloud

</td>
</tr>
</table>

**Visit our website:** [kishansavaliya.com](https://kishansavaliya.com) &nbsp;|&nbsp; **Get a quote:** [kishansavaliya.com/get-quote](https://kishansavaliya.com/get-quote)

---

## Table of Contents

- [Who Is It For](#who-is-it-for)
- [Key Features](#key-features)
- [Compatibility](#compatibility)
- [Installation](#installation)
- [Configuration](#configuration)
- [How It Works](#how-it-works)
- [Bot and Abuse Protection](#bot-and-abuse-protection)
- [Performance and Caching](#performance-and-caching)
- [FAQ](#faq)
- [Support](#support)
- [About Panth Infotech](#about-panth-infotech)
- [Quick Links](#quick-links)

---

## Who Is It For

- **Stores with large catalogs** where shoppers type a product name or SKU and expect instant results, not a full page reload.
- **B2B and wholesale stores** where customers know the SKU and want to find the product immediately.
- **Hyva storefronts** that need a search dropdown built with Alpine.js, without adding jQuery or Knockout back.
- **High-traffic stores** that want to protect the search endpoint from bots, scrapers, and rate abuse.
- **Merchants running Elasticsearch or OpenSearch** who want autocomplete that respects their configured search engine and any synonyms they have set up.

---

## Key Features

### Instant AJAX Dropdown
- **Live results as you type** using debounced AJAX requests (default 200ms) that fire only after the minimum query length is met.
- **Products with images and prices** shown per hit, including name, SKU, and short description.
- **SKU search** for exact and partial SKU matches, important for B2B and trade customers.
- **Description matching** searches short and long descriptions, not just the product name.
- **Category suggestions** surface matching category links so shoppers can jump to a listing page.
- **CMS page suggestions** show matching pages inside the dropdown.
- **Popular searches** display trending queries when the input is empty or too short.
- **Recent searches** in localStorage so the shopper sees their own history.

### Merchant Controls
- **Configurable result limits** for each section: products, categories, CMS pages, and popular searches independently.
- **Min and max query length** to prevent single-character fishing queries and oversized payloads.
- **Debounce interval** so you can tune between responsiveness and server load.
- **Toggle each section** on or off (products, categories, CMS pages, popular searches) per store view.
- **Toggle product images and prices** in the dropdown independently.
- **Engine-agnostic** so it uses whatever search engine Magento is configured with.

### Bot and Abuse Protection
- **9 configurable security layers** covering form key validation, per-IP rate limiting, honeypot fields, empty user-agent blocking, bot user-agent pattern blocking, X-Requested-With header requirement, same-origin validation, POST body size cap, and query length bounds.
- **All layers are on by default** and can be tuned in the admin without code changes.

### Performance and Caching
- **Dedicated cache type** `panth_search_autocomplete` listed in Admin under System / Cache Management.
- **Auto-invalidation** when a product, category, or CMS page is saved or when config changes.
- **Typical response time** under 5ms on cache hit.
- **Assets lazy-loaded** when the search input is focused, so there is no weight on initial page load.

### Hyva + Luma Ready
- **Native Hyva templates** using Alpine.js, with no jQuery, RequireJS, or Knockout.
- **Native Luma templates** using KnockoutJS and standard Magento UI components.
- **Full keyboard navigation** with arrow keys, Enter, and Escape plus ARIA-accessible markup and screen reader support.
- **Mobile-first** with a bottom-up overlay on small screens, 16px input font, and large touch targets.

### Built to Last
- **Engine-agnostic backend** works with Elasticsearch 7, Elasticsearch 8, OpenSearch, and the MySQL fallback, with no extra setup.
- **Respects Magento admin Search Synonyms** automatically, no code changes needed.
- **Extends to any custom attribute** marked Searchable = Yes, no code change required.
- **Clean, MEQP-style code** with constructor dependency injection only and no ObjectManager.
- **Translation ready**, every label uses Magento's `__()` function.

---

## Compatibility

| Requirement | Versions Supported |
|---|---|
| Magento Open Source | 2.4.4, 2.4.5, 2.4.6, 2.4.7, 2.4.8 |
| Adobe Commerce | 2.4.4, 2.4.5, 2.4.6, 2.4.7, 2.4.8 |
| Adobe Commerce Cloud | 2.4.4 to 2.4.8 |
| PHP | 8.1.x, 8.2.x, 8.3.x, 8.4.x |
| Search Engine | Elasticsearch 7.x, 8.x, OpenSearch 1.x/2.x, MySQL fallback |
| Hyva Theme | 1.0+ (Alpine.js powered dropdown) |
| Luma Theme | Native support |
| Required Dependency | `mage2kishan/module-core` (free) |

---

## Installation

### Composer Installation (Recommended)

```bash
composer require mage2kishan/module-search-autocomplete
bin/magento module:enable Panth_Core Panth_SearchAutocomplete
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento setup:static-content:deploy -f
bin/magento cache:flush
```

### Manual Installation via ZIP

1. Download the latest release from [Packagist](https://packagist.org/packages/mage2kishan/module-search-autocomplete) or from the [product page](https://kishansavaliya.com/magento-2-search-autocomplete.html).
2. Extract it to `app/code/Panth/SearchAutocomplete/` in your Magento install.
3. Make sure `Panth_Core` is installed too (required dependency).
4. Run the commands above starting from `bin/magento module:enable`.

### Verify Installation

```bash
bin/magento module:status Panth_SearchAutocomplete
# Expected: Module is enabled
```

After install, open:
```
Admin -> Stores -> Configuration -> Panth Extensions -> Search Autocomplete
```

---

## Configuration

Go to **Stores -> Configuration -> Panth Extensions -> Search Autocomplete**.

| Setting | Group | Default | Description |
|---|---|---|---|
| Enable Module | General | Yes | Master toggle for the autocomplete dropdown. |
| Minimum Query Length | General | 2 | Queries shorter than this never reach the backend. |
| Maximum Query Length | General | 64 | Hard cap to prevent oversized query payloads. |
| Input Debounce (ms) | General | 200 | Lower means more requests; higher means a laggier experience. |
| Max Products | Result Sections | 8 | Number of product hits shown in the dropdown. |
| Max Categories | Result Sections | 4 | Number of matching categories shown. |
| Max CMS Pages | Result Sections | 3 | Number of matching CMS pages shown. |
| Max Popular Searches | Result Sections | 5 | Number of trending queries shown when input is short. |
| Show Product Images | Result Sections | Yes | Toggle product thumbnails in the dropdown. |
| Show Product Prices | Result Sections | Yes | Toggle prices in the dropdown. |
| Show Categories Section | Result Sections | Yes | Toggle the entire categories block. |
| Show CMS Pages Section | Result Sections | Yes | Toggle the entire CMS pages block. |
| Show Popular Searches Section | Result Sections | Yes | Toggle the popular searches block. |
| Enable Result Cache | Caching | Yes | Turns on the `panth_search_autocomplete` cache type. |
| Cache TTL (seconds) | Caching | 300 | Auto-invalidated when products, categories, or CMS pages change. |
| Require Form Key | Bot and Abuse Prevention | Yes | Rejects requests without a valid Magento form_key. |
| Rate Limit (req / min / IP) | Bot and Abuse Prevention | 60 | Cache-served responses do not count against the limit. |
| Block Empty User-Agent | Bot and Abuse Prevention | Yes | Blocks requests with no user-agent header. |
| Block Bot User-Agent Patterns | Bot and Abuse Prevention | Yes | Blocks curl, wget, python-requests, scrapy, headlesschrome, and similar. |
| Enable Honeypot Field | Bot and Abuse Prevention | Yes | Hidden input that bots fill in but real shoppers cannot see. |
| Require X-Requested-With Header | Bot and Abuse Prevention | Yes | Rejects direct browser navigation to the JSON endpoint. |
| Require Same-Origin Request | Bot and Abuse Prevention | Yes | Validates Origin / Referer against the store base URL. |
| Max POST Body (bytes) | Bot and Abuse Prevention | 4096 | Hard cap on POST body size. |

---

## How It Works

1. A shopper types in the search box.
2. JavaScript debounces input (configurable, default 200ms) and checks the minimum length.
3. An AJAX request is sent to `/panth_searchautocomplete/ajax/index` along with the form key.
4. The controller validates the form key, checks the per-IP rate limit, and runs the bot protection layers.
5. The cache layer is checked. If there is a hit, the response comes back in under 5ms.
6. On a cache miss, the module queries Magento's configured search engine across product name, SKU, short description, and description fields, then assembles category and CMS page results.
7. The JSON response is cached and rendered into the dropdown by Alpine.js on Hyva or KnockoutJS on Luma.
8. Each result section (products, categories, CMS pages, popular searches) respects its own limit and visibility setting.

---

## Bot and Abuse Protection

Search endpoints are a common target for scrapers and automated abuse. Panth Search Autocomplete ships with 9 defensive layers, all enabled by default and all configurable from the admin:

1. **Form key validation** rejects requests without a valid Magento session form_key.
2. **Per-IP rate limiting** counts requests per minute using Magento cache. Cache-served responses do not count.
3. **Query length bounds** block single-character fishing queries and oversized payloads.
4. **POST body size cap** rejects payloads over the configured byte limit (default 4096 bytes).
5. **Honeypot field** is a hidden input that bots fill in but real shoppers do not interact with.
6. **Empty user-agent blocking** drops requests with no UA header.
7. **Bot user-agent blocking** matches patterns like curl, wget, python-requests, scrapy, headlesschrome, and phantomjs.
8. **X-Requested-With header requirement** blocks direct browser navigation to the JSON endpoint. Both the Hyva and Luma clients send this header automatically.
9. **Same-origin validation** checks the Origin and Referer header against the store base URL to block cross-site enumeration.

---

## FAQ

### Does search autocomplete work on Hyva themes?
Yes. Panth Search Autocomplete ships native Alpine.js templates for Hyva, with no jQuery, Knockout, or RequireJS. The module detects the active theme through `Panth_Core` and serves the correct template.

### Which search engines are supported?
All of them. The module is engine-agnostic and uses whatever Magento is configured with: Elasticsearch 7, Elasticsearch 8, OpenSearch 1.x/2.x, or the MySQL fallback. No extra setup is needed.

### Can I search by SKU?
Yes. SKU is a first-class search field. Both exact and partial SKU matches are returned, which is useful for B2B and wholesale stores where customers know the product code.

### Can I control what sections appear in the dropdown?
Yes. The Result Sections group in configuration lets you toggle products, categories, CMS pages, and popular searches on or off, and set an independent result limit for each section.

### Will it slow down my category pages?
No. The dropdown assets are lazy-loaded when the search input is focused, not on initial page render. Cached responses return in under 5ms and do not re-hit the search engine.

### How does the rate limit work?
Requests are counted per IP per minute using Magento's default cache. Cache-served responses do not count against the limit, so frequent identical queries from a real shopper stay fast. Abusive bots are blocked with a 429 response.

### Does it work with Magento admin Search Synonyms?
Yes. The module queries Magento's configured search engine, so any synonyms configured in Admin under Marketing / Search Synonyms are respected automatically.

### Does it work in multi-store setups?
Yes. All settings respect Magento's standard scope order of default, website, and store view. Each store view can have its own limits, debounce interval, and visibility toggles.

### Does Panth Search Autocomplete need Panth Core?
Yes. `mage2kishan/module-core` is a free required dependency that Composer installs for you automatically.

---

## Support

| Channel | Contact |
|---|---|
| Product Page | [kishansavaliya.com/magento-2-search-autocomplete.html](https://kishansavaliya.com/magento-2-search-autocomplete.html) |
| Email | kishansavaliyakb@gmail.com |
| Website | [kishansavaliya.com](https://kishansavaliya.com) |
| WhatsApp | +91 84012 70422 |
| GitHub Issues | [github.com/mage2sk/module-search-autocomplete/issues](https://github.com/mage2sk/module-search-autocomplete/issues) |
| Upwork (Top Rated Plus) | [Hire Kishan Savaliya](https://www.upwork.com/freelancers/~016dd1767321100e21) |
| Upwork Agency | [Panth Infotech](https://www.upwork.com/agencies/1881421506131960778/) |

Response time: 1-2 business days.

### Need Custom Magento Development?

Looking for **custom Magento module development**, **Hyva theme work**, **store migrations**, or **performance tuning**? Get a free quote in 24 hours:

<p align="center">
  <a href="https://kishansavaliya.com/get-quote">
    <img src="https://img.shields.io/badge/%F0%9F%92%AC%20Get%20a%20Free%20Quote-kishansavaliya.com%2Fget--quote-DC2626?style=for-the-badge" alt="Get a Free Quote" />
  </a>
</p>

<p align="center">
  <a href="https://www.upwork.com/freelancers/~016dd1767321100e21">
    <img src="https://img.shields.io/badge/Hire%20Kishan-Top%20Rated%20Plus-14a800?style=for-the-badge&logo=upwork&logoColor=white" alt="Hire on Upwork" />
  </a>
  &nbsp;&nbsp;
  <a href="https://www.upwork.com/agencies/1881421506131960778/">
    <img src="https://img.shields.io/badge/Visit-Panth%20Infotech%20Agency-14a800?style=for-the-badge&logo=upwork&logoColor=white" alt="Visit Agency" />
  </a>
  &nbsp;&nbsp;
  <a href="https://kishansavaliya.com/magento-2-search-autocomplete.html">
    <img src="https://img.shields.io/badge/View%20Product%20Page-magento--2--search--autocomplete-0D9488?style=for-the-badge" alt="View Product Page" />
  </a>
</p>

---

## About Panth Infotech

Built and maintained by **Kishan Savaliya** ([kishansavaliya.com](https://kishansavaliya.com)), a **Top Rated Plus** Magento developer on Upwork with 10+ years of eCommerce experience.

**Panth Infotech** is a Magento 2 development agency that builds high quality, security focused extensions and themes for both Hyva and Luma storefronts. The extension suite covers SEO, performance, checkout, product presentation, customer engagement, and store management, with each module built to MEQP standards and tested across Magento 2.4.4 to 2.4.8.

Browse the full extension catalog on our [Magento extensions page](https://kishansavaliya.com/magento-extensions.html) or on [Packagist](https://packagist.org/packages/mage2kishan/).

---

## Quick Links

| Resource | Link |
|---|---|
| **Product Page** | [magento-2-search-autocomplete.html](https://kishansavaliya.com/magento-2-search-autocomplete.html) |
| **Packagist** | [mage2kishan/module-search-autocomplete](https://packagist.org/packages/mage2kishan/module-search-autocomplete) |
| **GitHub** | [mage2sk/module-search-autocomplete](https://github.com/mage2sk/module-search-autocomplete) |
| **Website** | [kishansavaliya.com](https://kishansavaliya.com) |
| **Free Quote** | [kishansavaliya.com/get-quote](https://kishansavaliya.com/get-quote) |
| **Upwork (Top Rated Plus)** | [Hire Kishan Savaliya](https://www.upwork.com/freelancers/~016dd1767321100e21) |
| **Upwork Agency** | [Panth Infotech](https://www.upwork.com/agencies/1881421506131960778/) |
| **Email** | kishansavaliyakb@gmail.com |
| **WhatsApp** | +91 84012 70422 |

---

<p align="center">
  <strong>Ready to give shoppers faster search results?</strong><br/>
  <a href="https://kishansavaliya.com/magento-2-search-autocomplete.html">
    <img src="https://img.shields.io/badge/%F0%9F%9A%80%20See%20Search%20Autocomplete%20%E2%86%92-Product%20Page%20%26%20Details-DC2626?style=for-the-badge" alt="See Search Autocomplete" />
  </a>
</p>

---

**SEO Keywords:** magento 2 search autocomplete, magento 2 search autocomplete extension, magento 2 instant search, magento 2 ajax search, magento 2 product suggestions, magento 2 search dropdown, magento 2 live search extension, magento 2 sku search, magento 2 search by description, hyva search autocomplete, hyva instant search, luma search autocomplete, magento 2 elasticsearch autocomplete, magento 2 opensearch autocomplete, magento 2 search bot protection, magento 2 search rate limiting, magento 2 search cache, magento 2 category suggestions, magento 2 cms page search, magento 2 popular searches, magento 2 recent searches, ajax search magento 2.4.8, php 8.4 magento search, engine agnostic magento search, magento 2 search performance, mage2kishan search autocomplete, panth search autocomplete, panth infotech, kishan savaliya magento, hire magento developer, top rated plus upwork, custom magento 2 search module
