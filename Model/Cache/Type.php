<?php
declare(strict_types=1);

namespace Panth\SearchAutocomplete\Model\Cache;

use Magento\Framework\App\Cache\Type\FrontendPool;
use Magento\Framework\Cache\Frontend\Decorator\TagScope;

/**
 * Dedicated cache type so admins can flush autocomplete results
 * independently of the full page cache.
 *
 * Listed in cache management as "Panth Search Autocomplete".
 */
class Type extends TagScope
{
    public const TYPE_IDENTIFIER = 'panth_search_autocomplete';
    public const CACHE_TAG = 'PANTH_SEARCH_AUTOCOMPLETE';

    public function __construct(FrontendPool $cacheFrontendPool)
    {
        parent::__construct($cacheFrontendPool->get(self::TYPE_IDENTIFIER), self::CACHE_TAG);
    }
}
