<?php
/**
 * Magento 2 extensions for PayRight Payment
 *
 * @author PayRight
 * @copyright 2016-2018 PayRight https://www.payright.com.au
 */

class Payright_Payright_Helper_Data extends Mage_Core_Helper_Abstract {

    /**
     * Get 'Access Token' of merchant store.
     * For more information, please review the README.md file.
     *
     * @return mixed
     */
    public function getAccessToken() {
        return $this->getConfigValue('accesstoken');
    }

    /**
     * Get 'Redirect Url' from admin configuration settings.
     *
     * @return mixed
     */
    public function getRedirectUrl() {
        return $this->getConfigValue('redirecturl');
    }

    public function performApiCheckout($merchantReference, $saleAmount, $redirectUrl, $expiresAt) {
        $apiURL = "api/v1/checkouts";

        $data = array(
            'merchantReference' => $merchantReference,
            'saleAmount' => $saleAmount,
            'type' => 'standard',
            'redirectUrl' => $redirectUrl,
            'expiresAt' => $expiresAt
        );

        $response = $this->callPayrightAPI($data, $apiURL, $this->getAccessToken());

        if (!isset($response['error'])) {
            return $response;
        } else {
            return "Error";
            // return $response['error']['status'];
        }
    }

    /*
     *
     */
    public function getPlanDataByCheckoutId($checkoutId) {
        $apiURL = "api/v1/checkouts/";

        $data = array(
            'checkoutId' => $checkoutId,
        );

        $response = $this->callPayrightAPI($data, $apiURL, $this->getAccessToken());

        if (!isset($response['error'])) {
            return $response;
        } else {
            return "Error";
        }
    }

    /*
     * Retrive data of rates, establishmentFees and otherFees
     */
    public function performApiGetRates() {
        $apiURL = "api/v1/merchant/configuration";
        $authToken = $this->getAccessToken();

        $getEnvironmentEndpoints = $this->getEnvironmentEndpoints();
        $apiEndpoint = $getEnvironmentEndpoints['ApiUrl'];

        $client = new Zend_Http_Client($apiEndpoint . $apiURL);
        // $client->setMethod(Zend_Http_Client::GET); // default setMethod is already GET
        $client->setHeaders('Accept: application/json');
        $client->setHeaders('Authorization: Bearer ' . $authToken);
        $client->setConfig(array('timeout' => 15));

        $response = $client->request()->getBody();

        $returnArray = json_decode($response);

        if (!isset($response['error']) && isset($response['data']['rates'])) {
            // The 'rates' are json format, hence we need json_decode() with associative array
            // $returnArray['rates'] = Mage::helper('core')->jsonDecode($response['data']['rates']);
            $returnArray['rates'] = $response['data']['rates'];
            $returnArray['establishmentFees'] = $response['data']['establishmentFees'];
            $returnArray['otherFees'] = $response['data']['otherFees'];

            return $returnArray;
        } else {
            return $response['error'];
        }
    }

    /*
     * Calculate product installments, for current product.
     * The 'Block/Catalog/Installments.php' performs 'renderInstallments()'.
     *
     * @return string
     */
    public function calculateSingleProductInstallment($saleAmount) {
        $authToken = $this->getAccessToken();

        if ($authToken) {
            $data = $this->performApiGetRates();

            $getRates = $data['rates'];

            if (isset($getRates)) {
                $payrightInstallmentApproval = $this->getMaximumSaleAmount($getRates, $saleAmount);
                if (true) {
                // if ($payrightInstallmentApproval == 0) {
                    // Acquire 'establishment fees'
                    $establishmentFees = $data['establishmentFees'];

                    // We need the mentioned fees below, to calculate for 'payment frequency'
                    // and 'loan amount per repayment'
                    $accountKeepingFee = $data['otherFees']['monthlyAccountKeepingFee'];
                    $paymentProcessingFee = $data['otherFees']['paymentProcessingFee'];

                    // Get your 'loan term'. For example, term = 4 fortnights (28 weeks).
                    $loanTerm = $this->fetchLoanTermForSale($getRates, $saleAmount);

                    // Get your 'minimum deposit amount', from 'rates' data received and sale amount.
                    $getMinDeposit = $this->calculateMinDeposit($getRates, $saleAmount);

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

                    // TODO Keep or discard below? Currently, unused.
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
                    $dataResponseArray['accountKeepFees'] = $accountKeepingFee;
                    $dataResponseArray['processingFees'] = $paymentProcessingFee;
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
     *
     * @return string
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
     * @return float mindeposit
     */
    public function calculateMinDeposit($getRates, $saleAmount) {
        /*
        for ($i = 0; $i < count($getRates); $i++) {
            for ($l = 0; $l < count($getRates[$i]); $l++) {
                if ($getRates[$i]['term'] == 4) {
                    $per[] = $getRates[$i]['minimumDepositPercentage'];
                }
            }
        }
        */
        foreach ($getRates as $key => $value) {
            $per[] = $value["minimumDepositPercentage"];
        }

        if (isset($per)) {
            $percentage = min($per);
            $value = 10 / 100 * $saleAmount;
            return money_format('%.2n', $value);
        } else {
            return 0;
        }
    }

    /**
     * Payment frequancy for loan amount
     *
     * @param float $accountKeepingFee account keeping fees
     * @param int $loanTerm loan term
     * @param array $returnArray noofpayments and accountkeeping fees
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
     * Get the loan term for sale amount
     *
     * @param array $rates rates for merchant
     * @param float $saleAmount sale amount
     * @return float loanamount
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
        $fee_bandArray = null;
        $feeBandCalculator = 0;

        foreach ($establishmentFees as $key => $row) {
            $fee_bandArray[$key]['term'] = $row['term'];
            $fee_bandArray[$key]['initialEstFee'] = $row['initialEstFee'];
            $fee_bandArray[$key]['initialEstFee'] = $row['repeatEstFee'];

            if ($fee_bandArray[$key]['term'] == $loanTerm) {
                $h = $row['initialEstFee'];
            }

            $feeBandCalculator++;
        }

        if (isset($h)) {
            return $h;
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
     * Get the total credit required
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
     * @param null $data
     * @param $apiURL
     * @param false $authToken
     * @return string
     */
    public
    function callPayrightAPI($data = null, $apiURL, $authToken = false) {

        $getEnvironmentEndpoints = $this->getEnvironmentEndpoints();
        $apiEndpoint = $getEnvironmentEndpoints['ApiUrl'];

        $client = new Zend_Http_Client($apiEndpoint . $apiURL);
        $client->setMethod(Zend_Http_Client::POST);
        $client->setHeaders('Content-Type: application/json');
        $client->setHeaders('Accept: application/json');
        $client->setHeaders('Authorization: Bearer ' . $authToken);
        $client->setConfig(array('timeout' => 15));
        if ($data) {
            $client->setParameterPost($data);
        }

        try {
            $json = $client->request()->getBody();
            return Mage::helper('core')->jsonDecode($json);
        } catch (\Exception $e) {
            return "Error: API POST failed";
        }
    }

    public
    function getConfigValue($field) {
        $store = Mage::app()->getStore()->getStoreId();
        return Mage::getStoreConfig('payment/payrightcheckout/' . $field, $store);
    }

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
            echo 'Error: ', $e->getMessage(), "\n";
        }
    }

}
