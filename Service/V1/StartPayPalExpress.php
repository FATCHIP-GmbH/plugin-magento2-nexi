<?php

namespace Fatchip\Nexi\Service\V1;

use Fatchip\Nexi\Api\Data\StartPayPalExpressResponseInterfaceFactory;
use Fatchip\Nexi\Api\StartPayPalExpressInterface;
use Fatchip\Nexi\Helper\Checkout;
use Fatchip\Nexi\Model\ComputopConfig;
use Fatchip\Nexi\Model\ResourceModel\ApiLog;
use Fatchip\Nexi\Helper\Encryption;
use Magento\Checkout\Model\Session;

class StartPayPalExpress implements StartPayPalExpressInterface
{
    /**
     * Factory for the response object
     *
     * @var StartPayPalExpressResponseInterfaceFactory
     */
    protected $responseFactory;

    /**
     * Checkout session object
     *
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var ApiLog
     */
    protected $apiLog;

    /**
     * @var Encryption
     */
    protected $encryptionHelper;

    /**
     * @var Checkout
     */
    protected $checkoutHelper;

    /**
     * Constructor.
     *
     * @param StartPayPalExpressResponseInterfaceFactory $responseFactory
     * @param Session                                    $checkoutSession
     * @param ApiLog                                     $apiLog
     * @param Encryption                                 $encryptionHelper
     * @param Checkout                                   $checkoutHelper
     */
    public function __construct(
        StartPayPalExpressResponseInterfaceFactory $responseFactory,
        Session $checkoutSession,
        ApiLog $apiLog,
        Encryption $encryptionHelper,
        Checkout $checkoutHelper
    ) {
        $this->responseFactory = $responseFactory;
        $this->checkoutSession = $checkoutSession;
        $this->apiLog = $apiLog;
        $this->encryptionHelper = $encryptionHelper;
        $this->checkoutHelper = $checkoutHelper;
    }

    /**
     * Logs the PPE auth request and set PPE as used payment method
     *
     * @param string $cartId
     * @param string $data
     * @param string $len
     * @return \Fatchip\Nexi\Service\V1\Data\StartPayPalExpressResponse
     */
    public function start($cartId, $data, $len)
    {
        $response = $this->responseFactory->create();
        $response->setData('success', false); // set success to false as default, set to true later if true

        $quote = $this->checkoutSession->getQuote();

        $payment = $quote->getPayment();
        $payment->setMethod(ComputopConfig::METHOD_PAYPAL);

        $request = $this->encryptionHelper->decrypt($data, $len);
        if (!empty($request)) {
            $this->apiLog->addApiLogEntry('PAYPALEXPRESS', $request);
            #$payment->setTransactionId($request['TransID']);
            #$payment->setIsTransactionClosed(0);
            #$payment->save();
            $this->checkoutSession->setComputopTmpRefnr($request['RefNr']);
        }

        $quote->save();
        $this->checkoutSession->setComputopQuoteComparisonString($this->checkoutHelper->getQuoteComparisonString($quote));

        return $response;
    }
}
