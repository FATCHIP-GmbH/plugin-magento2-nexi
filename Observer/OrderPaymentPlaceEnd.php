<?php

namespace Fatchip\Nexi\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;

class OrderPaymentPlaceEnd implements ObserverInterface
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var string[]
     */
    protected $checkoutSessionCleanParams = [
        'computop_is_error',
        #'computop_customer_is_redirected',
        #'computop_redirect_url',
        'computop_ppe_is_express_order',
        'computop_ppe_is_express_auth_step',
        'computop_ppe_pay_id',
        'computop_api_log_data',
        'computop_quote_comparison_string',
        'computop_no_order_redirect_response',
        'computop_easy_credit_dob',
        'computop_ratepay_dfp_sent',
        #'computop_easy_credit_info',
        #'computop_easy_credit_confirm_response',
    ];

    protected $customerSessionCleanParams = [
        'computop_rate_pay_device_ident_token',
    ];

    /**
     * Constructor
     *
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Customer\Model\Session $customerSession
     */
    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Model\Session $customerSession
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
    }

    /**
     * @return void
     */
    protected function clearCheckoutSession()
    {
        foreach ($this->checkoutSessionCleanParams as $cleanParam) {
            $this->checkoutSession->unsetData($cleanParam);
        }
    }

    /**
     * @return void
     */
    protected function clearCustomerSession()
    {
        foreach ($this->customerSessionCleanParams as $cleanParam) {
            $this->customerSession->unsetData($cleanParam);
        }
    }

    /**
     * Execute certain tasks after the payment is placed and thus the order is placed
     *
     * @param  Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $this->clearCheckoutSession();
        $this->clearCustomerSession();
    }
}
