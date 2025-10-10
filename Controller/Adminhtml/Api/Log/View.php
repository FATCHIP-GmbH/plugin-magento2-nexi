<?php

namespace Fatchip\Nexi\Controller\Adminhtml\Api\Log;

use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\ForwardFactory;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

/**
 * Controller class for API-log details-page
 */
class View extends \Magento\Backend\App\Action
{
    /**
     * Result page
     *
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * Result forward
     *
     * @var \Magento\Backend\Model\View\Result\ForwardFactory
     */
    protected $resultForwardFactory;

    /**
     * Constructor
     *
     * @param \Magento\Backend\App\Action\Context               $context
     * @param \Magento\Backend\Model\View\Result\ForwardFactory $resultForwardFactory
     * @param \Magento\Framework\View\Result\PageFactory        $resultPageFactory
     */
    public function __construct(
        Context $context,
        ForwardFactory $resultForwardFactory,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->resultForwardFactory = $resultForwardFactory;
    }

    /**
     * Return if the user has the needed rights to view this page
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Fatchip_Nexi::nexi_api_log');
    }

    /**
     * Returns result page
     *
     * @return Page
     */
    public function execute()
    {
        /** @var Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        if ($this->_isAllowed()) {
            $resultPage->setActiveMenu('Fatchip_Nexi::nexi_api_log');
            $resultPage->getConfig()->getTitle()->prepend(__('API - Log'));
            $resultPage->getConfig()->getTitle()->prepend(sprintf("#%s", $this->getRequest()->getParam('id')));
        }
        return $resultPage;
    }
}
