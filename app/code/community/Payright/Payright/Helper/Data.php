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
            $client->setMethod(Zend_Http_Client::GET);

            return json_decode($client->request()->getBody(), true);
        } catch (\Exception $e) {
            return $e;
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
     * @return string
     */
    public function calculateSingleProductInstallment($saleAmount) {
        // Get 'Access Token' from system configuration
        $authToken = $this->getAccessToken();

        // Check if 'Access Token' configured in system configuration
        if ($authToken) {
            // Get 'data' = 'rates', 'establishmentFees' and 'otherFees'
            $data = $this->performApiGetRates();

            $rates = $data['rates'];
            $establishmentFees = $data['establishmentFees'];

            // We need the mentioned 'otherFees' below, to calculate for 'payment frequency'
            // and 'loan amount per repayment'
            $accountKeepingFee = $data['otherFees']['monthlyAccountKeepingFee'];
            $paymentProcessingFee = $data['otherFees']['paymentProcessingFee'];

            if (isset($rates)) {
                // TODO Re-enable the $payrightInstallmentApproval IF condition
                $payrightInstallmentApproval = $this->getMaximumSaleAmount($rates, $saleAmount);
                if (true) {
                    // if ($payrightInstallmentApproval == 0) {

                    // Get your 'loan term'. For example, term = 4 fortnights (28 weeks).
                    $loanTerm = $this->fetchLoanTermForSale($rates, $saleAmount);

                    // Get your 'minimum deposit amount', from 'rates' data received and sale amount.
                    $getMinDeposit = $this->calculateMinDeposit($rates, $saleAmount);

                    // Get your 'payment frequency', from 'monthly account keeping fee' and 'loan term'
                    $getPaymentFrequency = $this->getPaymentFrequency($accountKeepingFee, $loanTerm);

                    // Calculate and collect all 'number of repayments' and 'monthly account keeping fees'
                    $calculatedNumberOfRepayments = $getPaymentFrequency['numberOfRepayments'];
                    $calculatedAccountKeepingFees = $getPaymentFrequency['accountKeepingFees'];

                    // Get 'loan amount', for example: 'sale amount' - 'minimum deposit amount' = loan amount.
                    $loanAmount = $saleAmount - $getMinDeposit;

                    // For 'total credit required' output. Format the 'loan amount', into currency format.
                    $formattedLoanAmount = number_format((float)$loanAmount, 2, '.', '');

                    // Process 'establishment fees', from 'loan term' and 'establishment fees' (response)
                    $resEstablishmentFees = $this->getEstablishmentFees($loanTerm, $establishmentFees);

                    // TODO Keep or discard below? Currently, unused I think.
                    // $establishmentFeePerPayment = $resEstablishmentFees / $calculatedNumberOfRepayments;
                    // $loanAmountPerPayment = $formattedLoanAmount / $calculatedNumberOfRepayments;

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
                    $dataResponseArray['minDeposit'] = $getMinDeposit;
                    $dataResponseArray['totalCreditRequired'] = $this->totalCreditRequired($formattedLoanAmount, $resEstablishmentFees);
                    $dataResponseArray['accountKeepingFee'] = $accountKeepingFee;
                    $dataResponseArray['paymentProcessingFee'] = $paymentProcessingFee;
                    $dataResponseArray['saleAmount'] = $saleAmount;
                    $dataResponseArray['numberOfRepayments'] = $calculatedNumberOfRepayments;
                    $dataResponseArray['repaymentFrequency'] = 'Fortnightly';
                    $dataResponseArray['loanAmountPerPayment'] = $calculateRepayments;

                    return $dataResponseArray;
                } else {
                    return "exceed_amount"; // error 'exceed_amount' text
                }
            } else {
                return "rates_error";
            }
        } else {
            return "auth_token_error";
        }
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

    /**
     * Calculate minimum deposit that needs to be paid, based on sale amount
     *
     * @param array $getRates
     * @param int $saleAmount amount for purchased product
     * @return float min deposit
     */
    public function calculateMinDeposit($getRates, $saleAmount) {
        foreach ($getRates as $key => $value) {
            $per[] = $value["minimumDepositPercentage"];
        }

        if (isset($per)) {
            // $percentage = min($per);
            $value = 10 / 100 * $saleAmount;
            return money_format('%.2n', $value);
        } else {
            return money_format('%.2n', 0);
        }
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
     * @param float $saleAmount sale amount
     * @return float loan amount
     */
    public
    function fetchLoanTermForSale($rates, $saleAmount) {
        $ratesArray = null;
        //$generateLoanTerm = '';

        foreach ($rates as $key => $rate) {
            $ratesArray[$key]['term'] = $rate['term'];
            $ratesArray[$key]['minimumPurchase'] = $rate['minimumPurchase'];
            $ratesArray[$key]['maximumPurchase'] = $rate['maximumPurchase'];

            if (($saleAmount >= $ratesArray[$key]['minimumPurchase'] && $saleAmount <= $ratesArray[$key]['maximumPurchase'])) {
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
        // $fee_bandArray = null;
        // $feeBandCalculator = 0;

        foreach ($establishmentFees as $key => $estFee) {
            // $fee_bandArray[$key]['term'] = $estFee['term'];
            // $fee_bandArray[$key]['initialEstFee'] = $estFee['initialEstFee'];
            // $fee_bandArray[$key]['repeatEstFee'] = $estFee['repeatEstFee'];

            if ($estFee['term'] == $loanTerm) {
                $initialEstFee = $estFee['initialEstFee'];
            }

            // $feeBandCalculator++;
        }

        if (isset($initialEstFee)) {
            return $initialEstFee;
        } else {
            return 0;
        }
    }

    /**
     * Get the maximum limit for sale amount
     *
     * @param array $getRates get the rates for merchant
     * @param float $saleAmount price of purchased amount
     * @return int allowed loan limit in form 0 or 1, 0 means sale amount is still in limit and 1 is over limit
     */
    public function getMaximumSaleAmount($getRates, $saleAmount) {

        // Define 'loan limit boolean check, 0 = within / under limit and 1 = over limit.
        $chkLoanLimit = 0;

        // Declare $getVal[] array first time.
        $getVal[] = null;

        // Get all 'maximumPurchase' values from rates.
        foreach ($getRates as $key => $value) {
            // Build a 'maximumPurchase' array list for later use.
            $getVal[] = $value["maximumPurchase"];
        }

        // If 'sale amount' is over the maximum 'allowed loan limit', then true.
        // AKA if 'sale amount' > max loan limit.
        if (max($getVal) < $saleAmount) {
            $chkLoanLimit = 1;
        }

        // Else, still within / under the 'allowed loan limit'.
        return $chkLoanLimit;
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
        // If the Payright 'Environment Mode' is set to 'sandbox', then get the 'sandbox' API endpoints.
        $envMode = $this->getConfigValue('sandbox');

        try {
            if ($envMode == '1') {
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
            return 'Error: '.$e->getMessage()."\n";
        }
    }

}
