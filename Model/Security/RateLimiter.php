<?php
declare(strict_types=1);

namespace Panth\SearchAutocomplete\Model\Security;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\RequestInterface;
use Panth\SearchAutocomplete\Helper\Config;

/**
 * Per-IP sliding-window rate limiter backed by the framework cache.
 *
 * Keeps a small list of recent request timestamps in cache and trims
 * anything older than 60 seconds. Honest users hit cache so this stays
 * cheap; abusers either share an IP and get throttled together or
 * burn unique IPs (which is the point of CAPTCHA layers above us).
 *
 * Storage key:  panth_sac_rl_<sha1(ip|ua|store)>
 * Storage form: implode(',', timestamps)  -- 60 chars per minute @ 60req/min
 */
class RateLimiter
{
    private const KEY_PREFIX = 'panth_sac_rl_';
    private const WINDOW = 60;

    private CacheInterface $cache;
    private Config $config;

    public function __construct(CacheInterface $cache, Config $config)
    {
        $this->cache = $cache;
        $this->config = $config;
    }

    /**
     * Returns true if the request is within the rate-limit budget.
     * Bumps the counter as a side effect.
     */
    public function allow(RequestInterface $request, int $storeId): bool
    {
        $limit = $this->config->getRateLimitPerMinute();
        if ($limit <= 0) {
            return true;
        }
        $key = $this->key($request, $storeId);
        $now = time();
        $raw = (string) $this->cache->load($key);
        $timestamps = $raw === '' ? [] : array_map('intval', explode(',', $raw));
        // Drop entries outside the window.
        $cutoff = $now - self::WINDOW;
        $timestamps = array_values(array_filter($timestamps, static fn($t) => $t > $cutoff));
        if (count($timestamps) >= $limit) {
            // Persist the trimmed list (no new entry) so subsequent calls
            // still see the throttle.
            $this->cache->save(implode(',', $timestamps), $key, [], self::WINDOW);
            return false;
        }
        $timestamps[] = $now;
        $this->cache->save(implode(',', $timestamps), $key, [], self::WINDOW);
        return true;
    }

    private function key(RequestInterface $request, int $storeId): string
    {
        $ip = (string) $request->getServer('REMOTE_ADDR');
        $ua = substr((string) $request->getHeader('User-Agent'), 0, 80);
        return self::KEY_PREFIX . sha1($ip . '|' . $ua . '|' . $storeId);
    }
}
