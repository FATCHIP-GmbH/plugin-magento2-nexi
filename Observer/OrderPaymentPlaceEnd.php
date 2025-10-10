<?php

namespace Fatchip\Nexi\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Checkout\Model\Session;

class OrderPaymentPlaceEnd implements ObserverInterface
{
    /**
     * @var Session
     */
    protected $checkoutSession;

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
        #'computop_easy_credit_info',
        #'computop_easy_credit_confirm_response',
    ];

    /**
     * Constructor
     *
     * @param Session $checkoutSession
     */
    public function __construct(
        Session $checkoutSession
    ) {
        $this->checkoutSession = $checkoutSession;
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
     * Execute certain tasks after the payment is placed and thus the order is placed
     *
     * @param  Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $this->clearCheckoutSession();
    }
}
