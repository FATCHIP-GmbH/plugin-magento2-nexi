<?php

namespace Fatchip\Nexi\Model;

use Fatchip\Nexi\Model\Method\BaseMethod;
use Magento\Checkout\Model\ConfigProviderInterface;

class ConfigProvider implements ConfigProviderInterface
{
    /**
     * Payment helper object
     *
     * @var \Magento\Payment\Helper\Data
     */
    protected $dataHelper;

    /**
     * Computop payment helper
     *
     * @var \Fatchip\Nexi\Helper\Payment
     */
    protected $paymentHelper;

    /**
     * Escaper object
     *
     * @var \Magento\Framework\Escaper
     */
    protected $escaper;

    /**
     * Checkout session object
     *
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * Constructor
     *
     * @param \Magento\Payment\Helper\Data     $dataHelper
     * @param \Fatchip\Nexi\Helper\Payment $paymentHelper
     * @param \Magento\Framework\Escaper       $escaper
     * @param \Magento\Checkout\Model\Session  $checkoutSession
     */
    public function __construct(
        \Magento\Payment\Helper\Data $dataHelper,
        \Fatchip\Nexi\Helper\Payment $paymentHelper,
        \Magento\Framework\Escaper $escaper,
        \Magento\Checkout\Model\Session  $checkoutSession
    ) {
        $this->dataHelper = $dataHelper;
        $this->paymentHelper = $paymentHelper;
        $this->escaper = $escaper;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Returns Computop custom config for config params not specifically tied to a payment method
     *
     * @return array
     */
    protected function getComputopCustomConfig()
    {
        $config = [
            'cancelledPaymentMethod' => $this->getCancelledPaymentMethod(),
        ];
        return $config;
    }

    /**
     * @return string|false
     */
    protected function getCancelledPaymentMethod()
    {
        $paymentMethod = $this->checkoutSession->getComputopCancelledPaymentMethod();
        $this->checkoutSession->unsComputopCancelledPaymentMethod();
        if (!empty($paymentMethod)) {
            return $paymentMethod;
        }
        return false;
    }

    /**
     * Get the payment instruction text
     *
     * @param  BaseMethod $methodInstance
     * @return string
     */
    protected function getInstructionByCode($methodInstance)
    {
        return nl2br($this->escaper->escapeHtml($methodInstance->getConfigData('instructions')));
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig()
    {
        $config = ['payment' => [
            'computop' => $this->getComputopCustomConfig(),
        ]];

        foreach ($this->paymentHelper->getAvailablePaymentTypes() as $methodCode) {
            $methodInstance = $this->dataHelper->getMethodInstance($methodCode);
            if ($methodInstance instanceof BaseMethod && $methodInstance->isAvailable()) {
                $config['payment']['computop'][$methodCode] = $methodInstance->getFrontendConfig();
                $config['payment']['instructions'][$methodCode] = $this->getInstructionByCode($methodInstance);
            }
        }
        return $config;
    }
}
