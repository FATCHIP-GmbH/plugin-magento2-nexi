<?php

namespace Fatchip\Nexi\Controller\Notify;

use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;

class Index extends \Magento\Framework\App\Action\Action implements CsrfAwareActionInterface
{
    /**
     * @var \Fatchip\Nexi\Model\ResourceModel\ApiLog
     */
    protected $apiLog;

    /**
     * @var \Fatchip\Nexi\Helper\Order
     */
    protected $orderHelper;

    /**
     * @var \Fatchip\Nexi\Helper\Encryption
     */
    protected $encryptionHelper;

    /**
     * @var \Magento\Framework\Controller\Result\RawFactory
     */
    protected $resultRawFactory;

    /**
     * Magento event manager
     *
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $eventManager;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Action\Context           $context
     * @param \Fatchip\Nexi\Model\ResourceModel\ApiLog    $apiLog
     * @param \Fatchip\Nexi\Helper\Order                  $orderHelper
     * @param \Fatchip\Nexi\Helper\Encryption             $encryptionHelper
     * @param \Magento\Framework\Controller\Result\RawFactory $resultRawFactory
     * @param \Magento\Framework\Event\ManagerInterface       $eventManager
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Fatchip\Nexi\Model\ResourceModel\ApiLog $apiLog,
        \Fatchip\Nexi\Helper\Order $orderHelper,
        \Fatchip\Nexi\Helper\Encryption $encryptionHelper,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
        \Magento\Framework\Event\ManagerInterface $eventManager
    ) {
        parent::__construct($context);
        $this->apiLog = $apiLog;
        $this->orderHelper = $orderHelper;
        $this->encryptionHelper = $encryptionHelper;
        $this->resultRawFactory = $resultRawFactory;
        $this->eventManager = $eventManager;
    }

    /**
     * @inheritdoc
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    /**
     * Handles return to shop
     *
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $resultRaw = $this->resultRawFactory->create();

        $content = 'ERROR'; // default
        if (!empty($this->getRequest()->getParam('Data') && !empty($this->getRequest()->getParam('Len')))) {
            $response = $this->encryptionHelper->decrypt($this->getRequest()->getParam('Data'), $this->getRequest()->getParam('Len'));
            $this->apiLog->addApiLogEntry('NOTIFY', $response);
            $params = [
                'response' => $response,
            ];

            if (!empty($response['TransID'])) {
                $order = $this->orderHelper->getOrderByTransId($response['TransID']);

                if (!empty($order)) {
                    $params['order'] = $order;
                }
            }

            $this->eventManager->dispatch('fatchip_computop_notify_all', $params);
            if (!empty($response['Status'])) {
                $this->eventManager->dispatch('fatchip_computop_notify_'.strtolower($response['Status']), $params);
            }

            $content = 'OK';
        }
        $resultRaw->setContents($content);

        return $resultRaw;
    }
}
