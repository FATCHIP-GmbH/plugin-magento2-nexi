<?php

namespace Fatchip\Nexi\Helper;

class Database extends Base
{
    /**
     * Database connection resource object
     *
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $databaseResource;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Helper\Context      $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\App\State               $state
     * @param \Magento\Framework\App\ResourceConnection  $resource
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\State $state,
        \Magento\Framework\App\ResourceConnection $resource
    ) {
        parent::__construct($context, $storeManager, $state);
        $this->databaseResource = $resource;
    }

    /**
     * Returns db connection object
     *
     * @return AdapterInterface
     */
    protected function getDb()
    {
        return $this->databaseResource->getConnection();
    }

    /**
     * Select increment_id by computop_transid from sales_order table
     *
     * @param  string $transId
     * @return string
     */
    public function getIncrementIdByTransId($transId)
    {
        $select = $this->getDb()
            ->select()
            ->from($this->databaseResource->getTableName('sales_order'), ['increment_id'])
            ->where("computop_transid = :computop_transid")
            ->limit(1);
        return $this->getDb()->fetchOne($select, ['computop_transid' => $transId]);
    }
}