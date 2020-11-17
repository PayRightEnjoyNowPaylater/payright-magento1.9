<?php

// app/code/local/Envato/Custompaymentmethod/controllers/PaymentController.php
class Payright_Payright_PaymentController extends Mage_Core_Controller_Front_Action {

    public function redirectAction() {

        $authToken = Mage::helper('payright')->getAccessToken();
        $redirectUrl = Mage::helper('payright')->getRedirectUrl();

        // If 'access token' and 'redirect url' is not empty.
        if ($authToken != '' && $redirectUrl != '') {
            // Fetch the Magento order id
            $_order = new Mage_Sales_Model_Order();
            $orderId = Mage::getSingleton('checkout/session')->getLastRealOrderId();
            $_order->loadByIncrementId($orderId);

            // Prepare 'sale amount' as currency format.
            $saleAmount = number_format((float)$_order->getBaseGrandTotal(), 2, '.', '');

            // Define the merchant reference, of order.
            $merchantReference = "MagePayright_" . $orderId;

            // Generate 'expiresAt', set to expire 6 months from today's datetime.
            $dt = new DateTime();
            $interval = new DateInterval('P6M');
            $dt->add($interval);
            $dt->setTimeZone(new DateTimeZone('UTC'));
            $expiresAt = $dt->format('Y-m-d\TH-i-s.\0\0\0\Z');

            // Initialize the Payright transaction. To get the 'checkoutId'
            $initialiseTransaction = Mage::helper('payright')->performApiCheckout(
                $merchantReference,
                $saleAmount,
                $redirectUrl,
                $expiresAt
            );

            // Get the endpoints from the config files
            $apiEndpoints = Mage::helper('payright')->getEnvironmentEndpoints();

            // Build the redirect, to 'checkout portal'.
            $builtAppUrl = $this->buildRedirectUrl($apiEndpoints);

            // $layoutData['builtAppEndpoint'] = $builtAppUrl; // TODO What's this for?
            // $layoutDataString = implode(", ", $layoutData); // TODO What's this for?

            // Clear session values.
            // Mage::getSingleton('customer/session')->unsPayrightAccessToken();

            // Restore cart / quote - for users who click 'Back' browser button
            $this->_handleCart(true);

            $this->_redirectUrl($initialiseTransaction['data']['redirectEndpoint']);

            /*
            $this->loadLayout();

            $block = $this->getLayout()->createBlock(
                'Mage_Core_Block_Template',
                'payright',
                array('template' => 'payright/redirect.phtml')
            )->setData('builtappendpoint', $builtAppUrl)
                ->setData('checkoutId', $initialiseTransaction['data']['checkoutId']);

            $this->getLayout()->getBlock('content')->append($block);
            $this->renderLayout();
            */
        }
    }

    // The response action is triggered when your gateway sends back a response after processing the customer's payment
    public function responseAction() {
        // $this->getRequest()->isGet()

        /*
        /* Your gateway's code to make sure the response you
        /* just got is from the gateway and not from some weirdo.
        /* This generally has some checksum or other checks,
        /* and is provided by the gateway.
        /* For now, we assume that the gateway's response is valid
         */
        $orderId = Mage::getSingleton('checkout/session')->getLastRealOrderId();
        $checkoutId = Mage::app()->getRequest()->getParam('checkoutId');
        $status = Mage::app()->getRequest()->getParam('status');

        // TODO Add validation from response source. For example, get 'Access Token'?
        // $validated = true;

        // Get plan data for the payright transaction.
        $json = Mage::helper('payright')->getPlanDataByCheckoutId($checkoutId);
        $result = $json['data'];

        $resPlanId = isset($result->planId) ? $result->planId : null;
        $resPlanNumber = isset($result->planNumber) ? $result->planNumber : null;
        $resStatus = isset($result->status) ? $result->status : null; // TODO Not using it YET, using 'status' URL param.

        // TODO Update status check, from query param to work with response status value.
        // if ($validated) {
            if ($status != "COMPLETE") {
                $this->cancelAction();
            } else {
                // Payment was successful, so update the order's state, send order email and move to the success page
                $order = Mage::getModel('sales/order');
                $order->loadByIncrementId($orderId);
                $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true, 'Gateway has authorized the payment.');

                // Set Payright details.
                $order->setPayrightPlanId($resPlanId);
                $order->setPayrightCheckoutId($checkoutId); // TODO What's this for? It was $order->setPayrightCheckoutId($ecom), unsure.

                // Send customer the email of order
                $order->sendNewOrderEmail();
                $order->setEmailSent(true);

                // Save the order
                $order->save();

                // Save order ID in sales_flat_order_payment table
                $payment = $order->getPayment();
                $payment->setData('payright_plan_number', $resPlanNumber);
                $payment->save();

                // Since we're done, unset quote Id
                Mage::getSingleton('checkout/session')->unsQuoteId();

                // Redirect customer to success page
                Mage_Core_Controller_Varien_Action::_redirect('checkout/onepage/success', array('_secure' => true));
            }
        //} else {
            // There is a problem in the response we got
            // $this->cancelAction();
            // Mage_Core_Controller_Varien_Action::_redirect('checkout/onepage/failure', array('_secure' => true));
        //}
    }

    // The cancel action is triggered when an order is to be cancelled
    public function cancelAction() {
        $checkoutId = Mage::app()->getRequest()->getParam('checkoutId');

        // do the call to getplan data for the payright transaction.
        $json = Mage::helper('payright')->getPlanDataByCheckoutId($checkoutId);
        $result = json_decode($json['data']);

        $this->_handleCart(false, true);

        Mage::getSingleton('checkout/session')->addError(Mage::helper('checkout')->__("Payright Checkout has been cancelled."));

        $this->_redirect('checkout/cart');

        return;
    }

    public function buildRedirectUrl($envConfigArray) {
        return $envConfigArray['AppEndpoint'];
    }

    private function _handleCart($isRestoreCart, $cancel = false) {

        // If for redirectAction() function, then also "save" (restore) the last quote of order given
        if ($isRestoreCart) {
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