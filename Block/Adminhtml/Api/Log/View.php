<?php

namespace Fatchip\Nexi\Block\Adminhtml\Api\Log;

use Fatchip\Nexi\Model\ComputopConfig;
use Fatchip\Nexi\Model\Entities\ApiLog;

class View extends \Magento\Backend\Block\Widget\Container
{
    /**
     * Requested ApiLog-entry
     *
     * @var \Fatchip\Nexi\Model\Entities\ApiLog
     */
    protected $apiLog = null;

    /**
     * ApiLog factory
     *
     * @var \Fatchip\Nexi\Model\Entities\ApiLogFactory
     */
    protected $apiLogFactory;

    /**
     * Constructor
     *
     * @param \Magento\Backend\Block\Widget\Context          $context
     * @param \Fatchip\Nexi\Model\Entities\ApiLogFactory     $apiLogFactory
     * @param array                                          $data
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Fatchip\Nexi\Model\Entities\ApiLogFactory $apiLogFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->apiLogFactory = $apiLogFactory;
    }

    /**
     * Returns the currently requested ApiLog-object
     *
     * @return ApiLog
     */
    public function getApiLogEntry()
    {
        if ($this->apiLog === null) {
            $apiLog = $this->apiLogFactory->create();
            $apiLog->load($this->getRequest()->getParam('id'));
            $this->apiLog = $apiLog;
        }
        return $this->apiLog;
    }

    /**
     * Adding the Back button
     *
     * @return void
     */
    protected function _construct()
    {
        $this->buttonList->add(
            'back',
            [
                'label' => __('Back'),
                'onclick' => "setLocation('".$this->getUrl(ComputopConfig::ROUTE_NAME.'/api_log/')."')",
                'class' => 'back'
            ]
        );
        parent::_construct();
    }
}
