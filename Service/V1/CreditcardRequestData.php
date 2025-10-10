<?php

namespace Fatchip\Nexi\Service\V1;

use Fatchip\Nexi\Api\CreditcardRequestDataInterface;
use Fatchip\Nexi\Api\Data\CreditcardRequestDataResponseInterfaceFactory;
use Fatchip\Nexi\Model\Method\Creditcard;
use Fatchip\Nexi\Service\V1\Data\CreditcardRequestDataResponse;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\RequestInterface;
use Fatchip\Nexi\Model\Api\Request\Authorization;

class CreditcardRequestData implements CreditcardRequestDataInterface
{
    /**
     * Factory for the response object
     *
     * @var CreditcardRequestDataResponseInterfaceFactory
     */
    protected $responseFactory;

    /**
     * Checkout session object
     *
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var \Fatchip\Nexi\Model\Api\Request\Authorization
     */
    protected $authRequest;

    /**
     * Constructor.
     *
     * @param CreditcardRequestDataResponseInterfaceFactory $responseFactory
     * @param Session                                       $checkoutSession
     * @param RequestInterface                              $request
     * @param Authorization                                 $authRequest
     */
    public function __construct(
        CreditcardRequestDataResponseInterfaceFactory $responseFactory,
        Session $checkoutSession,
        RequestInterface $request,
        Authorization $authRequest
    ) {
        $this->responseFactory = $responseFactory;
        $this->checkoutSession = $checkoutSession;
        $this->request = $request;
        $this->authRequest = $authRequest;
    }

    /**
     * @param  bool $javaEnabled
     * @param  int  $screenHeight
     * @param  int  $screenWidth
     * @param  int  $colorDepth
     * @param  int  $timeZoneOffset
     * @return string
     */
    protected function getBrowserInfo($javaEnabled, $screenHeight, $screenWidth, $colorDepth, $timeZoneOffset)
    {
        $lang = explode(',', $this->request->getHeader('accept-language'));
        $browserInfoParams = [
            'javaScriptEnabled' => true,
            'javaEnabled' => $javaEnabled,
            'colorDepth' => (int)$colorDepth,
            'screenHeight' => (int)$screenHeight,
            'screenWidth' => (int)$screenWidth,
            'timeZoneOffset' => (string)$timeZoneOffset,
            'acceptHeaders' => $this->request->getHeader('accept'),
            'ipAddress' => $this->request->getClientIp(),
            'language' => array_shift($lang),
            'userAgent' => $this->request->getHeader('user-agent'),
        ];
        return $this->authRequest->getApiHelper()->encodeArray($browserInfoParams);
    }

    /**
     * Returns Data and Len parameters for creditcard silent mode request
     *
     * @param  string $orderId
     * @param  bool   $javaEnabled
     * @param  int    $screenHeight
     * @param  int    $screenWidth
     * @param  int    $colorDepth
     * @param  int    $timeZoneOffset
     * @return \Fatchip\Nexi\Service\V1\Data\CreditcardRequestDataResponse
     */
    public function getCreditcardRequestData($orderId, $javaEnabled, $screenHeight, $screenWidth, $colorDepth, $timeZoneOffset)
    {
        $response = $this->responseFactory->create();
        $response->setData('success', false); // set success to false as default, set to true later if true

        $order = $this->checkoutSession->getLastRealOrder();
        $payment = $order->getPayment();
        $methodInstance = $payment->getMethodInstance();
        if ($payment && $methodInstance instanceof Creditcard) {
            $browserInfo = $this->getBrowserInfo($javaEnabled, $screenHeight, $screenWidth, $colorDepth, $timeZoneOffset);

            $this->authRequest->addParameter('browserInfo', $browserInfo);
            $this->authRequest->setTransactionId($payment->getLastTransId());
            $request = $this->authRequest->generateRequestFromOrder($order, $payment, $order->getTotalDue(), true, true);

            $response->setData('dataParam', $request['Data']);
            $response->setData('lenParam', $request['Len']);
            $response->setData('merchantId', $request['MerchantID']);
            $response->setData('success', true);
        } else {
            $response->setData('errormessage', "Creditcard payment has to be chosen");
        }

        return $response;
    }
}
