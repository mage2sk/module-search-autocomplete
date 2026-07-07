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

    public function search(string $query): array
    {
        $limit = $this->config->getProductsLimit();
        if ($limit <= 0 || $query === '') {
            return [];
        }

        try {
            $store = $this->storeManager->getStore();
            $storeId = (int) $store->getId();

            $items = $this->runEngineSearch($query, $store, $storeId, $limit);

            if (count($items) < $limit) {
                $extra = $this->searchByDirectAttributes($query, $store, $storeId, $limit);
                $items = $this->mergeUnique($items, $extra, $limit);
            }

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

    private function expandQueryViaVocabulary(string $query, int $storeId): string
    {
        $tokens = $this->tokenise($this->normalise($query));
        if (!$tokens) {
            return '';
        }
        $extras = [];
        foreach ($tokens as $tok) {
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

    private function runEngineSearch(string $query, $store, int $storeId, int $limit): array
    {
        try {
            try {
                $this->layerResolver->create(LayerResolver::CATALOG_LAYER_SEARCH);
            } catch (\RuntimeException $e) {
            }
            $collection = $this->layerResolver->get()->getProductCollection();
            $collection
                ->addAttributeToSelect(['name', 'small_image', 'thumbnail', 'price', 'special_price', 'sku', 'url_key'])
                ->setStore($store)
                ->addStoreFilter((int) $store->getId())

                ->setVisibility($this->visibility->getVisibleInSearchIds())

                ->addAttributeToFilter('status', ['eq' => ProductStatus::STATUS_ENABLED])
                ->addSearchFilter($query)

                ->setOrder('relevance', 'DESC')

                ->setPageSize(max($limit * 4, 24))
                ->setCurPage(1);

            $showOos = (bool) $this->scopeConfig->getValue(
                'cataloginventory/options/show_out_of_stock',
                ScopeInterface::SCOPE_STORE
            );
            if (!$showOos) {
                $this->stockHelper->addInStockFilterToCollection($collection);
            }

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

    private function normalise(string $value): string
    {
        $value = mb_strtolower($value);

        $value = preg_replace('/[\-_\/]+/u', ' ', $value) ?? $value;

        $value = preg_replace('/[^\p{L}\p{N} ]+/u', '', $value) ?? $value;

        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
        return trim($value);
    }

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

            if (mb_strlen($tok) >= 4 && mb_substr($tok, -1) === 's') {
                $out[mb_substr($tok, 0, -1)] = true;
            }

            if (mb_strlen($tok) >= 5 && mb_substr($tok, -2) === 'es') {
                $out[mb_substr($tok, 0, -2)] = true;
            }
        }
        return array_keys($out);
    }

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
            return null;
        }
    }
}
