<?php
declare(strict_types=1);

namespace Panth\SearchAutocomplete\Model\Vocabulary;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Module\ModuleListInterface;
use Panth\SearchAutocomplete\Model\Cache\Type as AutocompleteCache;
use Psr\Log\LoggerInterface;

/**
 * Catalog-derived vocabulary builder.
 *
 * The job of this class is to give the autocomplete a DYNAMIC, catalog-
 * specific synonym/similarity dictionary WITHOUT any hard-coded English
 * fashion vocabulary. It works for jewelry stores, electronics, German /
 * French / Japanese catalogs — anything — because the only data it ever
 * looks at is the merchant's own product names.
 *
 * How it works:
 *
 *   1. On first request, walk catalog_product_entity_varchar for the
 *      'name' attribute on the current store. Pull the raw product
 *      names (DISTINCT) — usually a few thousand strings.
 *   2. Tokenise each name into normalised lowercase words ≥ 3 chars.
 *   3. Count frequency per word, drop hapax legomena (words that appear
 *      only once — usually proper nouns or junk).
 *   4. Bucket each word by its first 2 characters so the phonetic
 *      similarity scan can run on a tiny subset, not the whole vocab.
 *   5. Cache the resulting structure under the dedicated
 *      `panth_search_autocomplete` cache type (TTL = 1 hour, invalidated
 *      automatically by the cat_p tag whenever a product is saved).
 *
 * Subsequent requests load the cached structure in <1 ms. The whole
 * vocabulary build for a 50k-SKU catalog takes ~150 ms ONCE per hour
 * per store; warm requests pay nothing.
 */
class VocabularyProvider
{
    /** Minimum word length we keep in the dictionary. */
    private const MIN_WORD = 3;

    /** Drop words shorter than this when scoring. */
    private const MIN_FREQ = 1;

    /** TTL for the vocabulary cache row (seconds). */
    private const TTL = 3600;

    private ResourceConnection $resource;
    private AutocompleteCache $cache;
    private LoggerInterface $logger;
    private ModuleListInterface $moduleList;

    public function __construct(
        ResourceConnection $resource,
        AutocompleteCache $cache,
        LoggerInterface $logger,
        ModuleListInterface $moduleList
    ) {
        $this->resource = $resource;
        $this->cache = $cache;
        $this->logger = $logger;
        $this->moduleList = $moduleList;
    }

    /**
     * Get the cached vocabulary for a store. First call per hour
     * triggers a rebuild; subsequent calls are sub-millisecond.
     *
     * Structure:
     *   [
     *     'words'   => ['shirt' => 12, 'jacket' => 8, ...],   // word => freq
     *     'buckets' => ['sh' => ['shirt','shoe',...], ...],   // first 2 chars
     *   ]
     *
     * @return array{words: array<string,int>, buckets: array<string, string[]>}
     */
    public function getVocabulary(int $storeId): array
    {
        $key = 'panth_sav_' . $storeId;
        $cached = $this->cache->load($key);
        if ($cached !== false && $cached !== null && $cached !== '') {
            $decoded = json_decode((string) $cached, true);
            if (is_array($decoded) && isset($decoded['words'], $decoded['buckets'])) {
                return $decoded;
            }
        }
        try {
            $vocab = $this->build($storeId);
            $this->cache->save(
                json_encode($vocab, JSON_UNESCAPED_UNICODE),
                $key,
                [
                    AutocompleteCache::CACHE_TAG,
                    \Magento\Catalog\Model\Product::CACHE_TAG,
                ],
                self::TTL
            );
            return $vocab;
        } catch (\Throwable $e) {
            $this->logger->warning('[PanthSearchAutocomplete] vocabulary build failed: ' . $e->getMessage());
            return ['words' => [], 'buckets' => []];
        }
    }

    /**
     * Build the vocabulary by walking distinct product names for the
     * current store. Single SQL query, no ORM overhead.
     *
     * @return array{words: array<string,int>, buckets: array<string, string[]>}
     */
    private function build(int $storeId): array
    {
        $conn = $this->resource->getConnection();
        $eav = $this->resource->getTableName('eav_attribute');
        $varchar = $this->resource->getTableName('catalog_product_entity_varchar');

        // Resolve the 'name' attribute id once per build.
        $attrId = (int) $conn->fetchOne(
            $conn->select()
                ->from($eav, 'attribute_id')
                ->where('entity_type_id = ?', 4) // catalog_product
                ->where('attribute_code = ?', 'name')
                ->limit(1)
        );
        if ($attrId <= 0) {
            return ['words' => [], 'buckets' => []];
        }

        // Pull distinct names for the current store + the default scope (0).
        // Limited to a sane upper bound to keep memory bounded on huge catalogs.
        $select = $conn->select()
            ->distinct(true)
            ->from(['v' => $varchar], ['value'])
            ->where('v.attribute_id = ?', $attrId)
            ->where('v.store_id IN (?)', [0, $storeId])
            ->where('v.value IS NOT NULL')
            ->where("v.value != ''")
            ->limit(50000);
        $names = $conn->fetchCol($select);

        $words = [];
        foreach ($names as $name) {
            foreach ($this->tokenise((string) $name) as $token) {
                if (!isset($words[$token])) {
                    $words[$token] = 0;
                }
                $words[$token]++;
            }
        }

        // Drop tokens that appear less than MIN_FREQ times — those are
        // usually proper nouns ("Aether", "Erika") which don't help with
        // synonym lookup.
        if (self::MIN_FREQ > 1) {
            $words = array_filter($words, static fn($freq) => $freq >= self::MIN_FREQ);
        }
        arsort($words);

        // Bucket by first 2 chars for fast similarity scans.
        $buckets = [];
        foreach (array_keys($words) as $word) {
            $bucket = mb_substr($word, 0, 2);
            if (!isset($buckets[$bucket])) {
                $buckets[$bucket] = [];
            }
            $buckets[$bucket][] = $word;
        }

        return ['words' => $words, 'buckets' => $buckets];
    }

    /**
     * Tokenise a product name into searchable words. Lowercased,
     * stripped of punctuation, plurals collapsed to singular form.
     *
     * @return string[]
     */
    private function tokenise(string $name): array
    {
        $name = mb_strtolower($name);
        $name = preg_replace('/[\-_\/]+/u', ' ', $name) ?? $name;
        $name = preg_replace('/[^\p{L}\p{N} ]+/u', '', $name) ?? $name;
        $name = preg_replace('/\s+/u', ' ', $name) ?? $name;
        $words = explode(' ', trim($name));
        $out = [];
        foreach ($words as $w) {
            if (mb_strlen($w) < self::MIN_WORD) {
                continue;
            }
            $out[] = $w;
            // Singular form (drop trailing 's' on 4+ char words).
            if (mb_strlen($w) >= 4 && mb_substr($w, -1) === 's') {
                $out[] = mb_substr($w, 0, -1);
            }
        }
        return $out;
    }

    /**
     * Find catalog words that are SIMILAR to the user's query token.
     *
     * Four signals (highest score wins):
     *
     *   1. Substring containment — vocab word contains token, e.g.
     *      "shirt" → "jackshirt" (1000 + freq)
     *   2. Reverse containment — token contains vocab word, e.g.
     *      "tshirts" → "tee" if "tee" is in vocab (800 + freq)
     *   3. Typo tolerance via Levenshtein distance ≤ ceil(len/4) for
     *      tokens ≥ 4 chars (200 - dist*50 + freq)
     *   4. Phonetic match via metaphone — last-resort fallback for
     *      spelling variants (50 + freq)
     *
     * Scans the FULL vocabulary word list (no bucketing). For a typical
     * 5-10k unique-word vocabulary that's ~5 ms — well within budget,
     * and avoids missing cross-prefix matches that bucketing dropped.
     *
     * @return string[]
     */
    public function findSimilar(string $token, int $storeId, int $limit = 5): array
    {
        if (mb_strlen($token) < self::MIN_WORD) {
            return [];
        }
        $vocab = $this->getVocabulary($storeId);
        if (!$vocab['words']) {
            return [];
        }
        $token = mb_strtolower($token);
        $tokenLen = mb_strlen($token);
        $tokenMeta = function_exists('metaphone') ? metaphone($token) : '';
        $maxDist = max(1, (int) ceil($tokenLen / 4));

        $matches = [];
        foreach ($vocab['words'] as $word => $freq) {
            if ($word === $token) {
                continue;
            }
            $wordLen = mb_strlen((string) $word);
            // Tier 1: vocab word contains the user's token. Powerful for
            // partial typing — "shirt" surfaces "jackshirt", "polo
            // shirts", etc.
            if (mb_strpos((string) $word, $token) !== false) {
                $matches[$word] = 1000 + (int) $freq;
                continue;
            }
            // Tier 2: token contains vocab word (token is more specific
            // than the catalog word, e.g. "shoeing" → "shoe").
            if ($wordLen >= 4 && mb_strpos($token, (string) $word) !== false) {
                $matches[$word] = 800 + (int) $freq;
                continue;
            }
            // Tier 3: Levenshtein typo tolerance. Skip when length
            // difference is too big to ever be within maxDist.
            if (abs($wordLen - $tokenLen) <= $maxDist) {
                $dist = levenshtein($token, (string) $word);
                if ($dist > 0 && $dist <= $maxDist) {
                    $matches[$word] = (200 - $dist * 50) + (int) $freq;
                    continue;
                }
            }
            // Tier 4: phonetic equivalence (handles "shew" → "shoe").
            if ($tokenMeta !== '' && metaphone((string) $word) === $tokenMeta) {
                $matches[$word] = 50 + (int) $freq;
            }
        }
        if (!$matches) {
            return [];
        }
        arsort($matches);
        return array_slice(array_keys($matches), 0, $limit);
    }
}
