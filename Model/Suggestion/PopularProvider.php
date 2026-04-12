<?php
declare(strict_types=1);

namespace Panth\SearchAutocomplete\Model\Suggestion;

use Magento\Framework\App\ResourceConnection;
use Magento\Search\Model\ResourceModel\Query\CollectionFactory as QueryCollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Panth\SearchAutocomplete\Helper\Config;
use Psr\Log\LoggerInterface;

/**
 * Popular searches provider.
 *
 * Reads the most-used queries from search_query (which Magento populates
 * for every storefront search) and returns them as quick-pick chips when
 * the user has not typed anything yet (or for empty result-sets).
 *
 * Restricted to queries that:
 *  - belong to the current store
 *  - have num_results > 0  (so we never suggest a dead query)
 *  - have a non-empty query_text
 *  - are not display_in_terms = 0 (admin can hide individual terms)
 */
class PopularProvider
{
    private QueryCollectionFactory $queryCollectionFactory;
    private StoreManagerInterface $storeManager;
    private Config $config;
    private LoggerInterface $logger;
    private ResourceConnection $resource;

    public function __construct(
        QueryCollectionFactory $queryCollectionFactory,
        StoreManagerInterface $storeManager,
        Config $config,
        LoggerInterface $logger,
        ResourceConnection $resource
    ) {
        $this->queryCollectionFactory = $queryCollectionFactory;
        $this->storeManager = $storeManager;
        $this->config = $config;
        $this->logger = $logger;
        $this->resource = $resource;
    }

    /**
     * @param string $query optional — when present, popular results that
     *                      do NOT contain the query string are filtered out
     *                      so the chip row "matches" the user's intent.
     * @return array<int, array<string, mixed>>
     */
    public function search(string $query = ''): array
    {
        $limit = $this->config->getPopularLimit();
        if ($limit <= 0 || !$this->config->showPopular()) {
            return [];
        }
        try {
            $storeId = (int) $this->storeManager->getStore()->getId();
            $coll = $this->queryCollectionFactory->create();
            $coll->addFieldToFilter('store_id', $storeId);
            $coll->addFieldToFilter('num_results', ['gt' => 0]);
            $coll->addFieldToFilter('query_text', ['neq' => '']);
            $coll->addFieldToFilter('display_in_terms', 1);
            if ($query !== '') {
                $coll->addFieldToFilter('query_text', ['like' => '%' . $this->escapeLike($query) . '%']);
            }
            $coll->setOrder('popularity', 'DESC');
            $coll->setPageSize($limit);

            $rows = [];
            foreach ($coll as $row) {
                $text = trim((string) $row->getQueryText());
                if ($text === '') {
                    continue;
                }
                $rows[] = [
                    'text'    => $text,
                    'results' => (int) $row->getNumResults(),
                ];
            }
            return $rows;
        } catch (\Throwable $e) {
            $this->logger->warning('[PanthSearchAutocomplete] popular search failed: ' . $e->getMessage());
            return [];
        }
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
