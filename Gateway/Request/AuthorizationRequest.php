<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Hipay\HipayProfessionalGateway\Gateway\Request;

use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;

class AuthorizationRequest implements BuilderInterface
{
    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @param ConfigInterface $config
     */
    public function __construct(
        ConfigInterface $config
    ) {
        $this->config = $config;
    }

    /**
     * Builds ENV request
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
		
        if (!isset($buildSubject['payment'])
            || !$buildSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new \InvalidArgumentException('Payment data object should be provided');
        }

        /** @var PaymentDataObjectInterface $payment */
        $payment = $buildSubject['payment'];
        $order = $payment->getOrder();
                        
        $address = $order->getShippingAddress();
        return array(
            'TYPE' 					=> 'CAPTURE',
            'INVOICE' 				=> $order->getOrderIncrementId(),
            'AMOUNT' 				=> $order->getGrandTotalAmount(),
            'CURRENCY' 				=> $order->getCurrencyCode(),
            'EMAIL' 				=> $address->getEmail(),
            'DEBUG' 				=> $this->config->getValue('debug',     				$order->getStoreId() ),
            'SANDBOX' 				=> $this->config->getValue('sandbox',     			$order->getStoreId() ),
            'IFRAME' 				=> $this->config->getValue('iframe',     			$order->getStoreId() ),
            'TECH_EMAIL' 			=> $this->config->getValue('technical_email',  	$order->getStoreId() ),
            'WEBSITE_RATING' 			=> $this->config->getValue('website_rating',   $order->getStoreId() ),
            'WEBSITE_LOGO' 			=> $this->config->getValue('website_logo',     	$order->getStoreId() ),
            'MERCHANT_CREDENTIALS' 		=> $this->config->getValue('api_' .	$order->getCurrencyCode(),     $order->getStoreId() )
        );

    }
}
