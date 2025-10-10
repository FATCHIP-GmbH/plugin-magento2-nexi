<?php

namespace Fatchip\Nexi\Block\Adminhtml\Config\Form\Field;

use Fatchip\Nexi\Model\ComputopConfig;

class ModuleVersion extends \Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray
{
    /**
     * @var \Magento\Framework\Module\ModuleListInterface
     */
    protected $moduleList;

    /**
     * Template
     *
     * @var string
     */
    protected $_template = 'Fatchip_Nexi::system/config/form/field/module_version.phtml';

    /**
     * Constructor
     *
     * @param \Magento\Backend\Block\Template\Context       $context
     * @param \Magento\Framework\Module\ModuleListInterface $moduleList
     * @param array                                         $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->moduleList = $moduleList;
    }

    /**
     * Initialise form fields
     *
     * @return void
     */
    protected function _construct()
    {
        $this->addColumn('txaction', ['label' => __('Some text')]); // isnt shown but needed for block to work
        parent::_construct();
    }

    /**
     * @return string
     */
    public function getModuleVersion()
    {
        return $this->moduleList->getOne(ComputopConfig::MODULE_NAME)['setup_version'];
    }
}
