<?php

/*
 class Payright_Payright_Model_Standard extends Mage_Payment_Model_Method_Abstract
 {
     // This is the identifier of our payment method
     protected $_code = 'mypayright';
     protected $_isInitializeNeeded      = true;
     protected $_canUseInternal          = false;
     protected $_canUseForMultishipping  = false;
 }
 */

class Payright_Payright_Model_Standard extends Mage_Payment_Model_Method_Abstract {

    protected $_code = 'payrightcheckout';
    protected $_formBlockType = 'payright/form_payright';
    protected $_infoBlockType = 'payright/info_payright';

    protected $_isInitializeNeeded = true;
    protected $_canUseInternal = false;
    protected $_canUseForMultishipping = false;

    /**
     * Return Order place redirect url
     *
     * @return mixed
     */
    public function getOrderPlaceRedirectUrl() {
        return Mage::getUrl('payright/payment/redirect', array('_secure' => true));
    }

}
