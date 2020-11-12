<?php

class Payright_Payright_Block_Form_Payright extends Mage_Payment_Block_Form
{
    const XML_CONFIG_PREFIX   = 'payright/payovertime_checkout/';
    protected $_labelTemplate = 'payright/form/payrightlabel.phtml';
    /**
     * Instructions text
     *
     * @var string
     */
    // protected $_instructions;

    /**
     * Block construction. Set block template.
     */
    protected function _construct()
    {
        parent::_construct();

        $this->setTemplate('payright/form/payrightcheckout.phtml');
        $this->setMethodLabelAfterHtml($this->getTitleConfiguration());
        $this->setMethodTitle("");
    }

    public function fetchInstallments()
    {
        $orderTotal   = $this->getOrderTotal();
        $installments = Mage::helper('payright')->calculateSingleProductInstallment($orderTotal);

        return $installments;
    }

    public function getOrderTotal()
    {
        return Mage::getSingleton('checkout/session')->getQuote()->getGrandTotal();
    }

    public function getTitleConfiguration()
    {
        $block = Mage::app()->getLayout()->createBlock('core/template');
        $block->setTemplate($this->_labelTemplate);
        $config = $this->getHtmlTemplate();

        $block->setData(
            array(
                'logo' => $config,
            )
        );

        return $block->toHtml();
    }

    public function getHtmlTemplate()
    {
        $result = Mage::getStoreConfig(self::XML_CONFIG_PREFIX . 'checkout_headline_html_template');

        $result = str_replace(
            '{skin_url}',
            Mage::app()->getStore()->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_SKIN),
            $result
        );

        return $result;
    }

}
