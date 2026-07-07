<?php
declare(strict_types=1);

namespace Panth\SearchAutocomplete\ViewModel;

use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Escaper;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Panth\SearchAutocomplete\Helper\Config;
use Panth\SearchAutocomplete\Model\Security\RequestValidator;

class SearchBox implements ArgumentInterface
{
    private Config $config;
    private FormKey $formKey;
    private UrlInterface $urlBuilder;
    private Escaper $escaper;

    public function __construct(
        Config $config,
        FormKey $formKey,
        UrlInterface $urlBuilder,
        Escaper $escaper
    ) {
        $this->config = $config;
        $this->formKey = $formKey;
        $this->urlBuilder = $urlBuilder;
        $this->escaper = $escaper;
    }

    public function isEnabled(): bool
    {
        return $this->config->isEnabled();
    }

    public function getEndpointUrl(): string
    {
        return $this->urlBuilder->getUrl('searchautocomplete/ajax/suggest');
    }

    public function getViewAllSearchUrl(): string
    {
        return $this->urlBuilder->getUrl('catalogsearch/result');
    }

    public function getFormKey(): string
    {
        return (string) $this->formKey->getFormKey();
    }

    public function getMinQueryLength(): int
    {
        return $this->config->getMinQueryLength();
    }

    public function getMaxQueryLength(): int
    {
        return $this->config->getMaxQueryLength();
    }

    public function getDebounceMs(): int
    {
        return $this->config->getDebounceMs();
    }

    public function getHoneypotName(): string
    {
        return RequestValidator::HONEYPOT_FIELD;
    }

    public function showImage(): bool
    {
        return $this->config->showImage();
    }

    public function showPrice(): bool
    {
        return $this->config->showPrice();
    }

    public function showCategories(): bool
    {
        return $this->config->showCategories();
    }

    public function showPages(): bool
    {
        return $this->config->showPages();
    }

    public function showPopular(): bool
    {
        return $this->config->showPopular();
    }

    public function jsConfig(): string
    {
        return (string) json_encode([
            'endpoint'      => $this->getEndpointUrl(),
            'viewAllUrl'    => $this->getViewAllSearchUrl(),
            'minLength'     => $this->getMinQueryLength(),
            'maxLength'     => $this->getMaxQueryLength(),
            'debounceMs'    => $this->getDebounceMs(),
            'honeypotName'  => $this->getHoneypotName(),
            'showImage'     => $this->showImage(),
            'showPrice'     => $this->showPrice(),
            'showCategories'=> $this->showCategories(),
            'showPages'     => $this->showPages(),
            'showPopular'   => $this->showPopular(),
            'i18n' => [
                'placeholder'   => __('Search for products, categories, brands...')->render(),
                'searching'     => __('Searching…')->render(),
                'noResults'     => __('No matches. Try a simpler word like "shirt" or "bag".')->render(),
                'products'      => __('Products')->render(),
                'categories'    => __('Categories')->render(),
                'pages'         => __('Pages')->render(),
                'popular'       => __('Popular searches')->render(),
                'recent'        => __('Recent searches')->render(),
                'viewAll'       => __('See all results for "%1"')->render(),
                'clear'         => __('Clear')->render(),
                'close'         => __('Close')->render(),
                'submit'        => __('Search')->render(),
                'tooShort'      => __('Type at least %1 characters')->render(),
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    public function escape(string $value): string
    {
        return $this->escaper->escapeHtml($value);
    }
}
