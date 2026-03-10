<?php

namespace Fatchip\Nexi\Model\CronJob;

use Fatchip\Nexi\Helper\Base;
use Fatchip\Nexi\Model\Api\Request\Inquire;
use Magento\Framework\App\ObjectManager;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Store\Model\StoresConfig;
use Magento\Sales\Model\Order;
use Magento\Framework\App\ResourceConnection;

/**
 * Class that provides functionality of cleaning expired quotes by cron
 */
class CleanExpiredOrders
{
    /**
     * @var Base
     */
    protected $baseHelper;

    /**
     * @var OrderManagementInterface
     */
    private $orderManagement;

    /**
     * @var Inquire
     */
    protected $inquireRequest;

    /**
     * Database connection resource
     *
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $databaseResource;

    /**
     * @param Base                          $baseHelper
     * @param ResourceConnection            $resource
     * @param Inquire                       $inquireRequest
     * @param OrderManagementInterface|null $orderManagement
     */
    public function __construct(
        Base                     $baseHelper,
        ResourceConnection       $resource,
        Inquire                  $inquireRequest,
        ?OrderManagementInterface $orderManagement = null
    )
    {
        $this->baseHelper = $baseHelper;
        $this->databaseResource = $resource;
        $this->inquireRequest = $inquireRequest;
        $this->orderManagement = $orderManagement ?: ObjectManager::getInstance()->get(OrderManagementInterface::class);
    }

    /**
     * Check inquire response for failed status
     *
     * @param array $inquireResponse
     * @return bool
     */
    protected function isPaymentStatusFailed($inquireResponse)
    {
        if (!empty($inquireResponse)) {
            if (!empty($inquireResponse['LastStatus']) && $inquireResponse['LastStatus'] == 'FAILED') {
                return true;
            }

            if (!empty($inquireResponse['Status']) && !empty($inquireResponse['Description']) && $inquireResponse['Status'] == 'FAILED' && $inquireResponse['Description'] == 'PAYMENT NOT FOUND') {
                return true;
            }
        }
        return false;
    }

    /**
     * Get expired orders from database
     *
     * @return array
     */
    protected function getExpiredOrders()
    {
        $lifetime = (int)$this->baseHelper->getConfigParam('cronjob_pending_lifetime');
        if ($lifetime < 0) {
            $lifetime = 25; // set to default
        }

        $db = $this->databaseResource->getConnection();
        $select = $db
            ->select()
            ->from($this->databaseResource->getTableName('sales_order_grid'), ['entity_id', 'increment_id'])
            ->where("payment_method LIKE 'computop_%'")
            ->where('TIME_TO_SEC(TIMEDIFF(CURRENT_TIMESTAMP, `updated_at`)) >= ?', ($lifetime * 60))  // check for $lifetime minutes
            ->where('TIME_TO_SEC(TIMEDIFF(CURRENT_TIMESTAMP, `updated_at`)) < ?', (60 * 60 * 24)) // only check for the last 24 hours
            ->where("status = 'pending_payment'");
        return $db->fetchAll($select);
    }

    /**
     * Clean expired quotes (cron process)
     *
     * @return void
     */
    public function execute()
    {
        $expiredOrders = $this->getExpiredOrders();
        foreach ($expiredOrders as $order) {
            try {
                $inquireResponse = $this->inquireRequest->getPaymentStatusByTransId($order['increment_id']);
                if ($this->isPaymentStatusFailed($inquireResponse) === true) {
                    $this->orderManagement->cancel((int)$order['entity_id']);
                }
            } catch (\Exception $e) {
                // do nothing
            }
        }
    }
}
