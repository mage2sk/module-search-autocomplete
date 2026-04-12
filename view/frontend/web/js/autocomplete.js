/*!
 * Panth Search Autocomplete — Luma vanilla-JS bundle.
 *
 * Same logic as the Hyva Alpine.js component but written as a small
 * IIFE so it works on every Magento storefront with zero RequireJS /
 * jQuery dependency. Reads its config from the data-psac-config attr
 * (a JSON blob the ViewModel renders).
 *
 * Public surface: window.PanthSearchAutocomplete (one instance per
 * .psac-wrap on the page).
 */
(function () {
    'use strict';

    function $(sel, root) { return (root || document).querySelector(sel); }
    function escapeHtml(s) {
        return String(s).replace(/[&<>"']/g, function (c) {
            return { '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c];
        });
    }
    function escapeRegExp(s) { return s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'); }
    function highlight(text, query) {
        var safe = escapeHtml(text || '');
        if (!query) return safe;
        var re = new RegExp('(' + escapeRegExp(query) + ')', 'ig');
        return safe.replace(re, '<mark class="psac-hl">$1</mark>');
    }
    function debounce(fn, ms) {
        var t;
        return function () {
            var args = arguments, ctx = this;
            clearTimeout(t);
            t = setTimeout(function () { fn.apply(ctx, args); }, ms);
        };
    }

    function PanthSearchAutocomplete(root) {
        var cfg;
        try { cfg = JSON.parse(root.getAttribute('data-psac-config') || '{}'); }
        catch (e) { cfg = {}; }
        if (!cfg.endpoint) { return; }

        var input    = $('[data-psac-input]', root);
        var clearBtn = $('[data-psac-clear]', root);
        var dropdown = $('[data-psac-dropdown]', root);
        var loading  = $('[data-psac-loading]', root);
        var form     = $('[data-psac-form]', root);
        var formKey  = (root.querySelector('input[name="form_key"]') || {}).value || '';
        var honeypot = (root.querySelector('input[name="' + cfg.honeypotName + '"]') || {}).value || '';
        if (!input || !dropdown) { return; }

        var state = {
            query: '',
            products: [], categories: [], pages: [], popular: [], recent: [],
            activeIdx: -1, loading: false, open: false,
            cache: new Map(), controller: null,
        };
        try {
            state.recent = (JSON.parse(localStorage.getItem('psac_recent') || '[]') || []).slice(0, 6);
        } catch (e) { state.recent = []; }

        function hasResults() { return (state.products.length + state.categories.length + state.pages.length) > 0; }
        function hasQuery()   { return state.query.length >= cfg.minLength; }

        function open()  { state.open = true;  dropdown.hidden = false; }
        function close() { state.open = false; dropdown.hidden = true; state.activeIdx = -1; render(); }

        function clearAll() {
            input.value = ''; state.query = '';
            state.products = []; state.categories = []; state.pages = [];
            state.activeIdx = -1;
            updateClearBtn(); render(); input.focus();
        }
        function updateClearBtn() {
            if (clearBtn) clearBtn.hidden = !state.query;
        }

        function fetch(q, popularOnly) {
            var key = (popularOnly ? 'pop:' : 'q:') + q;
            if (state.cache.has(key)) { apply(state.cache.get(key), popularOnly); return; }
            if (state.controller) { try { state.controller.abort(); } catch (e) {} }
            state.controller = new AbortController();
            if (!popularOnly) {
                state.loading = true;
                if (loading) loading.hidden = false;
            }
            var url = new URL(cfg.endpoint, window.location.origin);
            url.searchParams.set('q', popularOnly ? '  '.padEnd(cfg.minLength, ' ') : q);
            url.searchParams.set('form_key', formKey);
            url.searchParams.set(cfg.honeypotName, '');
            window.fetch(url.toString(), {
                method: 'GET',
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                signal: state.controller.signal,
            }).then(function (r) { return r.ok ? r.json() : Promise.reject(r); })
              .then(function (data) { state.cache.set(key, data); apply(data, popularOnly); })
              .catch(function () { /* aborted or net error — ignore */ })
              .finally(function () {
                  state.loading = false;
                  if (loading) loading.hidden = true;
                  render();
              });
        }
        function apply(data, popularOnly) {
            if (popularOnly) { state.popular = data.popular || []; render(); return; }
            state.products   = data.products   || [];
            state.categories = data.categories || [];
            state.pages      = data.pages      || [];
            state.popular    = data.popular    || [];
            state.activeIdx  = -1;
            open(); render();
        }

        function flatHrefs() {
            var arr = [];
            state.products.forEach(function (p) { arr.push(p.url); });
            state.categories.forEach(function (c) { arr.push(c.url); });
            state.pages.forEach(function (p) { arr.push(p.url); });
            return arr;
        }

        function render() {
            if (!state.open) { dropdown.hidden = true; return; }
            dropdown.hidden = false;
            var html = '<div class="psac-scroll">';

            // Recent (no query yet)
            if (!state.query && state.recent.length) {
                html += '<div class="psac-section-heading">' + escapeHtml(cfg.i18n.recent) + '</div>';
                html += '<div class="psac-popular-wrap">';
                state.recent.forEach(function (term) {
                    html += '<button type="button" class="psac-popular-tag" data-psac-pick="' + escapeHtml(term) + '">'
                          + '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8v4l3 3M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'
                          + '<span>' + escapeHtml(term) + '</span>'
                          + '</button>';
                });
                html += '</div>';
            }

            // Popular
            if (state.popular.length && cfg.showPopular) {
                html += '<div class="psac-section-heading">' + escapeHtml(cfg.i18n.popular) + '</div>';
                html += '<div class="psac-popular-wrap">';
                state.popular.forEach(function (row) {
                    html += '<button type="button" class="psac-popular-tag" data-psac-pick="' + escapeHtml(row.text) + '">'
                          + '<svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M13.5.67s.74 2.65.74 4.8c0 2.06-1.35 3.73-3.41 3.73-2.07 0-3.63-1.67-3.63-3.73l.03-.36C5.21 7.51 4 10.62 4 14c0 4.42 3.58 8 8 8s8-3.58 8-8C20 8.61 17.41 3.8 13.5.67z"/></svg>'
                          + '<span>' + escapeHtml(row.text) + '</span>'
                          + '</button>';
                });
                html += '</div>';
            }

            // Loading state
            if (state.loading && !hasResults()) {
                html += '<div class="psac-empty">'
                      + '<svg width="42" height="42" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10" stroke-opacity=".2"/><path d="M12 2a10 10 0 0110 10" stroke-linecap="round"><animateTransform attributeName="transform" type="rotate" from="0 12 12" to="360 12 12" dur="1s" repeatCount="indefinite"/></path></svg>'
                      + '<p>' + escapeHtml(cfg.i18n.searching) + '</p>'
                      + '</div>';
            }

            // Products
            if (state.products.length) {
                html += '<div class="psac-section-heading">' + escapeHtml(cfg.i18n.products) + '</div>';
                state.products.forEach(function (p, idx) {
                    var active = state.activeIdx === idx ? ' is-active' : '';
                    html += '<a class="psac-row' + active + '" href="' + escapeHtml(p.url) + '">';
                    if (cfg.showImage) {
                        html += '<div class="psac-product-image">';
                        if (p.image) {
                            html += '<img src="' + escapeHtml(p.image) + '" alt="' + escapeHtml(p.name) + '" width="56" height="56" loading="lazy"/>';
                        } else {
                            html += '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="color:#d4d4d4"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="9" cy="9" r="2"/><path d="M21 15l-5-5L5 21"/></svg>';
                        }
                        html += '</div>';
                    }
                    html += '<div class="psac-product-info">';
                    html += '<span class="psac-product-name">' + highlight(p.name, state.query) + '</span>';
                    if (p.sku) html += '<span class="psac-product-sku">' + escapeHtml(p.sku) + '</span>';
                    if (cfg.showPrice && p.price) {
                        html += '<span class="psac-product-price">'
                              + '<span>' + escapeHtml(p.price.final) + '</span>';
                        if (p.price.has_special) {
                            html += '<span class="psac-old">' + escapeHtml(p.price.regular) + '</span>';
                        }
                        html += '</span>';
                    }
                    html += '</div></a>';
                });
            }

            // Categories
            if (state.categories.length && cfg.showCategories) {
                html += '<div class="psac-section-heading">' + escapeHtml(cfg.i18n.categories) + '</div>';
                state.categories.forEach(function (c) {
                    html += '<a class="psac-cat-row" href="' + escapeHtml(c.url) + '">'
                          + '<span class="psac-cat-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7h18M3 12h18M3 17h18"/></svg></span>'
                          + '<span class="psac-cat-name">' + highlight(c.name, state.query) + '</span>';
                    if (c.count > 0) {
                        html += '<span class="psac-cat-count">' + c.count + ' items</span>';
                    }
                    html += '</a>';
                });
            }

            // CMS pages
            if (state.pages.length && cfg.showPages) {
                html += '<div class="psac-section-heading">' + escapeHtml(cfg.i18n.pages) + '</div>';
                state.pages.forEach(function (p) {
                    html += '<a class="psac-page-row" href="' + escapeHtml(p.url) + '">'
                          + '<span class="psac-page-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6"/></svg></span>'
                          + '<span class="psac-page-name">' + highlight(p.title, state.query) + '</span>'
                          + '</a>';
                });
            }

            // Empty state
            if (!state.loading && hasQuery() && !hasResults()) {
                html += '<div class="psac-empty">'
                      + '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35M8 11h6"/></svg>'
                      + '<p>' + escapeHtml(cfg.i18n.noResults) + '</p>'
                      + '</div>';
            }

            html += '</div>'; // /scroll

            if (hasQuery() && hasResults()) {
                var viewAll = cfg.viewAllUrl + '?q=' + encodeURIComponent(state.query);
                var label = cfg.i18n.viewAll.replace('%1', escapeHtml(state.query));
                html += '<a class="psac-view-all" href="' + escapeHtml(viewAll) + '">'
                      + '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>'
                      + '<span>' + label + '</span>'
                      + '</a>';
            }

            dropdown.innerHTML = html;

            // wire popular-tag picks
            dropdown.querySelectorAll('[data-psac-pick]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var term = btn.getAttribute('data-psac-pick') || '';
                    input.value = term;
                    state.query = term;
                    updateClearBtn();
                    fetch(term);
                    input.focus();
                });
            });
        }

        function saveRecent(term) {
            if (!term) return;
            var list = state.recent.filter(function (t) { return t !== term; });
            list.unshift(term);
            state.recent = list.slice(0, 6);
            try { localStorage.setItem('psac_recent', JSON.stringify(state.recent)); } catch (e) {}
        }

        // Wire events.
        var debounced = debounce(function () {
            state.query = input.value || '';
            updateClearBtn();
            if (state.query.length < cfg.minLength) {
                state.products = []; state.categories = []; state.pages = [];
                open(); render(); return;
            }
            fetch(state.query);
        }, cfg.debounceMs);
        input.addEventListener('input', debounced);
        input.addEventListener('focus', function () {
            open();
            if (!state.query && (state.recent.length || state.popular.length === 0)) {
                fetch('', true);
            }
            render();
        });
        input.addEventListener('keydown', function (e) {
            var hrefs = flatHrefs();
            if (e.key === 'ArrowDown') {
                e.preventDefault(); open();
                if (!hrefs.length) return;
                state.activeIdx = (state.activeIdx + 1) % hrefs.length;
                render();
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                if (!hrefs.length) return;
                state.activeIdx = (state.activeIdx - 1 + hrefs.length) % hrefs.length;
                render();
            } else if (e.key === 'Enter') {
                if (state.activeIdx >= 0 && hrefs[state.activeIdx]) {
                    e.preventDefault();
                    saveRecent(state.query);
                    window.location.href = hrefs[state.activeIdx];
                } else if (state.query) {
                    saveRecent(state.query);
                }
            } else if (e.key === 'Escape') {
                close();
            }
        });
        if (clearBtn) clearBtn.addEventListener('click', clearAll);
        if (form) {
            form.addEventListener('submit', function () {
                if (state.query) saveRecent(state.query);
            });
        }
        document.addEventListener('click', function (e) {
            if (!root.contains(e.target)) close();
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') close();
        });

        // Initial paint with no query so the dropdown is wired up.
        render();
    }

    /**
     * Wire each .psac-trigger-wrap on the page so that:
     *   - clicking the trigger opens the overlay
     *   - clicking the backdrop / close button / pressing Escape closes it
     *   - opening focuses the input and locks page scroll
     *   - closing restores scroll and returns focus to the trigger
     */
    function wireOverlay(wrap) {
        var trigger  = wrap.querySelector('[data-psac-trigger]');
        var overlay  = wrap.querySelector('[data-psac-overlay]');
        var backdrop = wrap.querySelector('[data-psac-backdrop]');
        var closeBtn = wrap.querySelector('[data-psac-close]');
        var input    = wrap.querySelector('[data-psac-input]');
        if (!trigger || !overlay) return;

        function open() {
            overlay.hidden = false;
            trigger.setAttribute('aria-expanded', 'true');
            document.documentElement.style.overflow = 'hidden';
            // Focus the input on next tick so the browser is ready.
            window.setTimeout(function () { if (input) input.focus(); }, 30);
        }
        function close() {
            overlay.hidden = true;
            trigger.setAttribute('aria-expanded', 'false');
            document.documentElement.style.overflow = '';
            try { trigger.focus(); } catch (e) {}
        }

        trigger.addEventListener('click', function (e) {
            e.preventDefault();
            if (overlay.hidden) { open(); } else { close(); }
        });
        if (backdrop) backdrop.addEventListener('click', close);
        if (closeBtn) closeBtn.addEventListener('click', close);
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !overlay.hidden) close();
        });
        // Cmd/Ctrl+K shortcut to open
        document.addEventListener('keydown', function (e) {
            if ((e.ctrlKey || e.metaKey) && (e.key === 'k' || e.key === 'K')) {
                e.preventDefault();
                if (overlay.hidden) open();
            }
        });
    }

    function init() {
        // Dual-mode bootstrap: if Panth_ThemeCustomizer is shipping its
        // own search bar (#panth-search-input is present), the attach
        // script handles autocomplete and we leave our standalone
        // fallback hidden. Otherwise, REVEAL the fallback wrap so the
        // user gets autocomplete on a stock Luma store too.
        var themeCustomizerInput = document.getElementById('panth-search-input');
        document.querySelectorAll('[data-psac-fallback]').forEach(function (wrap) {
            if (!themeCustomizerInput) {
                wrap.style.display = '';
            }
        });

        // Init overlay open/close on every visible trigger wrapper.
        document.querySelectorAll('.psac-trigger-wrap').forEach(function (wrap) {
            if (wrap.dataset.psacOverlayInited === '1') return;
            // Skip wrap that is still hidden (ThemeCustomizer is active).
            if (wrap.style.display === 'none' || getComputedStyle(wrap).display === 'none') return;
            wrap.dataset.psacOverlayInited = '1';
            wireOverlay(wrap);
        });
        // Init the search component on every .psac-wrap (Hyva direct
        // mode + Luma standalone fallback).
        document.querySelectorAll('.psac-wrap').forEach(function (root) {
            if (root.dataset.psacInited === '1') return;
            // If we are in attach mode, skip — the attach script handles
            // wiring autocomplete to ThemeCustomizer's input.
            var inFallback = root.closest('[data-psac-fallback]');
            if (inFallback && themeCustomizerInput) return;
            root.dataset.psacInited = '1';
            try { PanthSearchAutocomplete(root); } catch (e) {
                if (window.console) console.warn('[PanthSearchAutocomplete] init failed', e);
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    window.PanthSearchAutocomplete = { init: init };
})();
