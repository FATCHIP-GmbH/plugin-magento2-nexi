<?php

namespace Fatchip\Nexi\Block\Adminhtml\Config\Form\Field;

class Validation extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * Template
     *
     * @var string
     */
    protected $_template = 'Fatchip_Nexi::system/config/form/field/validation.phtml';

    /**
     * @var \Fatchip\Nexi\Helper\Validation
     */
    protected $validationHelper;

    /**
     * Constructor
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Fatchip\Nexi\Helper\Validation     $validationHelper
     * @param array                                   $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Fatchip\Nexi\Helper\Validation $validationHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->validationHelper = $validationHelper;
    }

    /**
     * Unset some non-related element parameters
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * @return false|string
     */
    public function validateRefNrExtensions()
    {
        $error = false;
        try {
            $this->validationHelper->validateRefNrExtensions();
        } catch(\Exception $exc) {
            $error = $exc->getMessage();
        }
        return $error;
    }

    /**
     * Get the button and scripts contents
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $originalData = $element->getOriginalData();
        $this->addData(
            [
                'html_id' => $element->getHtmlId(),
            ]
        );
        return $this->_toHtml();
    }
}
