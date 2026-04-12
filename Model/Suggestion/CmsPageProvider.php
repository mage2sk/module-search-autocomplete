<?php
declare(strict_types=1);

namespace Panth\SearchAutocomplete\Model\Suggestion;

use Magento\Cms\Model\ResourceModel\Page\CollectionFactory;
use Magento\Cms\Model\Page as CmsPage;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Panth\SearchAutocomplete\Helper\Config;
use Psr\Log\LoggerInterface;

/**
 * CMS page suggestion provider.
 *
 * Searches active CMS pages on the current store by title / content
 * preview, returning identifier-based URLs that respect store-front
 * URL rewrites.
 */
class CmsPageProvider
{
    private CollectionFactory $collectionFactory;
    private StoreManagerInterface $storeManager;
    private UrlInterface $urlBuilder;
    private Config $config;
    private LoggerInterface $logger;

    public function __construct(
        CollectionFactory $collectionFactory,
        StoreManagerInterface $storeManager,
        UrlInterface $urlBuilder,
        Config $config,
        LoggerInterface $logger
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->storeManager = $storeManager;
        $this->urlBuilder = $urlBuilder;
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function search(string $query): array
    {
        $limit = $this->config->getPagesLimit();
        if ($limit <= 0 || $query === '' || !$this->config->showPages()) {
            return [];
        }
        try {
            $storeId = (int) $this->storeManager->getStore()->getId();
            $like = '%' . $this->escapeLike($query) . '%';

            $collection = $this->collectionFactory->create();
            // Store scope: addStoreFilter joins cms_page_store and accepts
            // both the current store id and 0 = "All Stores", which is
            // exactly the visibility model the storefront uses.
            $collection->addStoreFilter($storeId);
            // Active flag — admin can disable a page without deleting it.
            $collection->addFieldToFilter('is_active', ['eq' => CmsPage::STATUS_ENABLED]);
            // Identifier must be set, otherwise we cannot build a URL.
            $collection->addFieldToFilter('identifier', ['neq' => '']);
            // Skip CMS pages used as system pages (404, no-route, home)
            // when their identifier matches the well-known reserved set.
            $collection->addFieldToFilter('identifier', ['nin' => ['no-route', 'enable-cookies', 'home', 'privacy-policy-cookie-restriction-mode']]);
            // Composite OR-search across EVERY meaningful text column on
            // the cms_page row — including the actual page body
            // (content) so a customer who types a phrase that only
            // appears inside the page text still surfaces the page.
            // Magento 2 OR-group syntax is (array $fields, array $conditions)
            // — both arrays must be the same length.
            $collection->addFieldToFilter(
                ['title', 'meta_keywords', 'meta_description', 'content_heading', 'content', 'identifier'],
                [
                    ['like' => $like],
                    ['like' => $like],
                    ['like' => $like],
                    ['like' => $like],
                    ['like' => $like],
                    ['like' => $like],
                ]
            );
            $collection->setPageSize($limit);
            $collection->setOrder('title', 'ASC');

            $rows = [];
            foreach ($collection as $page) {
                $identifier = (string) $page->getIdentifier();
                if ($identifier === '') {
                    continue;
                }
                $url = $this->urlBuilder->getUrl(null, ['_direct' => $identifier]);
                $rows[] = [
                    'id'    => (int) $page->getId(),
                    'title' => (string) $page->getTitle(),
                    'url'   => $url,
                ];
            }
            return $rows;
        } catch (\Throwable $e) {
            $this->logger->warning('[PanthSearchAutocomplete] cms page search failed: ' . $e->getMessage());
            return [];
        }
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
