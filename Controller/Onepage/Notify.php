<?php

namespace Fatchip\Nexi\Controller\Onepage;

class Notify extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Fatchip\Nexi\Model\ResourceModel\ApiLog
     */
    protected $apiLog;

    /**
     * @var \Fatchip\Nexi\Helper\Encryption
     */
    protected $encryptionHelper;

    /**
     * @var \Magento\Framework\Controller\Result\RawFactory
     */
    protected $resultRawFactory;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Action\Context           $context
     * @param \Fatchip\Nexi\Model\ResourceModel\ApiLog    $apiLog
     * @param \Fatchip\Nexi\Helper\Encryption             $encryptionHelper
     * @param \Magento\Framework\Controller\Result\RawFactory $resultRawFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Fatchip\Nexi\Model\ResourceModel\ApiLog $apiLog,
        \Fatchip\Nexi\Helper\Encryption $encryptionHelper,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory
    ) {
        parent::__construct($context);
        $this->apiLog = $apiLog;
        $this->encryptionHelper = $encryptionHelper;
        $this->resultRawFactory = $resultRawFactory;
    }

    /**
     * Handles return to shop
     *
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $resultRaw = $this->resultRawFactory->create();
        if (!empty($this->getRequest()->getParam('Data') && !empty($this->getRequest()->getParam('Len')))) {
            $response = $this->encryptionHelper->decrypt($this->getRequest()->getParam('Data'), $this->getRequest()->getParam('Len'));
            $this->apiLog->addApiLogEntry('NOTIFY', $response);

            $resultRaw->setContents('OK');
        } else {
            $resultRaw->setContents('ERROR');
        }

        return $resultRaw;
    }
}
