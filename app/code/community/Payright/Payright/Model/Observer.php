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
        $event   = $observer->getEvent();
        $result  = $event->getResult();
        $method  = $observer->getMethodInstance();

        if ($method->getCode() == 'payrightcheckout') {
            $installments = $this->fetchInstallments();
            $result->isAvailable = ($installments !== "exceed_amount" && $installments !== "API Error") ? true: false;
        }

    }

    private function fetchInstallments()
    {
        $orderTotal   = Mage::helper('checkout')->getQuote()->getGrandTotal();
        $installments = Mage::helper('payright')->calculateSingleProductInstallment($orderTotal);

        return $installments;
    }

}
