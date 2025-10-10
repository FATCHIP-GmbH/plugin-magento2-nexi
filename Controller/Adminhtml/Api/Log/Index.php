<?php

namespace Fatchip\Nexi\Controller\Adminhtml\Api\Log;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Page as BackendPage;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

/**
 * Controller for admin API-log grid
 */
class Index extends Action
{
    /**
     * Page factory
     *
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * Result page
     *
     * @var \Magento\Backend\Model\View\Result\Page
     */
    protected $resultPage;

    /**
     * Constructor
     *
     * @param Context $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(Context $context, PageFactory $resultPageFactory)
    {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
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
     * Return result page
     *
     * @return BackendPage|Page
     */
    public function execute()
    {
        if ($this->_isAllowed()) {
            $this->setPageData();
        }
        return $this->getResultPage();
    }

    /**
     * instantiate result page object
     *
     * @return BackendPage|Page
     */
    public function getResultPage()
    {
        if ($this->resultPage === null) {
            $this->resultPage = $this->resultPageFactory->create();
        }
        return $this->resultPage;
    }

    /**
     * set page data
     *
     * @return $this
     */
    protected function setPageData()
    {
        $resultPage = $this->getResultPage();
        $resultPage->setActiveMenu('Fatchip_Nexi::nexi_api_log');
        $resultPage->getConfig()->getTitle()->set((__('Api - Log')));
        return $this;
    }
}
