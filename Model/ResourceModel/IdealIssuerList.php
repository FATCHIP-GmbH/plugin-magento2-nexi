<?php

namespace Fatchip\Nexi\Model\ResourceModel;

use Magento\Sales\Model\Order;

class IdealIssuerList extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * @var \Fatchip\Nexi\Model\Api\Request\IdealIssuerList
     */
    protected $idealIssuerRequest;

    /**
     * Class constructor
     *
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param \Fatchip\Nexi\Model\Api\Request\IdealIssuerList $idealIssuerRequest
     * @param string $connectionName
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Fatchip\Nexi\Model\Api\Request\IdealIssuerList $idealIssuerRequest,
        $connectionName = null
    ) {
        parent::__construct($context, $connectionName);
        $this->idealIssuerRequest = $idealIssuerRequest;
    }

    /**
     * Initialize connection and table
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('computop_ideal_issuerlist', 'issuer_id');
    }

    /**
     * Try to get another ApiLog entry by given TransactionId
     *
     * @param  string $transId
     * @return mixed
     */
    public function getIssuerList()
    {
        $select = $this->getConnection()->select()
            ->from($this->getMainTable());

        $result = $this->getConnection()->fetchAll($select);

        if (empty($result)) {
            $result = $this->idealIssuerRequest->getIssuerList();
            $this->addFullIdealIssuerList($result);
        }
        return $result;
    }

    /**
     * Save Api-log entry to database
     *
     * @param  string $issuerId
     * @param  string $name
     * @param  string $country
     * @return $this
     */
    public function addIdealIssuer($issuerId, $name, $country)
    {
        $this->getConnection()->insert(
            $this->getMainTable(),
            [
                'issuer_id' => $issuerId,
                'name' => $name,
                'country' => $country,
            ]
        );
        return $this;
    }

    /**
     * Write
     *
     * @param  array $issuerList
     * @param  bool  $clearList
     * @return $this|false
     */
    public function addFullIdealIssuerList($issuerList, $clearList = true)
    {
        if (empty($issuerList)) {
            return false;
        }

        if ($clearList === true) {
            $this->clearIssuerList();
        }

        foreach ($issuerList as $issuer) {
            $this->addIdealIssuer($issuer['issuerId'], $issuer['name'], $issuer['country']);
        }
        return $this;
    }

    /**
     * Deletes all entries from Ideal issuer list table
     *
     * @return $this
     */
    public function clearIssuerList()
    {
        $where = 1; // delete everything

        $this->getConnection()->delete(
            $this->getMainTable(),
            $where
        );
        return $this;
    }

    public function addApiLogResponse($response)
    {
        $response = $this->cleanData($response);

        $where = [
            'request = ?' => 'REDIRECT',
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

