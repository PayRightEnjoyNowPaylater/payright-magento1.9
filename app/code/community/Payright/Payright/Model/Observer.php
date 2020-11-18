<?php

class Payright_Payright_Model_Observer {

    // TODO When using auth-token / Access Token, need a auth verification call from byron-bay
    // TODO Then update config.xml under adminhtml > events (if needed - to tell admin Access Token entered was correct / incorrect)
    /**
     * Toggle enable/disable Payright payment method plugin, if certain Payright business rules are met.
     *
     * @param $observer
     */
    public function updateAdminConfiguration($observer) {
        $authToken = Mage::helper('payright')->getAccessToken();

        $emptyAuthToken = is_string($authToken) && strlen(trim($authToken)) === 0;

        if ($emptyAuthToken) {
            $message = 'We require your \'Access Token\', it can be obtained from your merchant store at the developer portal.';
            Mage::getSingleton('adminhtml/session')->addError($message);
        } else {
            $message = 'Your access token is saved. Please back up your access token '.$authToken.' for safe-keeping.';
            Mage::getSingleton('adminhtml/session')->addSuccess($message);
        }
    }

    /**
     * Toggle enable/disable Payright payment method plugin, if certain Payright business rules are met.
     *
     * @param $observer
     */
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
            $result->isAvailable = $installments !== "exceed_amount" && $installments !== "auth_token_error" && $installments !== "rates_error" && $orderTotal >= $minValue;
        }
    }

    /**
     * Activate Plans after shipment.
     *
     * @param $observer
     */
    public function payrightOrderShipment($observer) {
        $order = $observer->getEvent()->getShipment()->getOrder();

        if ($order->getPayrightPlanId() !== null) {
            Mage::helper('payright')->planStatusChange($order->getPayrightPlanId(), 'Active');
        }
    }

    /**
     * Fetch payment installments.
     *
     */
    private function fetchInstallments() {
        $orderTotal = Mage::helper('checkout')->getQuote()->getGrandTotal();
        return Mage::helper('payright')->calculateSingleProductInstallment($orderTotal);
    }

    /**
     * Test API Connection, with defined 'Access Token' in system configuration.
     *
     * @return bool
     */
    private function testApiConnection() {
        // Get 'Access Token' from system configuration
        $authToken = Mage::helper('payright')->getAccessToken();

        // Get the API Url endpoint, from 'config.xml'
        $getEnvironmentEndpoints = $this->getEnvironmentEndpoints();
        $apiEndpoint = $getEnvironmentEndpoints['ApiUrl'];

        try {
            // Define API GET call for 'data' = 'rates', 'establishmentFees' and 'otherFees'
            $client = new Zend_Http_Client($apiEndpoint . "api/v1/merchant/configuration");
            $client->setHeaders(
                array(
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' . $authToken
                )
            );
            $client->setConfig(array('timeout' => 15));

            // JSON decode the 'data' response
            // $response = json_decode($client->request()->getBody(), true);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

}
