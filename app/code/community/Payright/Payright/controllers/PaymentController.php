<?php

// app/code/local/Envato/Custompaymentmethod/controllers/PaymentController.php
class Payright_Payright_PaymentController extends Mage_Core_Controller_Front_Action {

    public function redirectAction() {

        $payApiAuth = Mage::helper('payright')->DoApiCallPayright();
        $apiToken = $payApiAuth['payrightAccessToken'];

        // if successfully authenticated then go do the configuration call
        if ($payApiAuth['status'] == 'Authenticated') {
            ### do the config call
            $payConfigCall = Mage::helper('payright')->DoApiTransactionConfCallPayright($apiToken);

            ### fetch the mangentoo order id
            $_order = new Mage_Sales_Model_Order();
            $orderId = Mage::getSingleton('checkout/session')->getLastRealOrderId();
            $_order->loadByIncrementId($orderId);

            $sandboxEnv = Mage::getConfig()->getNode('global/payright/environments/sandbox/api_url');
            ### this is the get the merchant store id
            $clientId = Mage::helper('payright')->getConfigValue('client_id');

            $transactionData['transactionTotal'] = number_format((float)$_order->getBaseGrandTotal(), 2, '.', '');
            $transactionData['platform_type'] = 'magentov1';
            $transactionData['magentoOrderId'] = $orderId;

            $ecommClientId = $clientId;
            $merchantReference = "MageParyright_" . $orderId;
            ### this is the transactions data

            $configToken = $payConfigCall['configToken'];
            $sugarAuth = $payConfigCall['auth']['auth-token'];

            ### do the call to intialize the payright transaction.
            $intialiseTransaction = Mage::helper('payright')->DoApiIntializeTransaction(
                $apiToken,
                $sugarAuth,
                $configToken,
                json_encode($transactionData),
                $ecommClientId,
                $merchantReference
            );

            /// get the endpoints from the config files
            $ApiEndpoints = $this->getEnviromentEndpoints();
            /// build the redirect URL
            $builtAppUrl = $this->buildRedirectUrl($ApiEndpoints, $intialiseTransaction['ecommToken']);
            $layoutData['builtAppEndpoint'] = $builtAppUrl;

            $layoutDataString = implode(", ", $layoutData);

            // Clear session values.
            Mage::getSingleton('customer/session')->unsPayrightAccessToken();
            Mage::getSingleton('customer/session')->unsPayrightRefereshToken();

            // Restore cart / quote - for users who click 'Back' browser button
            $this->_handleCart(true);

            $this->loadLayout();
            $block = $this->getLayout()->createBlock(
                'Mage_Core_Block_Template',
                'payright',
                array('template' => 'payright/redirect.phtml')
            )
                ->setData('builtappendpoint', $builtAppUrl);

            $this->getLayout()->getBlock('content')->append($block);
            $this->renderLayout();
        }
    }

    // The response action is triggered when your gateway sends back a response after processing the customer's payment
    public function responseAction() {
        if ($this->getRequest()->isGet()) {

            /*
            /* Your gateway's code to make sure the reponse you
            /* just got is from the gatway and not from some weirdo.
            /* This generally has some checksum or other checks,
            /* and is provided by the gateway.
            /* For now, we assume that the gateway's response is valid
             */
            $orderId = Mage::app()->getRequest()->getParam('orderid');
            $ecom = Mage::app()->getRequest()->getParam('ecommtoken');
            $validated = true;

            ### do the call to getplan data for the payright transaction.
            $json = Mage::helper('payright')->getPlanDataByToken($ecom);
            $result = json_decode($json['transactionResult']);

            if (isset($result->prtransactionStatus)) {
                $transactionStatus = $result->prtransactionStatus;
            }

            $planId = isset($result->planData) ? $result->planData->id : null;
            $planName = isset($result->planData) ? $result->planData->name : null;

            if ($validated) {
                if ($transactionStatus != "approved") {
                    $this->cancelAction();
                } else {
                    // Payment was successful, so update the order's state, send order email and move to the success page
                    $order = Mage::getModel('sales/order');
                    $order->loadByIncrementId($orderId);
                    $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true, 'Gateway has authorized the payment.');
                    // Set Payright Details.
                    $order->setPayrightPlanId($planId);
                    $order->setPayrightEcomToken($ecom);

                    $order->sendNewOrderEmail();
                    $order->setEmailSent(true);

                    $order->save();

                    // Save order ID in sales_flat_order_payment table
                    $payment = $order->getPayment();
                    $payment->setData('payright_plan_number', $planName);
                    $payment->save();

                    Mage::getSingleton('checkout/session')->unsQuoteId();

                    Mage_Core_Controller_Varien_Action::_redirect('checkout/onepage/success', array('_secure' => true));
                }
            } else {
                // There is a problem in the response we got
                $this->cancelAction();
                Mage_Core_Controller_Varien_Action::_redirect('checkout/onepage/failure', array('_secure' => true));
            }
        } else {
            echo "Inside the order";
            //Mage_Core_Controller_Varien_Action::_redirect('');

        }
    }

    // The cancel action is triggered when an order is to be cancelled
    public function cancelAction() {

        $ecom = Mage::app()->getRequest()->getParam('ecommtoken');

        ### do the call to getplan data for the payright transaction.
        $json = Mage::helper('payright')->getPlanDataByToken($ecom);
        $result = json_decode($json['transactionResult']);

        if (isset($result->prtransactionStatus)) {
            $transactionStatus = $result->prtransactionStatus;
            $planid = $result->planId;
            if ($transactionStatus != "approved" && $transactionStatus != "Declined") {
                $helper = Mage::helper('payright')->planStatusChange($planid, 'Cancelled');
            }
        }

        $this->_handleCart(false, true);

        Mage::getSingleton('checkout/session')->addError(Mage::helper('checkout')->__("Payright Checkout has been cancelled."));
        $this->_redirect('checkout/cart');
        return;
    }

    public function getEnviromentEndpoints() {
        $payrightMode = Mage::helper('payright')->getConfigValue('sandbox');
        /// if the payright mode is set to sandbox then get the API endpoints
        try {

            if ($payrightMode == '1') {
                $sandboxApiUrl = Mage::getConfig()->getNode('global/payright/environments/sandbox')->api_url;
                $sandboxAppEndpoint = Mage::getConfig()->getNode('global/payright/environments/sandbox')->web_url;

                $returnEndpoints['ApiUrl'] = $sandboxApiUrl;
                $returnEndpoints['AppEndpoint'] = $sandboxAppEndpoint;
            } else {

                $productionApiUrl = Mage::getConfig()->getNode('global/payright/environments/production')->api_url;
                $productionEndpoint = Mage::getConfig()->getNode('global/payright/environments/production')->web_url;

                $returnEndpoints['ApiUrl'] = $productionApiUrl;
                $returnEndpoints['AppEndpoint'] = $productionEndpoint;
            }

            return $returnEndpoints;
        } catch (Exception $e) {

            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function buildRedirectUrl($envConfigArray, $ecommToken) {
        $redirectUrlBuild = $envConfigArray['AppEndpoint'] . "/loan/new/" . $ecommToken;

        return $redirectUrlBuild;
    }

    private function _handleCart($isRestoreCart, $cancel = false) {

        // If for redirectAction() function, then also "save" (restore) the last quote of order given
        if($isRestoreCart) {
            if (Mage::getSingleton('checkout/session')->getLastRealOrderId()) {
                if ($lastQuoteId = Mage::getSingleton('checkout/session')->getLastQuoteId()) {
                    $quote = Mage::getModel('sales/quote')->load($lastQuoteId);
                    $quote->setIsActive(true)->save();
                }
            }
        }

        // If for cancelAction() function, then
        if ($cancel) {
            if (Mage::getSingleton('checkout/session')->getLastRealOrderId()) {
                $order = Mage::getModel('sales/order')->loadByIncrementId(Mage::getSingleton('checkout/session')->getLastRealOrderId());

                if ($order->getId()) {
                    // Flag the order as 'cancelled' and save
                    $order->cancel()->setState(Mage_Sales_Model_Order::STATE_CANCELED, true, 'Gateway has declined the payment.')->save();
                }
            }
        }
    }
}
