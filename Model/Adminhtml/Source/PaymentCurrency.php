<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Hipay\HipayProfessionalGateway\Model\Adminhtml\Source;

use Magento\Payment\Model\Method\AbstractMethod;

/**
 * Class PaymentAction
 */
class PaymentCurrency implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            ['value' => "EUR", 'label' => "Euro"],
            ['value' => "USD", 'label' => "US Dollar"],
            ['value' => "GBP", 'label' => "British Pound Sterling" ],
            ['value' => "SEK", 'label' => "Swedish Krona"    ],                        
            ['value' => "CAD", 'label' => "Canadian Dollar"  ],                        
            ['value' => "AUD", 'label' => "Australian Dollar"	 ],
            [ 'value' => "CHF",'label' => "Swiss Franc"   ],
            [ 'value' => "PLN", 'label' => "Polish Zloty" ]
        ];
    }
}
