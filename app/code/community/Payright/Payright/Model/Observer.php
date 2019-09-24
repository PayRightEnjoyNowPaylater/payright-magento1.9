<?php

class Payright_Payright_Model_Observer
{

    /* Update the configuration in Admin */
    public function updateAdminConfiguration($observer)
    {
        $helper = Mage::helper('payright');

        $authToken = $helper->DoApiCallPayright();

        if ($authToken['status'] != 'Authenticated') {
            $message = 'There is some problem with API Authentication details. Please check again!!';
            Mage::getSingleton('adminhtml/session')->addError($message);
        }
    }


    public function disablePayright($observer)
    {
        $event             = $observer->getEvent();
        $method            = $event->getMethodInstance();
        $result            = $event->getResult();
        $currencyCode      = Mage::app()->getStore()->getCurrentCurrencyCode();
        $methodnew         = $observer->getMethodInstance();
        $installmentsArray = $this->fetchInstallments();

        if ($methodnew->getCode() == 'payrightcheckout') {
            if (($installmentsArray != "exceed_amount") && ($installmentsArray != "API Error")) {
                $result->isAvailable = true;
            } else {
                $result->isAvailable = false;
            }
        }

    }

    public function fetchInstallments()
    {
        $orderTotal   = Mage::helper('checkout')->getQuote()->getGrandTotal();
        $installments = Mage::helper('payright')->calculateSingleProductInstallment($orderTotal);
        return $installments;
    }

}
