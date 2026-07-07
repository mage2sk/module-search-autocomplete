<?php
declare(strict_types=1);

namespace Panth\SearchAutocomplete\Setup;

use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Panth\SearchAutocomplete\Service\InstallReporter;

class RecurringData implements InstallDataInterface
{
    public function __construct(
        private readonly InstallReporter $reporter
    ) {
    }

    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context): void
    {
        $this->reporter->reportInstall();
    }
}
