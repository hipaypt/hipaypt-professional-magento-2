<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Hipay\HipayProfessionalGateway\Block;

use Magento\Framework\Phrase;
use Magento\Payment\Block\ConfigurableInfo;

class Info extends ConfigurableInfo
{
    protected function getLabel($field)
    {
        return __($field);
    }

    protected function getValueView($field, $value)
    {
        return parent::getValueView($field, $value);
    }
}
