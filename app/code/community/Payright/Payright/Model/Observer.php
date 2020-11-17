<?php

class Payright_Payright_Model_Observer {

    // TODO When using auth-token / Access Token, need a auth verification call from byron-bay
    // TODO Then update config.xml under adminhtml > events (if needed - to tell admin Access Token entered was correct / incorrect)
    /* Update the configuration in Admin */
    public function updateAdminConfiguration($observer) {
        $authToken = Mage::helper('payright')->getAccessToken();

        if ($authToken) {
            // Do nothing
        } else {
            $message = 'We require your \'Access Token\', it can be obtained from your merchant store at the developer portal.';
            Mage::getSingleton('adminhtml/session')->addError($message);
        }
    }


    public function disablePayright($observer) {
        $event = $observer->getEvent();
        $result = $event->getResult();
        $method = $observer->getMethodInstance();

        if (!$result->isAvailable) {
            return;
        }

        if ($method->getCode() == 'payrightcheckout') {

            $orderTotal = floatval(Mage::helper('checkout')->getQuote()->getGrandTotal());
            $minValue = Mage::helper('payright')->getConfigValue('min_amount');

            $installments = $this->fetchInstallments();
            $result->isAvailable = $installments !== "exceed_amount" && $installments !== "auth_token_error" && $orderTotal >= $minValue;
        }
    }

    // TODO No need to update status, as we do not need to make API call to byron-bay on 'status updates'

    /**
     * Activate Plans after shipment
     */
//    public function payrightOrderShipment($observer) {
//        $order = $observer->getEvent()->getShipment()->getOrder();
//
//        if ($order->getPayrightPlanId() !== null) {
//            Mage::helper('payright')->planStatusChange($order->getPayrightPlanId(), 'Active');
//        }
//    }

    private function fetchInstallments() {
        $orderTotal = Mage::helper('checkout')->getQuote()->getGrandTotal();
        return Mage::helper('payright')->calculateSingleProductInstallment($orderTotal);
    }

}
