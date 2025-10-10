<?php

namespace Fatchip\Nexi\Model\Method;

use Fatchip\Nexi\Model\ComputopConfig;
use Fatchip\Nexi\Model\Source\CaptureMethods;
use Fatchip\Nexi\Model\Source\Service;
use Magento\Framework\Event\ManagerInterface;
use Magento\Payment\Gateway\Command\CommandManagerInterface;
use Magento\Payment\Gateway\Command\CommandPoolInterface;
use Magento\Payment\Gateway\Config\ValueHandlerPoolInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectFactory;
use Magento\Payment\Gateway\Validator\ValidatorPoolInterface;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Psr\Log\LoggerInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Service\InvoiceService;

class Przelewy24 extends RedirectPayment
{
    /**
     * Method identifier of this payment method
     *
     * @var string
     */
    protected $methodCode = ComputopConfig::METHOD_PRZELEWY24;

    /**
     * Defines where API requests are sent to at the Comutop API
     *
     * @var string
     */
    protected $apiEndpoint = "p24.aspx";

    /**
     * Determines if auth requests adds billing address parameters to the request
     *
     * @var bool
     */
    protected $addBillingAddressData = true;

    /**
     * Determines if auth requests adds shipping address parameters to the request
     *
     * @var bool
     */
    protected $addShippingAddressData = true;

    /**
     * Can be used to assign data from frontend to info instance
     *
     * @var array
     */
    protected $assignKeys = [
        'accountholder',
    ];

    /**
     * @return string
     */
    public function getCaptureMode()
    {
        return CaptureMethods::CAPTURE_AUTO;
    }

    /**
     * Returns is PPRO service is configured
     *
     * @return bool
     */
    protected function isPproMode()
    {
        if ($this->getPaymentConfigParam('service') == Service::SERVICE_PPRO) {
            return true;
        }
        return false;
    }

    /**
     * Hook for extension by the real payment method classes
     *
     * @return array
     */
    public function getFrontendConfig()
    {
        return [
            'service' => $this->getPaymentConfigParam('service'),
        ];
    }


    /**
     * Create a ArticleList entry from given values
     *
     * @param  string $id
     * @param  string $name
     * @param  string $description
     * @param  double $qty
     * @param  double $total
     * @param  string $currency
     * @return array
     */
    protected function getArticleListEntry($id, $name, $description, $qty, $total, $currency)
    {
        return [
            'id' => $id,
            'name' => $name,
            'description' => $description,
            'quantity' => $qty,
            'amount' => $this->apiHelper->formatAmount($total, $currency),
        ];
    }

    /**
     * Create product ArticleList entry from order item
     *
     * @param $item
     * @param string $currency
     * @return array
     */
    protected function getArticleListProductEntry($item, $currency)
    {
        return $this->getArticleListEntry(
            $item->getProductId(),
            $item->getName(),
            $item->getDescription(), ///@TODO Check if reasonable - maybe just send name here too
            $item->getQtyOrdered(),
            $item->getRowTotalInclTax(),
            $currency
        );
    }

    /**
     * Create shipping ArticleList entry from order
     *
     * @param Order $order
     * @return array
     */
    protected function getArticleListShippingEntry(Order $order)
    {
        return $this->getArticleListEntry(
            'shipping',
            'shipping',
            (string)__('Shipping Costs'),
            '1',
            $order->getShippingInclTax(),
            $this->getCurrentCurrency($order)
        );
    }

    protected function getArticleListDiscount(Order $order)
    {
        $desc = (string)__('Discount');
        if ($order->getCouponCode()) {
            $desc = (string)__('Coupon').' - '.$order->getCouponCode(); // add counpon code to description
        }

        return $this->getArticleListEntry(
            'discount',
            'discount',
            $desc,
            '1',
            $order->getDiscountAmount(),
            $this->getCurrentCurrency($order)
        );
    }

    protected function getItemList(Order $order)
    {
        $list = [];
        foreach ($order->getAllItems() as $item) {
            if (($order instanceof Order && $item->isDummy() === false) || ($order instanceof Quote && $item->getParentItemId() === null)) { // prevent variant-products of adding 2 items
                $list[] = $this->getArticleListProductEntry($item, $this->getCurrentCurrency($order));
            }
        }

        if ($order->getShippingInclTax()) {
            $list[] = $this->getArticleListShippingEntry($order);
        }
        if ($order->getDiscountAmount()) {
            $list[] = $this->getArticleListDiscount($order);
        }
        return ['items' => $list];
    }

    /**
     * Return parameters specific to this payment type
     *
     * @param Order|null $order
     * @return array
     */
    public function getPaymentSpecificParameters(?Order $order = null)
    {
        $dataSource = $order;
        if ($order === null) {
            $dataSource = $this->checkoutSession->getQuote();
        }

        $infoInstance = $this->getInfoInstance();

        $params = [
            'Email' => $dataSource->getBillingAddress()->getEmail(),
            'bdeMail' => $dataSource->getBillingAddress()->getEmail(),
        ];

        if ($this->isPproMode()) {
            $params['AccOwner'] = $infoInstance->getAdditionalInformation('accountholder');

            if (!empty($this->getPaymentConfigParam('ppro_channel'))) {
                $params['Channel'] = $this->getPaymentConfigParam('ppro_channel');
            }
        } else { // Direct mode
            $params['Articlelist'] = $this->apiHelper->encodeArray($this->getItemList($order));
            $params['OrderDesc'] = 'test:0000';
        }

        return $params;
    }
}
