<?php
/**
 * Magento 2 extensions for PayRight Payment
 *
 * @author PayRight
 * @copyright 2016-2018 PayRight https://www.payright.com.au
 */

class Payright_Payright_Helper_Data extends Mage_Core_Helper_Abstract {

    /**
     * Get 'Access Token' of merchant store, from admin configuration settings.
     * For more information, please review the README.md file.
     *
     * @return mixed
     */
    public function getAccessToken() {
        return $this->getConfigValue('accesstoken');
    }

    /**
     * Get 'Redirect Url' of merchant store, from admin configuration settings.
     * For more information, please review the README.md file.
     *
     * @return mixed
     */
    public function getRedirectUrl() {
        return $this->getConfigValue('redirecturl');
    }

    /**
     * Get customer payment plan from new checkout, by 'checkoutId'.
     * Only used for 'responseAction' function in PaymentController.
     *
     * @param $merchantReference
     * @param $saleAmount
     * @param $redirectUrl
     * @param $expiresAt
     * @return array
     */
    public function performApiCheckout($merchantReference, $saleAmount, $redirectUrl, $expiresAt) {
        // Get the API Url endpoint, from 'config.xml'
        $getEnvironmentEndpoints = $this->getEnvironmentEndpoints();
        $apiEndpoint = $getEnvironmentEndpoints['ApiUrl'];

        // Prepare json raw data payload
        $data = array(
            'merchantReference' => $merchantReference,
            'saleAmount' => $saleAmount,
            'type' => 'standard',
            'redirectUrl' => $redirectUrl,
            'expiresAt' => $expiresAt
        );

        // Define API POST call, to create new checkout
        $client = new Zend_Http_Client($apiEndpoint . "api/v1/checkouts");
        $client->setHeaders(
            array(
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->getAccessToken()
            )
        );
        $client->setConfig(array('timeout' => 15));

        // Lastly, define POST method, with json body data sent
        $response = $client->setRawData(json_encode($data), 'application/json')->request('POST');

        try {
            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            return json_decode($response->getBody(), true);
        }
    }

    /**
     * Get customer payment plan from new checkout, by 'checkoutId'.
     * Only used for 'responseAction'.
     *
     * @param $checkoutId
     * @return Exception
     */
    public function getPlanDataByCheckoutId($checkoutId) {
        // Get the API Url endpoint, from 'config.xml'
        $getEnvironmentEndpoints = $this->getEnvironmentEndpoints();
        $apiEndpoint = $getEnvironmentEndpoints['ApiUrl'];

        $id = $checkoutId;

        try {
            // Define API GET call for 'data', for the 'responseAction'.
            $client = new Zend_Http_Client($apiEndpoint . "api/v1/checkouts/" . $id);
            $client->setHeaders(
                array(
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' . $this->getAccessToken()
                )
            );
            $client->setConfig(array('timeout' => 15));

            return json_decode($client->request()->getBody(), true);
        } catch (\Exception $e) {
            return $e;
        }
    }

    /**
     * Activate Payright payment plan. Please note, this is a PUT request but we need POST for Zend_Http_Client.
     *
     * @param $checkoutId
     * @return array
     */
    public function activatePlan($checkoutId) {
        // Get the API Url endpoint, from 'config.xml'
        $getEnvironmentEndpoints = $this->getEnvironmentEndpoints();
        $apiEndpoint = $getEnvironmentEndpoints['ApiUrl'];

        // Capture 'checkoutId' from parameter
        $cId = $checkoutId;

        // Define API POST call, to create new checkout
        $client = new Zend_Http_Client($apiEndpoint . "api/v1/checkouts/" . $cId . "/activate");
        $client->setHeaders(
            array(
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->getAccessToken()
            )
        );
        $client->setConfig(array('timeout' => 15));

        // Lastly, define PUT method (we use POST for Zend_Http_Client), with json body data sent
        $response = $client->request('PUT');

        // Response is data->message = 'Checkout activated'
        // else data->error and data->message
        try {
            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            return json_decode($response->getBody(), true);
        }
    }

    /**
     * Retrieve data of 'rates', 'establishmentFees' and 'otherFees'.
     *
     * @return array
     */
    public function performApiGetRates() {
        // Get the API Url endpoint, from 'config.xml'
        $getEnvironmentEndpoints = $this->getEnvironmentEndpoints();
        $apiEndpoint = $getEnvironmentEndpoints['ApiUrl'];

        // Define API GET call for 'data' = 'rates', 'establishmentFees' and 'otherFees'
        $client = new Zend_Http_Client($apiEndpoint . "api/v1/merchant/configuration");
        $client->setHeaders(
            array(
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $this->getAccessToken()
            )
        );
        $client->setConfig(array('timeout' => 15));

        // JSON decode the 'data' response
        $response = json_decode($client->request()->getBody(), true);

        // Define an empty array, to store breakdown of the 'data'
        $returnArray[] = null;

        // Simplify 'data' into smaller array datasets
        if (!isset($response['error']) && isset($response['data']['rates'])) {
            $returnArray['rates'] = $response['data']['rates'];
            $returnArray['establishmentFees'] = $response['data']['establishmentFees'];
            $returnArray['otherFees'] = $response['data']['otherFees'];

            return $returnArray;
        } else {
            // Return error response, 'code' and 'message' will be received
            return $response['error'];
        }
    }

    /**
     * Calculate product installments, for current product.
     * The 'Block/Catalog/Installments.php' performs 'renderInstallments()'.
     *
     * @param $saleAmount
     * @return string
     */
    public function calculateSingleProductInstallment($saleAmount) {
        // Get 'Access Token' from system configuration
        $authToken = $this->getAccessToken();

        // Get 'data' = 'rates', 'establishmentFees' and 'otherFees'
        $data = $this->performApiGetRates();

        // Breakdown fields of 'data'
        $rates = $data['rates'];
        $establishmentFees = $data['establishmentFees'];

        // We need the mentioned 'otherFees' below, to calculate for 'payment frequency'
        // and 'loan amount per repayment'
        $accountKeepingFee = $data['otherFees']['monthlyAccountKeepingFee'];
        $paymentProcessingFee = $data['otherFees']['paymentProcessingFee'];

        // Check if the sale amount falls within the rate card, and determine lowest term and deposit
        $minimumDepositAndTerm = $this->getMinimumDepositAndTerm($rates, $saleAmount);

        // Breakdown fields of 'minimumDepositAndTerm'
        $depositAmount = $minimumDepositAndTerm['minimumDepositAmount']; // minimum deposit amount = deposit amount
        $term = $minimumDepositAndTerm['minimumDepositTerm']; // loan term = term
        $loanAmount = $saleAmount - $depositAmount; // 'minimum deposit amount' = 'deposit amount'.

        // Begin 'Catch Error Types'
        if (!isset($rates)) {
            return "rates_error";
        }

        if (empty($minimumDepositAndTerm)) {
            return "exceed_amount";
        }

        if (!isset($authToken)) {
            return "auth_token_error";
        }
        // End 'Catch Error Types'

        // Get your 'payment frequency', from 'monthly account keeping fee' and 'loan term'
        $getPaymentFrequency = $this->getPaymentFrequency($accountKeepingFee, $term);

        // Calculate and collect all 'number of repayments' and 'monthly account keeping fees'
        $calculatedNumberOfRepayments = $getPaymentFrequency['numberOfRepayments'];
        $calculatedAccountKeepingFees = $getPaymentFrequency['accountKeepingFees'];

        // For 'total credit required' output. Format the 'loan amount', into currency format.
        $formattedLoanAmount = number_format((float)$loanAmount, 2, '.', '');

        // Process 'establishment fees', from 'loan term' and 'establishment fees' (response)
        $resEstablishmentFees = $this->getEstablishmentFees($term, $establishmentFees);

        // Calculate repayment, to get 'loan amount' as 'loan amount per payment'.
        $calculateRepayments = $this->calculateRepayment(
            $calculatedNumberOfRepayments,
            $calculatedAccountKeepingFees,
            $resEstablishmentFees,
            $loanAmount,
            $paymentProcessingFee);

        // The entire breakdown for calculated single product 'installment'.
        $dataResponseArray['loanAmount'] = $loanAmount;
        $dataResponseArray['establishmentFee'] = $resEstablishmentFees;
        $dataResponseArray['minDeposit'] = $depositAmount;
        $dataResponseArray['totalCreditRequired'] = $this->totalCreditRequired($formattedLoanAmount, $resEstablishmentFees);
        $dataResponseArray['accountKeepingFee'] = $accountKeepingFee;
        $dataResponseArray['paymentProcessingFee'] = $paymentProcessingFee;
        $dataResponseArray['saleAmount'] = $saleAmount;
        $dataResponseArray['numberOfRepayments'] = $calculatedNumberOfRepayments;
        $dataResponseArray['repaymentFrequency'] = 'Fortnightly';
        $dataResponseArray['loanAmountPerPayment'] = $calculateRepayments;

        return $dataResponseArray;
    }

    /**
     * Calculate repayment installment
     *
     * @param int $numberOfRepayments term for sale amount
     * @param int $accountKeepingFee account keeping fees
     * @param int $establishmentFees establishment fees
     * @param int $loanAmount loan amount
     * @param int $paymentProcessingFee processing fees for loan amount
     * @return string number format amount
     */
    public function calculateRepayment(
        $numberOfRepayments,
        $accountKeepingFee,
        $establishmentFees,
        $loanAmount,
        $paymentProcessingFee) {
        $repaymentAmountInit = ((floatval($establishmentFees) + floatval($loanAmount)) / $numberOfRepayments);
        $repaymentAmount = floatval($repaymentAmountInit) + floatval($accountKeepingFee) + floatval($paymentProcessingFee);

        return number_format($repaymentAmount, 2, '.', ',');
    }

    /*
     * Get minimum deposit amount + percentage + term (loan term)
     *
     */
    function getMinimumDepositAndTerm($rates, $saleAmount) {
        // Iterate through each term, apply the minimum deposit to the sale amount and see if it fits in the rate card. If not found, move to a higher term
        foreach ($rates as $rate) {
            $minimumDepositPercentage = $rate['minimumDepositPercentage'];
            $depositAmount = $saleAmount * ($minimumDepositPercentage / 100);
            $loanAmount = $saleAmount - $depositAmount;

            // Check if loan amount is within range
            if ($loanAmount >= $rate['minimumPurchase'] && $loanAmount <= $rate['maximumPurchase']) {
                return [
                    'minimumDepositPercentage' => $minimumDepositPercentage,
                    // If above PHP 7.4 check, source: https://www.php.net/manual/en/function.money-format.php
                    'minimumDepositAmount' => function_exists('money_format') ? money_format('%.2n', $depositAmount) : sprintf('%01.2f', $depositAmount),
                    'minimumDepositTerm' => $rate['term'],
                ];
            }
        }
        // No valid term and deposit found
        return [];
    }

    /**
     * Get payment frequency for loan amount.
     *
     * @param float $accountKeepingFee account keeping fees
     * @param int $loanTerm loan term
     * @return mixed
     */
    public
    function getPaymentFrequency($accountKeepingFee, $loanTerm) {
        $repaymentFrequency = 'Fortnightly';

        if ($repaymentFrequency == 'Weekly') {
            $j = floor($loanTerm * (52 / 12));
            $o = $accountKeepingFee * 12 / 52;
        }

        if ($repaymentFrequency == 'Fortnightly') {
            $j = floor($loanTerm * (26 / 12));
            if ($loanTerm == 3) {
                $j = 7;
            }
            $o = $accountKeepingFee * 12 / 26;
        }

        if ($repaymentFrequency == 'Monthly') {
            $j = parseInt(k);
            $o = $accountKeepingFee;
        }

        $numberOfRepayments = $j;
        $accountKeepingFee = $o;

        $returnArray['numberOfRepayments'] = $numberOfRepayments;
        $returnArray['accountKeepingFees'] = round($accountKeepingFee, 2);

        return $returnArray;
    }

    /**
     * Get the loan term for sale amount.
     *
     * @param array $rates rates for merchant
     * @param float $loanAmount the loan amount
     * @return float minimum loan term
     */
    public
    function fetchLoanTermForSale($rates, $loanAmount) {
        $ratesArray = null;
        //$generateLoanTerm = '';

        foreach ($rates as $key => $rate) {
            $ratesArray[$key]['term'] = $rate['term'];
            $ratesArray[$key]['minimumPurchase'] = $rate['minimumPurchase'];
            $ratesArray[$key]['maximumPurchase'] = $rate['maximumPurchase'];

            if (($loanAmount >= $ratesArray[$key]['minimumPurchase'] && $loanAmount <= $ratesArray[$key]['maximumPurchase'])) {
                $generateLoanTerm[] = $ratesArray[$key]['term'];
            }
        }

        if (isset($generateLoanTerm)) {
            return min($generateLoanTerm);
        } else {
            return 0;
        }
    }

    /**
     * Get the establishment fees
     *
     * @param int $loanTerm loan term for sale amount
     * @param $establishmentFees
     * @return string $h establishment fees
     */
    public
    function getEstablishmentFees($loanTerm, $establishmentFees) {
        foreach ($establishmentFees as $key => $estFee) {
            if ($estFee['term'] == $loanTerm) {
                $initialEstFee = $estFee['initialEstFee'];
            }
        }

        if (isset($initialEstFee)) {
            return $initialEstFee;
        } else {
            return 0;
        }
    }

    /**
     * Get the total credit required.
     *
     * @param int $loanAmount lending amount
     * @param float $establishmentFees establishmentFees
     * @return float total credit allowed
     */
    public
    static function totalCreditRequired($loanAmount, $establishmentFees) {
        $totalCreditRequired = (floatval($loanAmount) + floatval($establishmentFees));

        return number_format((float)$totalCreditRequired, 2, '.', '');
    }

    /**
     * Get your system config and value.
     *
     * @param string $field system configuration field name
     * @return string system configuration field value
     */
    public
    function getConfigValue($field) {
        $store = Mage::app()->getStore()->getStoreId();
        return Mage::getStoreConfig('payment/payrightcheckout/' . $field, $store);
    }

    /**
     * Get API endpoints, from config.xml
     *
     * @return string Payright API URL endpoints
     */
    public
    function getEnvironmentEndpoints() {
        // Get specified region / country
        $region = $this->getConfigValue('region');

        // If the Payright 'Environment Mode' is set to 'sandbox', then get the 'sandbox' API endpoints.
        $envMode = $this->getConfigValue('sandbox');


        try {
            if ($envMode == '1') {
                // Get region / country setting
                if ($region == '0') {
                    $sandboxApiUrl = Mage::getConfig()->getNode('global/payright/environments/sandbox')->api_url_au;
                    $sandboxAppEndpoint = Mage::getConfig()->getNode('global/payright/environments/sandbox')->web_url_au;
                } else {
                    $sandboxApiUrl = Mage::getConfig()->getNode('global/payright/environments/sandbox')->api_url_nz;
                    $sandboxAppEndpoint = Mage::getConfig()->getNode('global/payright/environments/sandbox')->web_url_nz;
                }

                $returnEndpoints['ApiUrl'] = $sandboxApiUrl;
                $returnEndpoints['AppEndpoint'] = $sandboxAppEndpoint;
            } else {
                // Get region / country setting
                if ($region == '0') {
                    $productionApiUrl = Mage::getConfig()->getNode('global/payright/environments/production')->api_url_au;
                    $productionEndpoint = Mage::getConfig()->getNode('global/payright/environments/production')->web_url_au;
                } else {
                    $productionApiUrl = Mage::getConfig()->getNode('global/payright/environments/production')->api_url_nz;
                    $productionEndpoint = Mage::getConfig()->getNode('global/payright/environments/production')->web_url_nz;
                }

                $returnEndpoints['ApiUrl'] = $productionApiUrl;
                $returnEndpoints['AppEndpoint'] = $productionEndpoint;
            }
            return $returnEndpoints;
        } catch (Exception $e) {
            return 'Error: ' . $e->getMessage() . "\n";
        }
    }

}
