<?php

namespace Fatchip\Nexi\Model\Method;

use Fatchip\Nexi\Model\ComputopConfig;
use Fatchip\Nexi\Model\Source\CreditcardModes;
use Fatchip\Nexi\Model\Source\CreditcardTypes;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Model\Order;

class Creditcard extends RedirectPayment
{
    /**
     * Method identifier of this payment method
     *
     * @var string
     */
    protected $methodCode = ComputopConfig::METHOD_CREDITCARD;

    /**
     * Defines where API requests are sent to at the Comutop API
     *
     * @var string
     */
    protected $apiEndpoint = "payssl.aspx"; // endpoint for iframe and payment page mode

    /**
     * Defines if transaction id is set pre or post authorization
     * True = pre auth
     * False = post auth with response
     *
     * @var bool
     */
    protected $setTransactionPreAuthorization = true;

    /**
     * Determines if auth requests adds address parameters to the request
     *
     * @var bool
     */
    protected $sendAddressData = true;

    /**
     * @var bool
     */
    protected $addLanguageToUrl = true;

    /**
     * Returns the API endpoint
     *
     * @return string
     */
    public function getApiEndpoint()
    {
        if ($this->getPaymentConfigParam('mode') == CreditcardModes::CC_MODE_SILENT) {
            $this->apiEndpoint = "paynow.aspx"; // endpoint for silent mode
        }
        return parent::getApiEndpoint();
    }

    /**
     * Get all activated creditcard types
     *
     * @return array
     */
    protected function getAvailableCreditcardTypes()
    {
        $types = [];

        $typesConfig = $this->getPaymentConfigParam('types');
        if ($typesConfig) {
            $allTypes = CreditcardTypes::getCreditcardTypes();

            $configuredTypes = explode(',', $typesConfig);
            foreach ($configuredTypes as $typeId) {
                $types[] = [
                    'id' => $allTypes[$typeId]['cardtype'],
                    'title' => $allTypes[$typeId]['name'],
                ];
            }
        }
        return $types;
    }

    /**
     * Hook for extension by the real payment method classes
     *
     * @return array
     */
    public function getFrontendConfig()
    {
        $config = [
            'mode' => $this->getPaymentConfigParam('mode'),
        ];
        if ($this->isSilentMode() === true) {
            $config = array_merge($config, $this->getSilentModeFrontendConfig());
        }
        return $config;
    }

    /**
     * Get frontend config for silent mode
     *
     * @return array[]
     */
    protected function getSilentModeFrontendConfig()
    {
        return [
            'types' => $this->getAvailableCreditcardTypes(),
        ];
    }

    /**
     * Returns whether silent mode is configured currently
     *
     * @return bool
     */
    protected function isSilentMode()
    {
        if ($this->getPaymentConfigParam('mode') == CreditcardModes::CC_MODE_SILENT) {
            return true;
        }
        return false;
    }

    /**
     * Returns redirect url for success case
     *
     * @return string|null
     */
    public function getSuccessUrl()
    {
        return $this->urlBuilder->getUrl(ComputopConfig::ROUTE_NAME.'/onepage/ccReturn', ['status' => 'success']);
    }

    /**
     * Returns redirect url for failure case
     *
     * @return string|null
     */
    public function getFailureUrl()
    {
        return $this->urlBuilder->getUrl(ComputopConfig::ROUTE_NAME.'/onepage/ccReturn', ['status' => 'failure']);
    }

    /**
     * Returns redirect url for cancel case
     *
     * @return string|null
     */
    public function getCancelUrl()
    {
        return $this->urlBuilder->getUrl(ComputopConfig::ROUTE_NAME.'/onepage/ccReturn', ['status' => 'cancel']);
    }

    /**
     * Returns if auth request is needed
     * Can be overloaded by other classes
     *
     * @return bool
     */
    protected function isAuthRequestNeeded()
    {
        if ($this->isSilentMode() === true) {
            return false;
        }
        return parent::isAuthRequestNeeded();
    }

    /**
     * @return string
     */
    protected function getTemplateName()
    {
        if (!empty($this->getPaymentConfigParam('template'))) {
            return $this->getPaymentConfigParam('template');
        }
        return "nexi_cards_v2"; // default
    }

    /**
     * @param Order|null $order
     * @return array
     */
    protected function getBillToCustomerArray(?Order $order = null)
    {
        $billToCustomer = [];
        $billingAddress = null;
        if ($order === null) {
            $quote = $this->checkoutSession->getQuote();
            $billingAddress = $quote->getBillingAddress();
            $billToCustomer['email'] = $quote->getCustomerEmail();
        } else { // order is NOT null
            $billingAddress = $order->getBillingAddress();
            $billToCustomer['email'] = $order->getCustomerEmail();
        }

        if ($billingAddress instanceof \Magento\Customer\Model\Address\AddressModelInterface) {
            if (!empty($billingAddress->getCompany())) {
                $billToCustomer['business'] = [
                    'legalName' => $billingAddress->getCompany(),
                ];
            } else {
                $billToCustomer['consumer'] = [
                    'firstName' => $billingAddress->getFirstname(),
                    'lastName' => $billingAddress->getLastname(),
                ];
            }
        }

        return $billToCustomer;
    }

    /**
     * Return parameters specific to this payment type
     *
     * @param Order|null $order
     * @return array
     */
    public function getPaymentSpecificParameters(?Order $order = null)
    {
        $params = [
            'msgVer' => '2.0',
            'Capture' => $this->getPaymentConfigParam('capture_method'),
            'billToCustomer' => $this->authRequest->getApiHelper()->encodeArray($this->getBillToCustomerArray($order)),
            'PayType' => '1', // for Acquirer GMO
        ];
        if ((bool)$this->getPaymentConfigParam('test_mode') === true) {
            $params['orderDesc'] = 'Test:0000';
        }
        return $params;
    }

    /**
     * Return parameters specific to this payment type that have to be added to the unencrypted URL
     *
     * @param Order|null $order
     * @return array
     */
    public function getUnencryptedParameters(?Order $order = null)
    {
        $params = parent::getUnencryptedParameters($order);
        $params['template'] = $this->getTemplateName();
        return $params;
    }

    /**
     * @param Order $order
     * @param array $notify
     * @return void
     */
    public function handleNotifySpecific(Order $order, $notify)
    {
        $changed = false;

        if (!empty($notify['PCNr'])) {
            $order->setComputopPcnr($notify['PCNr']);
            $changed = true;
        }
        if (!empty($notify['CCExpiry'])) {
            $order->setComputopCcexpiry($notify['CCExpiry']);
            $changed = true;
        }
        if (!empty($notify['CCBrand'])) {
            $order->setComputopCcbrand($notify['CCBrand']);
            $changed = true;
        }
        if (!empty($notify['CardHolder'])) {
            $order->setComputopCardholder($notify['CardHolder']);
            $changed = true;
        }

        if ($changed === true) {
            $order->save();
        }
    }
}
