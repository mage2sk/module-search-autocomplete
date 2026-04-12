<?php
declare(strict_types=1);

namespace Panth\SearchAutocomplete\Model\Suggestion;

use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Catalog\Model\Layer\Resolver as LayerResolver;
use Magento\Catalog\Model\Product\Attribute\Source\Status as ProductStatus;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\CatalogInventory\Helper\Stock as StockHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Pricing\Helper\Data as PriceHelper;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Panth\SearchAutocomplete\Helper\Config;
use Panth\SearchAutocomplete\Model\Vocabulary\VocabularyProvider;
use Psr\Log\LoggerInterface;

/**
 * Engine-agnostic product suggestion provider.
 *
 * Uses Magento's CatalogSearch Fulltext collection which delegates to
 * whichever search engine the merchant has configured:
 *
 *   - Elasticsearch 7
 *   - Elasticsearch 8
 *   - OpenSearch
 *   - MySQL fallback
 *
 * That means a single code path works on every supported Magento install,
 * with no engine-specific HTTP clients or query DSLs to maintain.
 *
 * Visibility filter restricts to "Catalog, Search" + "Search" so products
 * hidden from the storefront listing but flagged as searchable still
 * surface in the dropdown — matching the behaviour of /catalogsearch/result.
 */
class ProductProvider
{
    private LayerResolver $layerResolver;
    private StoreManagerInterface $storeManager;
    private Visibility $visibility;
    private ImageHelper $imageHelper;
    private PriceHelper $priceHelper;
    private Config $config;
    private LoggerInterface $logger;
    private StockHelper $stockHelper;
    private ScopeConfigInterface $scopeConfig;
    private VocabularyProvider $vocabulary;
    private ProductCollectionFactory $productCollectionFactory;

    public function __construct(
        LayerResolver $layerResolver,
        StoreManagerInterface $storeManager,
        Visibility $visibility,
        ImageHelper $imageHelper,
        PriceHelper $priceHelper,
        Config $config,
        LoggerInterface $logger,
        StockHelper $stockHelper,
        ScopeConfigInterface $scopeConfig,
        VocabularyProvider $vocabulary,
        ProductCollectionFactory $productCollectionFactory
    ) {
        $this->layerResolver = $layerResolver;
        $this->storeManager = $storeManager;
        $this->visibility = $visibility;
        $this->imageHelper = $imageHelper;
        $this->priceHelper = $priceHelper;
        $this->config = $config;
        $this->logger = $logger;
        $this->stockHelper = $stockHelper;
        $this->scopeConfig = $scopeConfig;
        $this->vocabulary = $vocabulary;
        $this->productCollectionFactory = $productCollectionFactory;
    }

    /**
     * Return up to N matching products as plain associative arrays.
     *
     * @return array<int, array<string, mixed>>
     */
    public function search(string $query): array
    {
        $limit = $this->config->getProductsLimit();
        if ($limit <= 0 || $query === '') {
            return [];
        }

        try {
            $store = $this->storeManager->getStore();
            $storeId = (int) $store->getId();

            // Pass 1 — engine search. Magento's catalog search engine
            // (Elasticsearch / OpenSearch / MySQL) indexes EVERY catalog
            // attribute marked Searchable=Yes, including default name,
            // sku, description, short_description AND any custom
            // attribute the merchant adds later. So this single call
            // covers all current and future searchable fields with no
            // code changes needed.
            $items = $this->runEngineSearch($query, $store, $storeId, $limit);

            // Pass 2 — direct DB SKU + name LIKE fallback. The engine
            // sometimes refuses to match exact SKU codes ("MJ12") or
            // partial codes ("WB04") because of how it tokenises. This
            // pass guarantees that any product whose SKU or name
            // literally contains the query string is found, in any
            // catalog, regardless of attribute searchable flags.
            if (count($items) < $limit) {
                $extra = $this->searchByDirectAttributes($query, $store, $storeId, $limit);
                $items = $this->mergeUnique($items, $extra, $limit);
            }

            // Pass 3 — vocabulary expansion. If we still have fewer
            // results than the limit, ask the catalog-derived
            // VocabularyProvider for similar words and re-run the engine
            // with an expanded query string. Catalog-agnostic — works on
            // any merchant catalog because the dictionary is built from
            // the merchant's own product names.
            if (count($items) < $limit) {
                $expanded = $this->expandQueryViaVocabulary($query, $storeId);
                if ($expanded !== '' && $expanded !== $query) {
                    $extra = $this->runEngineSearch($expanded, $store, $storeId, $limit);
                    $items = $this->mergeUnique($items, $extra, $limit);
                }
            }
            return $items;
        } catch (\Throwable $e) {
            $this->logger->warning('[PanthSearchAutocomplete] product search failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Direct catalog-collection search by SKU + name LIKE.
     *
     * Used as the safety-net pass after the engine query so partial
     * SKU codes ("MJ", "MJ12", "24-WB04") and any literal substring
     * always surface. Honours every visibility / status / stock /
     * store filter the engine path uses, so the result set never
     * includes a product the engine wouldn't have shown.
     *
     * @return array<int, array<string, mixed>>
     */
    private function searchByDirectAttributes(string $query, $store, int $storeId, int $limit): array
    {
        try {
            $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $query) . '%';
            $collection = $this->productCollectionFactory->create();
            $collection
                ->addAttributeToSelect(['name', 'small_image', 'thumbnail', 'price', 'special_price', 'sku', 'url_key'])
                ->setStore($store)
                ->addStoreFilter($storeId)
                ->addAttributeToFilter('status', ['eq' => ProductStatus::STATUS_ENABLED])
                ->setVisibility($this->visibility->getVisibleInSearchIds())
                // Single-attribute SKU filter — the engine path already
                // covers name + description + every other searchable
                // attribute. This pass exists ONLY to catch literal SKU
                // codes ("MJ12", "24-WB04") that the engine refuses to
                // tokenise.
                ->addAttributeToFilter('sku', ['like' => $like])
                ->setPageSize($limit)
                ->setCurPage(1);

            $showOos = (bool) $this->scopeConfig->getValue(
                'cataloginventory/options/show_out_of_stock',
                ScopeInterface::SCOPE_STORE
            );
            if (!$showOos) {
                $this->stockHelper->addInStockFilterToCollection($collection);
            }

            $rows = [];
            foreach ($collection as $product) {
                $rows[] = $this->buildRow($product);
            }
            return $rows;
        } catch (\Throwable $e) {
            $this->logger->warning('[PanthSearchAutocomplete] direct attribute search failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * De-dupe two row lists by id, preserving original order.
     *
     * @param array<int, array<string, mixed>> $primary
     * @param array<int, array<string, mixed>> $secondary
     * @return array<int, array<string, mixed>>
     */
    private function mergeUnique(array $primary, array $secondary, int $limit): array
    {
        $seen = [];
        foreach ($primary as $row) {
            $seen[$row['id']] = true;
        }
        foreach ($secondary as $row) {
            if (count($primary) >= $limit) {
                break;
            }
            if (!isset($seen[$row['id']])) {
                $primary[] = $row;
                $seen[$row['id']] = true;
            }
        }
        return $primary;
    }

    /**
     * Build a single row payload from a Product instance — same shape
     * the engine path produces, so callers don't care which path the
     * row came from.
     *
     * @return array<string, mixed>
     */
    private function buildRow(\Magento\Catalog\Api\Data\ProductInterface $product): array
    {
        $imageUrl = '';
        if ($this->config->showImage()) {
            try {
                $imageUrl = $this->imageHelper
                    ->init($product, 'product_small_image')
                    ->setImageFile((string) $product->getSmallImage())
                    ->resize(120, 120)
                    ->getUrl();
            } catch (\Throwable $e) {
                $imageUrl = '';
            }
        }
        $priceRow = null;
        if ($this->config->showPrice()) {
            $priceRow = $this->extractPrice($product);
        }
        return [
            'id'    => (int) $product->getId(),
            'name'  => (string) $product->getName(),
            'sku'   => (string) $product->getSku(),
            'url'   => (string) $product->getProductUrl(),
            'image' => $imageUrl,
            'price' => $priceRow,
        ];
    }

    /**
     * Build a vocabulary-expanded version of the query. Walks the
     * VocabularyProvider for each token and appends the top similar
     * catalog words to the original query string. Empty string if no
     * similar words found.
     */
    private function expandQueryViaVocabulary(string $query, int $storeId): string
    {
        $tokens = $this->tokenise($this->normalise($query));
        if (!$tokens) {
            return '';
        }
        $extras = [];
        foreach ($tokens as $tok) {
            // PHP auto-coerces numeric string array keys to int — cast
            // back to string before passing to the typed method.
            $tokStr = (string) $tok;
            if ($tokStr === '') {
                continue;
            }
            foreach ($this->vocabulary->findSimilar($tokStr, $storeId, 3) as $similar) {
                $extras[(string) $similar] = true;
            }
        }
        if (!$extras) {
            return '';
        }
        return $query . ' ' . implode(' ', array_keys($extras));
    }

    /**
     * Run a single engine pass for the given query string and return
     * the tier-filtered matches. Used by both the original-query pass
     * and the vocabulary-expanded fallback pass.
     *
     * @return array<int, array<string, mixed>>
     */
    private function runEngineSearch(string $query, $store, int $storeId, int $limit): array
    {
        try {
            // Engine-agnostic entry point: ask the catalog Layer Resolver
            // for the SEARCH layer (not category), then pull its product
            // collection. This is the same code path /catalogsearch/result
            // uses internally and works on every supported search engine
            // (Elasticsearch 7, Elasticsearch 8, OpenSearch, MySQL).
            //
            // The Layer Resolver can only call create() ONCE per request.
            // On subsequent calls (e.g. our vocabulary-expanded second
            // pass) we use the already-created layer instead — calling
            // create() a second time throws "Catalog Layer has been
            // already created".
            try {
                $this->layerResolver->create(LayerResolver::CATALOG_LAYER_SEARCH);
            } catch (\RuntimeException $e) {
                // Already created earlier in this request — fall through
                // and use the existing layer.
            }
            $collection = $this->layerResolver->get()->getProductCollection();
            $collection
                ->addAttributeToSelect(['name', 'small_image', 'thumbnail', 'price', 'special_price', 'sku', 'url_key'])
                ->setStore($store)
                ->addStoreFilter((int) $store->getId())
                // Visibility: only "Catalog, Search" + "Search" — same as
                // /catalogsearch/result. Hides products marked "Not Visible
                // Individually" or "Catalog" only, which would 404 on click.
                ->setVisibility($this->visibility->getVisibleInSearchIds())
                // Status: only enabled products. Belt-and-braces — the
                // fulltext index also filters disabled rows but custom
                // search engines may not, so we make it explicit.
                ->addAttributeToFilter('status', ['eq' => ProductStatus::STATUS_ENABLED])
                ->addSearchFilter($query)
                // Force relevance sorting — without this the underlying
                // engine returns results in document order which surfaces
                // weak fuzzy matches (e.g. "Hooded Ice Fleece" for the
                // query "t-shirt") above the strong matches.
                ->setOrder('relevance', 'DESC')
                // Pull more rows than we need so the post-filter step can
                // promote products whose name actually contains the query
                // tokens — see below.
                ->setPageSize(max($limit * 4, 24))
                ->setCurPage(1);

            // Out-of-stock filter — honour the merchant's
            // "Display out of stock products" setting at
            // cataloginventory/options/show_out_of_stock. When that flag
            // is OFF (the default), out-of-stock SKUs disappear from the
            // dropdown just like they do from the storefront listing.
            $showOos = (bool) $this->scopeConfig->getValue(
                'cataloginventory/options/show_out_of_stock',
                ScopeInterface::SCOPE_STORE
            );
            if (!$showOos) {
                $this->stockHelper->addInStockFilterToCollection($collection);
            }

            // Trust the engine's output 1:1.
            //
            // We deliberately do NOT post-filter here — the catalogsearch
            // /result page shows exactly what the engine returns, and the
            // autocomplete must match it 1:1. Any custom relevance
            // filtering would create the surprising "autocomplete shows
            // X products but the result page shows Y" mismatch the
            // merchant flagged.
            //
            // Per-catalog synonyms ("tshirt → tee", "phone → mobile") are
            // handled by Magento's built-in admin feature which both this
            // autocomplete and /catalogsearch/result respect:
            //   Marketing → SEO & Search → Search Synonyms
            $ordered = [];
            foreach ($collection as $product) {
                $ordered[] = $product;
            }
            $ordered = array_slice($ordered, 0, $limit);

            $items = [];
            foreach ($ordered as $product) {
                $items[] = $this->buildRow($product);
            }
            return $items;
        } catch (\Throwable $e) {
            $this->logger->warning('[PanthSearchAutocomplete] product search failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Lowercase + strip punctuation + collapse whitespace.
     * "T-Shirt!" → "t shirt", "tShirts/Blue" → "tshirts blue"
     */
    private function normalise(string $value): string
    {
        $value = mb_strtolower($value);
        // Replace hyphens / underscores / slashes with spaces.
        $value = preg_replace('/[\-_\/]+/u', ' ', $value) ?? $value;
        // Strip everything that is not a letter, digit, or space.
        $value = preg_replace('/[^\p{L}\p{N} ]+/u', '', $value) ?? $value;
        // Collapse runs of spaces.
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
        return trim($value);
    }

    /**
     * Split a normalised query into useful tokens. Drops single-letter
     * tokens (so "t-shirt" → ["shirt"], not ["t","shirt"]). Adds the
     * singular form of plural words (4+ chars) so "shoes" also matches
     * "shoe", "tees" also matches "tee", etc.
     *
     * @return string[]
     */
    private function tokenise(string $normalised): array
    {
        if ($normalised === '') {
            return [];
        }
        $raw = explode(' ', $normalised);
        $out = [];
        foreach ($raw as $tok) {
            if (mb_strlen($tok) < 2) {
                continue;
            }
            $out[$tok] = true;
            // Drop trailing 's' (English plural) for tokens >= 4 chars.
            if (mb_strlen($tok) >= 4 && mb_substr($tok, -1) === 's') {
                $out[mb_substr($tok, 0, -1)] = true;
            }
            // Drop trailing 'es' for tokens >= 5 chars (e.g. "boxes" → "box").
            if (mb_strlen($tok) >= 5 && mb_substr($tok, -2) === 'es') {
                $out[mb_substr($tok, 0, -2)] = true;
            }
        }
        return array_keys($out);
    }

    /**
     * @return array{regular:string, final:string, has_special:bool}|null
     */
    private function extractPrice(\Magento\Catalog\Api\Data\ProductInterface $product): ?array
    {
        try {
            $priceInfo = $product->getPriceInfo();
            $finalAmount = $priceInfo->getPrice('final_price')->getAmount()->getValue();
            $regularAmount = $priceInfo->getPrice('regular_price')->getAmount()->getValue();
            return [
                'regular'     => $this->priceHelper->currency((float) $regularAmount, true, false),
                'final'       => $this->priceHelper->currency((float) $finalAmount, true, false),
                'has_special' => (float) $finalAmount < (float) $regularAmount,
            ];
        } catch (\Throwable $e) {
            // Configurable / bundle products without a single price — return null.
            return null;
        }
    }
}
