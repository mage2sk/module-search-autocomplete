<?php
declare(strict_types=1);

namespace Panth\SearchAutocomplete\Model\Security;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Store\Model\StoreManagerInterface;
use Panth\SearchAutocomplete\Helper\Config;

/**
 * Stateless request validator for the autocomplete endpoint.
 *
 * Layered checks (cheapest first, fail fast):
 *  1. Method whitelist        (GET / POST only)
 *  2. User-Agent presence
 *  3. User-Agent bot blocklist
 *  4. Honeypot field empty
 *  5. Form key validity (rotates per session)
 *  6. Query length bounds + sanitisation
 *
 * Returns the sanitised query string on success, null on rejection.
 */
class RequestValidator
{
    /**
     * Substrings (case-insensitive) that flag a User-Agent as bot/script.
     * Kept tight: false positives here would block legitimate users.
     */
    private const BOT_UA_NEEDLES = [
        'curl/',
        'wget/',
        'python-requests',
        'python-urllib',
        'go-http-client',
        'okhttp',
        'java/',
        'apache-httpclient',
        'libwww-perl',
        'scrapy',
        'phantomjs',
        'headlesschrome',
        'puppeteer',
        'playwright',
        'masscan',
        'nikto',
        'sqlmap',
        'nmap',
        'zgrab',
        'feroxbuster',
        'dirbuster',
        'gobuster',
    ];

    /** Honeypot input name — must remain empty in real submissions. */
    public const HONEYPOT_FIELD = 'website';

    private Config $config;
    private FormKeyValidator $formKeyValidator;
    private StoreManagerInterface $storeManager;

    public function __construct(
        Config $config,
        FormKeyValidator $formKeyValidator,
        StoreManagerInterface $storeManager
    ) {
        $this->config = $config;
        $this->formKeyValidator = $formKeyValidator;
        $this->storeManager = $storeManager;
    }

    /**
     * Run every check and return the sanitised query, or null if rejected.
     */
    public function validate(RequestInterface $request): ?string
    {
        // Method check.
        $method = strtoupper((string) $request->getMethod());
        if ($method !== 'GET' && $method !== 'POST') {
            return null;
        }

        // POST body size cap — autocomplete payloads are tiny, anything
        // larger is either a misconfigured client or a probe.
        if ($method === 'POST') {
            try {
                $body = (string) $request->getContent();
                if (strlen($body) > $this->config->getMaxBodyBytes()) {
                    return null;
                }
            } catch (\Throwable $e) {
                return null;
            }
        }

        // User-Agent gate.
        $ua = trim((string) $request->getHeader('User-Agent'));
        if ($this->config->blockEmptyUserAgent() && $ua === '') {
            return null;
        }
        if ($this->config->blockBotUserAgent() && $ua !== '' && $this->isBotUserAgent($ua)) {
            return null;
        }

        // X-Requested-With gate — both bundled clients send "XMLHttpRequest";
        // direct curl-from-the-address-bar attacks do not.
        if ($this->config->requireAjaxHeader()) {
            $xrw = strtolower((string) $request->getHeader('X-Requested-With'));
            if ($xrw !== 'xmlhttprequest') {
                return null;
            }
        }

        // Same-origin gate — Origin OR Referer must match the store base URL
        // host. Blocks cross-site scraping and CSRF replay attempts.
        if ($this->config->requireSameOrigin() && !$this->isSameOrigin($request)) {
            return null;
        }

        // Honeypot — bots fill every field, humans never see this one.
        if ($this->config->isHoneypotEnabled()) {
            $honey = (string) $request->getParam(self::HONEYPOT_FIELD, '');
            if ($honey !== '') {
                return null;
            }
        }

        // Form key — rejects requests that did not come from a Magento page.
        if ($this->config->requireFormKey() && !$this->formKeyValidator->validate($request)) {
            return null;
        }

        // Query sanitisation + length bounds.
        $raw = (string) $request->getParam('q', '');
        $query = $this->sanitiseQuery($raw);
        $len = mb_strlen($query);
        if ($len < $this->config->getMinQueryLength() || $len > $this->config->getMaxQueryLength()) {
            return null;
        }

        return $query;
    }

    /**
     * Compares Origin / Referer header host against the configured store
     * base URL host. Returns true if EITHER header matches, OR if NEITHER
     * header is present (some legitimate clients omit Referer).
     */
    private function isSameOrigin(RequestInterface $request): bool
    {
        try {
            $base = parse_url((string) $this->storeManager->getStore()->getBaseUrl());
            $expectedHost = $base['host'] ?? '';
        } catch (\Throwable $e) {
            return true;
        }
        if ($expectedHost === '') {
            return true;
        }
        $origin = (string) $request->getHeader('Origin');
        $referer = (string) $request->getHeader('Referer');
        if ($origin === '' && $referer === '') {
            // Some browsers / clients legitimately omit both. Don't block.
            return true;
        }
        foreach ([$origin, $referer] as $headerVal) {
            if ($headerVal === '') {
                continue;
            }
            $parsed = parse_url($headerVal);
            $host = $parsed['host'] ?? '';
            if ($host !== '' && strcasecmp($host, $expectedHost) === 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * Strip control bytes, collapse whitespace, normalise unicode.
     */
    public function sanitiseQuery(string $raw): string
    {
        // Strip C0/C1 control bytes including NUL.
        $clean = preg_replace('/[\x00-\x1F\x7F]/u', ' ', $raw) ?? '';
        // Drop angle brackets and quotes that the JSON encoder would
        // escape anyway — they have no business in a search term.
        $clean = str_replace(['<', '>', '"', "'", '`'], ' ', $clean);
        // Collapse whitespace.
        $clean = preg_replace('/\s+/u', ' ', $clean) ?? '';
        return trim($clean);
    }

    private function isBotUserAgent(string $ua): bool
    {
        $low = strtolower($ua);
        foreach (self::BOT_UA_NEEDLES as $needle) {
            if (strpos($low, $needle) !== false) {
                return true;
            }
        }
        return false;
    }
}
