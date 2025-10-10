<?php

namespace Fatchip\Nexi\Block\Paypal;

use Fatchip\Nexi\Model\Api\Request\Authorization;
use Fatchip\Nexi\Model\Method\PayPal;
use Fatchip\Nexi\Model\Source\CaptureMethods;
use Magento\Checkout\Model\Session;
use Magento\Framework\View\Element\Template;

/**
 * Block class for the PayPal Express button
 */
class ExpressButton extends Template implements \Magento\Catalog\Block\ShortcutInterface
{
    /**
     * Shortcut alias
     *
     * @var string
     */
    protected $alias = 'computop.block.paypal.expressbutton';

    /**
     * @var \Magento\Framework\Locale\ResolverInterface
     */
    protected $localeResolver;

    /**
     * @var Authorization
     */
    protected $authRequest;

    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var PayPal
     */
    protected $paypalMethod;

    /**
     * @var array|null
     */
    protected $requestParams = null;

    /**
     * @var string
     */
    protected $name;

    /**
     * Locale codes supported by misc images (marks, shortcuts etc)
     *
     * @var array
     */
    protected $aSupportedLocales = [
        'de_DE',
        'en_AU',
        'en_GB',
        'en_US',
        'es_ES',
        'es_XC',
        'fr_FR',
        'fr_XC',
        'it_IT',
        'ja_JP',
        'nl_NL',
        'pl_PL',
        'zh_CN',
        'zh_XC',
    ];

    /**
     * Constructor
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Locale\ResolverInterface      $localeResolver
     * @param Authorization                                    $authRequest
     * @param Session                                          $checkoutSession
     * @param PayPal                                           $paypalMethod
     * @param array                                            $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Locale\ResolverInterface $localeResolver,
        Authorization $authRequest,
        Session $checkoutSession,
        PayPal $paypalMethod,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->localeResolver = $localeResolver;
        $this->authRequest = $authRequest;
        $this->checkoutSession = $checkoutSession;
        $this->paypalMethod = $paypalMethod;
        $this->setTemplate('paypal/express_button.phtml');
        $this->paypalMethod->setIsExpressOrder(true);
    }

    /**
     * Get shortcut alias
     *
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * @param  string $name
     * @return void
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getButtonId()
    {
        $buttonId = "paypal-button-container";
        if (strpos($this->getName(), "checkout.cart.shortcut.buttons") !== false) {
            $buttonId = "paypal-button-basket";
        } elseif (strpos($this->getName(), "shortcutbuttons") !== false) {
            $buttonId = "paypal-button-minibasket";
        }
        return $buttonId;
    }

    /**
     * Check whether specified locale code is supported. Fallback to en_US
     *
     * @param string $locale
     * @return string
     */
    protected function getSupportedLocaleCode($locale = null)
    {
        if (!$locale || !in_array($locale, $this->aSupportedLocales)) {
            return 'en_US';
        }
        return $locale;
    }


    /**
     * Get amazon widget url
     *
     * @return string
     */
    public function getJavascriptUrl()
    {
        $intent = "authorize";
        if ($this->paypalMethod->getCaptureMode() == CaptureMethods::CAPTURE_AUTO) {
            $intent = "capture";
        }
        return "https://www.paypal.com/sdk/js?client-id=".$this->getClientId()."&merchant-id=".$this->getPayerId()."&currency=".$this->getCurrency()."&disable-funding=giropay,sofort,sepa,card&intent=".$intent;
    }

    /**
     * @return string
     */
    protected function getCurrency()
    {
        return $this->checkoutSession->getQuote()->getQuoteCurrencyCode();
    }

    /**
     * @return int|mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getQuoteId()
    {
        return $this->checkoutSession->getQuote()->getId();
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getStoreCode()
    {
        return $this->checkoutSession->getQuote()->getStore()->getCode();
    }

    /**
     * @return array|null
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getRequestParams()
    {
        if ($this->requestParams === null) {
            $this->requestParams = $this->authRequest->generateRequestFromQuote($this->checkoutSession->getQuote(), $this->paypalMethod, true);
        }
        return $this->requestParams;
    }

    /**
     * @return string
     */
    protected function getPayerId()
    {
        return $this->paypalMethod->getPaymentConfigParam('paypal_account_id');
    }

    /**
     * @return string
     */
    public function getClientId()
    {
        if ((bool)$this->paypalMethod->getPaymentConfigParam('express_livemode') === false) {
            return 'ARCsDK7xBFxa5pnGxk8qvB0STB07fyi_yHDRrb5al6gxahj73Pxg9X2l7onP9J2IN-LqcVJojys94FLK';
        }
        return $this->paypalMethod->getPaymentConfigParam('paypal_client_id');
    }

    /**
     * @return string
     */
    public function getPartnerAttributionId()
    {
        if ((bool)$this->paypalMethod->getPaymentConfigParam('express_livemode') === false) {
            return 'Computop_PSP_PCP_Test';
        }
        return $this->paypalMethod->getPaymentConfigParam('paypal_partner_attribution_id');
    }

    /**
     * @return string
     */
    public function getMerchantId()
    {
        $params = $this->authRequest->getParameters();
        return $params['MerchantID'];
    }

    /**
     * @return string
     */
    public function getLenParam()
    {
        $params = $this->getRequestParams();
        if (isset($params['Len'])) {
            return $params['Len'];
        }
        return false;
    }

    /**
     * @return string
     */
    public function getDataParam()
    {
        $params = $this->getRequestParams();
        if (isset($params['Data'])) {
            return $params['Data'];
        }
        return false;
    }

    /**
     * @return string
     */
    public function getAmount()
    {
        return $this->checkoutSession->getQuote()->getGrandTotal();
    }

    /**
     * @return bool
     */
    protected function canShowPayPalExpressButton()
    {
        if ((bool)$this->paypalMethod->getPaymentConfigParam('express_active') === true && !empty($this->getPayerId()) && !empty($this->getCurrency())) {
            return true;
        }
        return false;
    }

    /**
     * Render block HTML
     *
     * @return string
     */
    protected function _toHtml()
    {
        if ($this->canShowPayPalExpressButton() === false) {
            return '';
        }

        return parent::_toHtml();
    }
}
