<?php

// app/code/local/Envato/Custompaymentmethod/controllers/PaymentController.php
class Payright_Payright_PaymentController extends Mage_Core_Controller_Front_Action {

    /**
     * The redirect action is triggered when customer places an order, redirected to Payright Checkout portal.
     *
     *
     */
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
            // $merchantReference = "MagePayright_" . $orderId;

            // Generate 'expiresAt', set to expire 6 months from today's datetime.
            $dt = new DateTime();
            $interval = new DateInterval('P6M');
            $dt->add($interval);
            $dt->setTimeZone(new DateTimeZone('UTC'));
            $expiresAt = $dt->format('Y-m-d\TH-i-s.\0\0\0\Z');

            // Capture the 'orderId', for further processing.
            $capturedOrderId = $orderId;

            // Build 'merchantReference'
            $merchantReference = "MagePayright_" . $capturedOrderId;

            // Use Magento to save Order Id
            Mage::getSingleton('core/session')->setSaveOrderId($capturedOrderId);

            // Initialize the Payright transaction. To get the 'checkoutId'.
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

            // Restore cart / quote - for users who click 'Back' browser button
            $this->_handleCart(true);

            // Define the 'redirectEndpoint'
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

    /**
     * The response action is triggered when your gateway sends back a response after processing the customer's payment.
     *
     *
     */
    public function responseAction() {
        // $this->getRequest()->isGet();

        // Get all URL parameters
        // $params = Mage::app()->getRequest()->getParams();
        $params = $this->getRequest()->getParams();

        // Breakdown URL parameters received back
        $checkoutId = $params['checkoutId'];
        $status = $params['status'];

        echo $checkoutId." ".$status;

        // $checkoutId = Mage::app()->getRequest()->getParam('checkoutId');
        // $status = Mage::app()->getRequest()->getParam('status');

        // TODO Add validation from response source. For example, get 'Access Token'?
        // $validated = true;

        // Get plan data for the Payright transaction
        $json = Mage::helper('payright')->getPlanDataByCheckoutId($checkoutId);

        var_dump($json);

        // TODO [A] Backup 'Order Id' value from Magento session, testing if works well.
        // $resOrderId = Mage::getSingleton('checkout/session')->getLastRealOrderId();
        $resOrderId = Mage::getSingleton('core/session')->getSaveOrderId();

        // Retrieve specific data, and sanitize / clean with string manipulation
        $resCheckoutId = isset($json["data"]["id"]) ? $json["data"]["id"] : null;
        // TODO [A] Re-enable when 'getPlanDataByCheckoutId' bug is fixed
        // $resOrderId = isset($json["data"]["merchantReference"]) ? substr($json["data"]["merchantReference"], strlen("MagePayright_")) : null;
        $resPlanId = isset($json["data"]["planId"]) ? $json["data"]["planId"] : null;
        $resPlanNumber = isset($json["data"]["planNumber"]) ? $json["data"]["planNumber"] : null;
        $resStatus = isset($json["data"]["status"]) ? $json["data"]["status"] : null; // TODO Not using it YET, using 'status' URL param.

        // TODO Update status check, from query param to work with response status value.
        // if ($validated) {
        if ($status != "COMPLETE") {
            $this->cancelAction();
        } else {
            // Payment was successful, so update the order's state, send order email and move to the success page
            $order = Mage::getModel('sales/order');
            $order->loadByIncrementId($resOrderId);
            $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true, 'Gateway has authorized the payment.');

            // Set Payright details.
            $order->setPayrightPlanId($resPlanId);
            $order->setPayrightCheckoutId($resCheckoutId); // TODO What's this for? It was $order->setPayrightCheckoutId($ecom), unsure.

            // TODO sendNewOrderEmail() - Uncaught TypeError: Argument 1 passed to Mage_Payment_Helper_Data::getInfoBlock() must be an
            // instance of Mage_Payment_Model_Info, boolean given
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

            // TODO [A] Magento session unset SaveOrderId
            Mage::getSingleton('checkout/session')->unsSaveOrderId();

            // Redirect customer to success page
            Mage_Core_Controller_Varien_Action::_redirect('checkout/onepage/success', array('_secure' => true));

            // $this->_redirect('checkout/onepage/success', array('_secure' => true));
        }
        //} else {
        // There is a problem in the response we got
        // $this->cancelAction();
        // Mage_Core_Controller_Varien_Action::_redirect('checkout/onepage/failure', array('_secure' => true));
        //}
    }

    /**
     * The cancel action is triggered when an order is to be cancelled.
     *
     *
     */
    public function cancelAction() {
        // Get all URL parameters
        $params = Mage::app()->getRequest()->getParams();

        // Breakdown URL parameters received back
        $checkoutId = $params['checkoutId'];
        $status = $params['status'];

        // do the call to getplan data for the payright transaction.
        $json = Mage::helper('payright')->getPlanDataByCheckoutId($checkoutId);

        // Retrieve specific data, and sanitize / clean with string manipulation
        // $resCheckoutId = isset($json["data"]["id"]) ? $json["data"]["id"] : null;
        // $resOrderId = isset($json["data"]["merchantReference"]) ? substr($json["data"]["merchantReference"], strlen("MagePayright_")) : null;
        $resPlanId = isset($json["data"]["planId"]) ? $json["data"]["planId"] : null;
        // $resPlanNumber = isset($json["data"]["planNumber"]) ? $json["data"]["planNumber"] : null;
        // $resStatus = isset($json["data"]["status"]) ? $json["data"]["status"] : null; // TODO Not using it YET, using 'status' URL param.

        if (isset($status)) {
            if ($status != "COMPLETE" && $status != "DECLINE") {
                $helper = Mage::helper('payright')->planStatusChange($resPlanId, 'Cancelled');
            }
        }

        // Reset or clear all shopping cart items.=
        $this->_handleCart(false, true);

        // Display message to customer, that order / checkout has been cancelled
        Mage::getSingleton('checkout/session')->addError(Mage::helper('checkout')->__("Payright Checkout has been cancelled."));

        // Redirect customer back to shopping cart
        $this->_redirect('checkout/cart');
    }

    /**
     *
     * Build redirect url.
     *
     * @param $envConfigArray
     * @return mixed
     */
    public function buildRedirectUrl($envConfigArray) {
        return $envConfigArray['AppEndpoint'];
    }

    /**
     *
     * Toggle to save cart details, based on order status triggers.
     *
     * @param $isRestoreCart
     * @param false $cancel
     */
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