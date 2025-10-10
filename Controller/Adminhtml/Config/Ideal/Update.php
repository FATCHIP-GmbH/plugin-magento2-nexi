<?php

namespace Fatchip\Nexi\Controller\Adminhtml\Config\Ideal;

use Magento\Backend\App\Action;

/**
 * Controller for updating Ideal banks
 */
class Update extends Action
{
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var \Fatchip\Nexi\Model\ResourceModel\IdealIssuerList
     */
    protected $idealIssuerList;

    /**
     * Constructor
     *
     * @param \Magento\Backend\App\Action\Context                   $context
     * @param \Magento\Framework\Controller\Result\JsonFactory      $resultJsonFactory
     * @param \Fatchip\Nexi\Model\ResourceModel\IdealIssuerList $idealIssuerList
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Fatchip\Nexi\Model\ResourceModel\IdealIssuerList $idealIssuerList
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->idealIssuerList = $idealIssuerList;
    }

    /**
     * Return if the user has the needed rights to view this page
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Fatchip_Nexi::nexi_ideal_update');
    }

    /**
     * Updates ideal banks
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $data = false;

        if ($this->_isAllowed()) {
            $this->idealIssuerList->clearIssuerList();
            $result = $this->idealIssuerList->getIssuerList();
            if (!empty($result)) {
                $data = [];
                $data['success'] = true;
            } else {
                $data = [];
                $data['success'] = false;
            }
        }

        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultJsonFactory->create();
        return $resultJson->setData($data);
    }
}
