<?php
declare(strict_types=1);

namespace Panth\SearchAutocomplete\Model\Suggestion;

use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Panth\SearchAutocomplete\Helper\Config;
use Psr\Log\LoggerInterface;

/**
 * Category suggestion provider.
 *
 * Searches the category collection by name LIKE on the current store.
 * Joins product_count for the "(N items)" hint shown in the dropdown.
 * Caching at the controller level means each query only hits the DB once
 * per (store, customer-group, query) combination per TTL window.
 */
class CategoryProvider
{
    private CollectionFactory $collectionFactory;
    private StoreManagerInterface $storeManager;
    private Config $config;
    private LoggerInterface $logger;

    public function __construct(
        CollectionFactory $collectionFactory,
        StoreManagerInterface $storeManager,
        Config $config,
        LoggerInterface $logger
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->storeManager = $storeManager;
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function search(string $query): array
    {
        $limit = $this->config->getCategoriesLimit();
        if ($limit <= 0 || $query === '' || !$this->config->showCategories()) {
            return [];
        }
        try {
            $store = $this->storeManager->getStore();
            $rootCategoryId = (int) $store->getRootCategoryId();
            $like = '%' . $this->escapeLike($query) . '%';

            // Pass 1 — primary filter on `name`. The vast majority of
            // category lookups are by category name, and a single-
            // attribute filter is the most reliable across Magento
            // versions and EAV join setups.
            $collection = $this->collectionFactory->create();
            $collection
                ->addAttributeToSelect(['name', 'description', 'url_key', 'url_path', 'is_active', 'include_in_menu'])
                ->addAttributeToFilter('is_active', ['eq' => 1])
                ->addAttributeToFilter('path', ['like' => '1/' . $rootCategoryId . '/%'])
                ->addAttributeToFilter('entity_id', ['neq' => $rootCategoryId])
                ->addAttributeToFilter('name', ['like' => $like])
                ->setStore($store)
                ->addUrlRewriteToResult()
                ->setPageSize($limit * 2)
                ->setCurPage(1);

            // Try to surface a product count when available without forcing
            // a slow JOIN — Magento exposes joinUrlRewrite + joinField helpers
            // that work on any storage backend.
            try {
                $collection->joinField(
                    'product_count',
                    'catalog_category_product',
                    'COUNT(product_id)',
                    'category_id = entity_id',
                    null,
                    'left',
                    'group'
                );
            } catch (\Throwable $e) {
                // Best-effort — count is decorative, never fatal.
            }

            $rows = $this->extractRows($collection, $query, $rootCategoryId, $limit);

            // Pass 2 — fallback search on `description` when pass 1 came
            // up short. Some merchants put rich descriptions on category
            // pages with the brand keywords / promo copy, and customers
            // type those words too. Single-attribute filter again so the
            // SQL stays simple and the EAV joins behave predictably.
            if (count($rows) < $limit) {
                try {
                    $descColl = $this->collectionFactory->create();
                    $descColl
                        ->addAttributeToSelect(['name', 'description', 'url_key', 'url_path', 'is_active'])
                        ->addAttributeToFilter('is_active', ['eq' => 1])
                        ->addAttributeToFilter('path', ['like' => '1/' . $rootCategoryId . '/%'])
                        ->addAttributeToFilter('entity_id', ['neq' => $rootCategoryId])
                        ->addAttributeToFilter('description', ['like' => $like])
                        ->setStore($store)
                        ->addUrlRewriteToResult()
                        ->setPageSize($limit * 2)
                        ->setCurPage(1);
                    $extra = $this->extractRows($descColl, $query, $rootCategoryId, $limit);
                    $seen = [];
                    foreach ($rows as $r) { $seen[$r['id']] = true; }
                    foreach ($extra as $r) {
                        if (count($rows) >= $limit) {
                            break;
                        }
                        if (!isset($seen[$r['id']])) {
                            $rows[] = $r;
                            $seen[$r['id']] = true;
                        }
                    }
                } catch (\Throwable $e) {
                    // description LIKE failed — non-fatal, primary results stand.
                }
            }

            return array_slice($rows, 0, $limit);
        } catch (\Throwable $e) {
            $this->logger->warning('[PanthSearchAutocomplete] category search failed: ' . $e->getMessage());
            return [];
        }
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    /**
     * Walk a category collection and convert each row into the dropdown
     * payload shape, applying the same defensive guards as the main
     * search() method.
     *
     * @return array<int, array<string, mixed>>
     */
    private function extractRows($collection, string $query, int $rootCategoryId, int $limit): array
    {
        $rows = [];
        $needle = mb_strtolower($query);
        foreach ($collection as $cat) {
            $id = (int) $cat->getId();
            $name = trim((string) $cat->getName());
            if ($id <= 0 || $id === $rootCategoryId || $name === '') {
                continue;
            }
            // Verify the row actually contains the query somewhere
            // (name OR description) — protects against phantom rows the
            // joined product_count GROUP BY can surface.
            $haystack = mb_strtolower($name . ' ' . (string) $cat->getData('description'));
            if (mb_strpos($haystack, $needle) === false) {
                continue;
            }
            $url = (string) $cat->getUrl();
            if ($url === '') {
                continue;
            }
            $rows[] = [
                'id'    => $id,
                'name'  => $name,
                'url'   => $url,
                'count' => (int) $cat->getData('product_count'),
            ];
            if (count($rows) >= $limit) {
                break;
            }
        }
        return $rows;
    }
}
