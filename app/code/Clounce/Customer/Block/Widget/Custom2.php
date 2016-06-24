<?php
/**
 * Copyright © 2015 Clounce.
 *
 * This class was obtained from Magento Customer Widget taxvat
 */

namespace Clounce\Customer\Block\Widget;

use \Magento\Customer\Api\CustomerMetadataInterface;

class Custom2 extends \Magento\Customer\Block\Widget\AbstractWidget
{
    /**
     * Constructor.
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Customer\Helper\Address $addressHelper
     * @param CustomerMetadataInterface $customerMetadata
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Customer\Helper\Address $addressHelper,
        CustomerMetadataInterface $customerMetadata,
        array $data = []
    ) {
        parent::__construct($context, $addressHelper, $customerMetadata, $data);
        $this->_isScopePrivate = true;
    }

    /**
     * Sets the template
     *
     * @return void
     */
    public function _construct()
    {
        parent::_construct();
        $this->setTemplate('Clounce_Customer::widget/custom2.phtml');
    }

    /**
     * Get is required.
     *
     * @return bool
     */
    public function isRequired()
    {
        return $this->_getAttribute('custom_2') ? (bool)$this->_getAttribute('custom_2')->isRequired() : false;
    }
}
