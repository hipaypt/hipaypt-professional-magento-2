<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Hipay\HipayProfessionalGateway\Gateway\Response;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;

class TxnIdHandler implements HandlerInterface
{
    const REDIRECT_URL = 'REDIRECT_URL';
    const ACCOUNT_TYPE = 'ACCOUNT_TYPE';
    const TRANSACTION_ID = 'TRANSACTION_ID';
    const IFRAME = 'IFRAME';

    /**
     * Handles transaction id
     *
     * @param array $handlingSubject
     * @param array $response
     * @return void
     */
    public function handle(array $handlingSubject, array $response)
    {
        if (!isset($handlingSubject['payment'])
            || !$handlingSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new \InvalidArgumentException('Payment data object should be provided');
        }

        $paymentDO = $handlingSubject['payment'];
        $payment = $paymentDO->getPayment();

        $payment->setTransactionId($response[self::TRANSACTION_ID]);
        $payment->setAdditionalInformation("redirectUrl",$response[self::REDIRECT_URL]);
        $payment->setAdditionalInformation("accountType",$response[self::ACCOUNT_TYPE]);
        $payment->setAdditionalInformation("iframe",$response[self::IFRAME]);
        $payment->setIsTransactionClosed(false);
			
    }


}
