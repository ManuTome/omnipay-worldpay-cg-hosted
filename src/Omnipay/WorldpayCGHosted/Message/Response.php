<?php

namespace Omnipay\WorldpayCGHosted\Message;

use DOMDocument;
use Omnipay\Common\Exception\InvalidResponseException;
use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\RequestInterface;

/**
 * WorldPay XML Response
 */
class Response extends AbstractResponse
{
    const PAYMENT_STATUS_AUTHORISED = 'AUTHORISED';

    /** @noinspection PhpMissingParentConstructorInspection */
    /**
     * @param RequestInterface $request Request
     * @param string           $data    Data
     * @throws InvalidResponseException if data is empty
     */
    public function __construct(RequestInterface $request, $data)
    {
        $this->request = $request;

        if (empty($data)) {
            throw new InvalidResponseException();
        }

        $responseDom = new DOMDocument;
        $responseDom->loadXML($data);

        $this->data = simplexml_import_dom(
            $responseDom->documentElement->firstChild->firstChild
        );
    }

    /**
     * Get message
     *
     * @return string
     */
    public function getMessage()
    {
        $codes = [
            0  => 'AUTHORISED',
            2  => 'REFERRED',
            3  => 'INVALID ACCEPTOR',
            4  => 'HOLD CARD',
            5  => 'REFUSED',
            8  => 'APPROVE AFTER IDENTIFICATION',
            12 => 'INVALID TRANSACTION',
            13 => 'INVALID AMOUNT',
            14 => 'INVALID ACCOUNT',
            15 => 'INVALID CARD ISSUER',
            17 => 'ANNULATION BY CLIENT',
            19 => 'REPEAT OF LAST TRANSACTION',
            20 => 'ACQUIRER ERROR',
            21 => 'REVERSAL NOT PROCESSED, MISSING AUTHORISATION',
            24 => 'UPDATE OF FILE IMPOSSIBLE',
            25 => 'REFERENCE NUMBER CANNOT BE FOUND',
            26 => 'DUPLICATE REFERENCE NUMBER',
            27 => 'ERROR IN REFERENCE NUMBER FIELD',
            28 => 'ACCESS DENIED',
            29 => 'IMPOSSIBLE REFERENCE NUMBER',
            30 => 'FORMAT ERROR',
            31 => 'UNKNOWN ACQUIRER ACCOUNT CODE',
            33 => 'CARD EXPIRED',
            34 => 'FRAUD SUSPICION',
            38 => 'SECURITY CODE EXPIRED',
            40 => 'REQUESTED FUNCTION NOT SUPPORTED',
            41 => 'LOST CARD',
            43 => 'STOLEN CARD, PICK UP',
            51 => 'LIMIT EXCEEDED',
            55 => 'INVALID SECURITY CODE',
            56 => 'UNKNOWN CARD',
            57 => 'ILLEGAL TRANSACTION',
            58 => 'TRANSACTION NOT PERMITTED',
            62 => 'RESTRICTED CARD',
            63 => 'SECURITY RULES VIOLATED',
            64 => 'AMOUNT HIGHER THAN PREVIOUS TRANSACTION AMOUNT',
            68 => 'TRANSACTION TIMED OUT',
            75 => 'SECURITY CODE INVALID',
            76 => 'CARD BLOCKED',
            80 => 'AMOUNT NO LONGER AVAILABLE, AUTHORISATION EXPIRED',
            85 => 'REJECTED BY CARD ISSUER',
            91 => 'CREDITCARD ISSUER TEMPORARILY NOT REACHABLE',
            92 => 'CREDITCARD TYPE NOT PROCESSED BY ACQUIRER',
            94 => 'DUPLICATE REQUEST ERROR',
            97 => 'SECURITY BREACH'
        ];

        $message = 'PENDING';

        if (isset($this->data->error)) {
            $message = 'ERROR: ' . $this->data->error;
        }

        if (isset($this->data->payment->ISO8583ReturnCode)) {
            $returnCode = $this->data->payment->ISO8583ReturnCode->attributes();

            foreach ($returnCode as $name => $value) {
                if ($name == 'code') {
                    $message = $codes[intval($value)];
                }
            }
        }

        if ($this->isSuccessful()) {
            $message = $codes[0];
        }

        return $message;
    }

    /**
     * Get transaction reference
     *
     * @return string|null
     */
    public function getTransactionReference()
    {
        $attributes = $this->data->attributes();

        if (isset($attributes['orderCode'])) {
            return $attributes['orderCode'];
        }

        return null;
    }

    /**
     * Get is redirect
     *
     * @return bool
     */
    public function isRedirect()
    {
        return (isset($this->data->reference));
    }

    /**
     * Get is successful
     *
     * @return bool
     */
    public function isSuccessful()
    {
        if (!isset($this->data->payment->lastEvent)) {
            return false;
        }

        return (strtoupper($this->data->payment->lastEvent) === self::PAYMENT_STATUS_AUTHORISED);
    }
}
