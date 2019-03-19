<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Hipay\HipayProfessionalGateway\Model\Adminhtml\Source;

use Magento\Payment\Model\Method\AbstractMethod;

class PaymentRating implements \Magento\Framework\Option\ArrayInterface
{
    public function toOptionArray()
    {
        return [
            [ 'value' => "ALL", 'label' => "ALL" ],
            [ 'value' => "+12", 'label' => "+12" ],
            [ 'value' => "+16", 'label' => "+16" ],
            [ 'value' => "+18", 'label' => "+18" ]            
        ];
    }
}
