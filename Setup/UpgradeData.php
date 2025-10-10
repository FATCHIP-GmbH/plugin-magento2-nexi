<?php

namespace Fatchip\Nexi\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Sales\Setup\SalesSetupFactory;

/**
 * Class UpgradeData
 */
class UpgradeData implements UpgradeDataInterface
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
     * @param SalesSetupFactory     $salesSetupFactory
     */
    public function __construct(
        SalesSetupFactory $salesSetupFactory
    ) {
        $this->salesSetupFactory = $salesSetupFactory;
    }

    /**
     * Upgrade method
     *
     * @param  ModuleDataSetupInterface $setup
     * @param  ModuleContextInterface   $context
     * @return void
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        if (!$setup->getConnection()->tableColumnExists($setup->getTable('sales_order'), 'computop_payid')) {
            $salesInstaller = $this->salesSetupFactory->create(['resourceName' => 'sales_setup', 'setup' => $setup]);
            $salesInstaller->addAttribute(
                'order',
                'computop_payid',
                ['type' => 'varchar', 'length' => 64, 'default' => '', 'grid' => true]
            );
        }

        if (!$setup->getConnection()->tableColumnExists($setup->getTable('sales_order'), 'computop_pcnr')) {
            $salesInstaller = $this->salesSetupFactory->create(['resourceName' => 'sales_setup', 'setup' => $setup]);
            $salesInstaller->addAttribute(
                'order',
                'computop_pcnr',
                ['type' => 'varchar', 'length' => 32, 'default' => '', 'grid' => true]
            );
        }

        if (!$setup->getConnection()->tableColumnExists($setup->getTable('sales_order'), 'computop_ccexpiry')) {
            $salesInstaller = $this->salesSetupFactory->create(['resourceName' => 'sales_setup', 'setup' => $setup]);
            $salesInstaller->addAttribute(
                'order',
                'computop_ccexpiry',
                ['type' => 'varchar', 'length' => 32, 'default' => '', 'grid' => true]
            );
        }

        if (!$setup->getConnection()->tableColumnExists($setup->getTable('sales_order'), 'computop_ccbrand')) {
            $salesInstaller = $this->salesSetupFactory->create(['resourceName' => 'sales_setup', 'setup' => $setup]);
            $salesInstaller->addAttribute(
                'order',
                'computop_ccbrand',
                ['type' => 'varchar', 'length' => 32, 'default' => '', 'grid' => true]
            );
        }

        if (!$setup->getConnection()->tableColumnExists($setup->getTable('sales_order'), 'computop_cardholder')) {
            $salesInstaller = $this->salesSetupFactory->create(['resourceName' => 'sales_setup', 'setup' => $setup]);
            $salesInstaller->addAttribute(
                'order',
                'computop_cardholder',
                ['type' => 'varchar', 'length' => 64, 'default' => '', 'grid' => true]
            );
        }

        $setup->endSetup();
    }
}
