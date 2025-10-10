<?php

namespace Fatchip\Nexi\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Sales\Setup\SalesSetupFactory;

class InstallData implements \Magento\Framework\Setup\InstallDataInterface
{
    /**
     * Sales setup factory
     *
     * @var SalesSetupFactory
     */
    protected $salesSetupFactory;

    /**
     * Constructor
     *
     * @param SalesSetupFactory $salesSetupFactory
     */
    public function __construct(SalesSetupFactory $salesSetupFactory)
    {
        $this->salesSetupFactory = $salesSetupFactory;
    }

    /**
     * Constructor
     *
     * @param  ModuleDataSetupInterface $setup
     * @param  ModuleContextInterface   $context
     * @return void
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        $salesInstaller = $this->salesSetupFactory->create(['resourceName' => 'sales_setup', 'setup' => $setup]);
        $salesInstaller->addAttribute(
            'order',
            'computop_payid',
            ['type' => 'varchar', 'length' => 64, 'default' => '', 'grid' => true]
        );

        $setup->endSetup();
    }
}
