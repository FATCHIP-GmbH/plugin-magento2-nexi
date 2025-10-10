<?php

namespace Fatchip\Nexi\Model\ResourceModel;

use Magento\Sales\Model\Order;

class ApiLog extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * @var string[]
     */
    protected $cleanKeys = [
        'HMAC',
        'MAC',
    ];

    /**
     * Fields in request or response that need to be masked
     *
     * @var array
     */
    protected $maskFields = [];

    /**
     * Initialize connection and table
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('nexi_api_log', 'entity_id');
    }

    /**
     * Mask a given value with Xs
     *
     * @param  string $value
     * @return string
     */
    protected function maskValue($value)
    {
        for ($i = 0; $i < strlen($value); $i++) {
            $value[$i] = 'x';
        }
        return $value;
    }

    /**
     * Mask certain fields in the request array
     *
     * @param  array $array
     * @return array
     */
    protected function maskParameters($array)
    {
        foreach ($this->maskFields as $key) {
            if (isset($array[$key])) {
                $array[$key] = $this->maskValue($array[$key]);
            }
        }
        return $array;
    }

    /**
     * Returns given key from response or request or null if not set
     *
     * @param  string $key
     * @param  array $arrayA
     * @param  array $arrayB
     * @return string|null
     */
    protected function getParamValue($key, $arrayA, $arrayB = null)
    {
        if (!empty($arrayB[$key])) {
            return $arrayB[$key];
        }

        if (!empty($arrayA[$key])) {
            return $arrayA[$key];
        }
        return null;
    }

    /**
     * Cleans data for database
     *
     * @param  array $array
     * @return array
     */
    protected function cleanData($array)
    {
        foreach ($this->cleanKeys as $key) {
            if (isset($array[$key])) {
                unset($array[$key]);
            }
        }
        return $this->maskParameters($array);
    }

    /**
     * Save Api-log entry to database
     *
     * @param  string     $requestType
     * @param  array      $request
     * @param  array      $response
     * @param Order|null $order
     * @return $this
     */
    public function addApiLogEntry($requestType, $request, $response = null, ?Order $order = null)
    {
        $request = $this->cleanData($request);
        $response = $this->cleanData($response);

        $orderIncrementId = !is_null($order) ? $order->getIncrementId() : null;
        $paymentMethod = !is_null($order) ? $order->getPayment()->getMethod() : null;

        if ($order === null) {
            $transId = $this->getParamValue('TransID', $request, $response);
            if (!empty($transId)) {
                $apiLog = $this->getApiLogEntryByTransId($transId);
                if (!empty($apiLog)) {
                    $orderIncrementId = $apiLog['order_increment_id'];
                    $paymentMethod = $apiLog['payment_method'];
                }
            }
        }

        $this->getConnection()->insert(
            $this->getMainTable(),
            [
                'order_increment_id' => $orderIncrementId,
                'payment_method' => $paymentMethod,
                'request' => $requestType,
                'response' => $this->getParamValue('Status', $response),
                'request_details' => !empty($request) ? json_encode($request) : null,
                'response_details' => !empty($response) ? json_encode($response) : null,
                'pay_id' => $this->getParamValue('PayID', $request, $response),
                'trans_id' => $this->getParamValue('TransID', $request, $response),
                'x_id' => $this->getParamValue('XID', $request, $response),
            ]
        );
        return $this;
    }

    /**
     * Try to get another ApiLog entry by given TransactionId
     *
     * @param  string $transId
     * @return mixed
     */
    protected function getApiLogEntryByTransId($transId)
    {
        $select = $this->getConnection()->select()
            ->from($this->getMainTable())
            ->where("trans_id = :transId");

        $params = [
            'transId' => $transId,
        ];

        return $this->getConnection()->fetchRow($select, $params);
    }

    /**
     * @param  Order $order
     * @param  array $request
     * @param  array $response
     * @return string|null
     */
    protected function getOrderIncrementId($order, $request, $response)
    {
        if ($order !== null && !empty($order->getIncrementId())) {
            return $order->getIncrementId();
        }

        $transId = $this->getParamValue('TransID', $request, $response);
        if (!empty($transId)) {
            return $this->getOrderIncrementIdFromTransId($transId);
        }

        return null;
    }

    public function addApiLogResponse($response)
    {
        $response = $this->cleanData($response);

        $where = [
            'request != ?' => 'NOTIFY',
            'trans_id = ?' => $this->getParamValue('TransID', $response),
            'response_details IS NULL' => null,
        ];

        $this->getConnection()->update(
            $this->getMainTable(),
            [
                'response' => $this->getParamValue('Status', $response),
                'response_details' => json_encode($response),
                'pay_id' => $this->getParamValue('PayID', $response),
                'trans_id' => $this->getParamValue('TransID', $response),
                'x_id' => $this->getParamValue('XID', $response),
            ],
            $where
        );
        return $this;
    }
}

