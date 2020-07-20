<?php
/**
 * Magento 2 extensions for PayRight Payment
 *
 * @author PayRight
 * @copyright 2016-2018 PayRight https://www.payright.com.au
 */

class Payright_Payright_Helper_Data extends Mage_Core_Helper_Abstract
{
    public function DoApiCallPayright()
    {
        $apiURL = "oauth/token";

        $existingPayrightAccessToken  = Mage::getSingleton('customer/session')->getPayrightAccessToken();
        $existingPayrightRefreshToken = Mage::getSingleton('customer/session')->getPayrightRefereshToken();

        if (empty($existingPayrightAccessToken) && empty($existingPayrightRefreshToken)) {
            $data = array(
                "username"      => $this->getConfigValue('username'),
                "password"      => $this->getConfigValue('password'),
                "grant_type"    => 'password',
                "client_id"     => $this->getConfigValue('client_id'),
                "client_secret" => $this->getConfigValue('client_secret'),
            );

            $response = $this->callPayrightAPI($data, $apiURL);

            if (!isset($response['error'])) {
                if (array_key_exists('error', $response)) {
                    return false;
                } else {
                    $payrightAccessToken  = $response['access_token'];
                    $payrightRefreshToken = $response['refresh_token'];

                    Mage::getSingleton('customer/session')->setPayrightAccessToken($payrightAccessToken);
                    Mage::getSingleton('customer/session')->setPayrightRefereshToken($payrightRefreshToken);

                    $reponseArray['payrightAccessToken']  = $payrightAccessToken;
                    $reponseArray['payrightRefreshToken'] = $payrightRefreshToken;
                    $reponseArray['status']               = 'Authenticated';

                    return $reponseArray;
                }
            } else {
                return 'Error';
            }
        }

        return [
            'payrightAccessToken'  => $existingPayrightAccessToken,
            'payrightRefreshToken' => $existingPayrightRefreshToken,
            'status'                => 'Authenticated',
        ];
    }

    public function DoApiConfCallPayright($authToken)
    {
        $apiURL = "api/v1/configuration";

        $returnArray = array();

        $data = array(
            "merchantusername" => $this->getConfigValue('merchant_username'),
            "merchantpassword" => $this->getConfigValue('merchant_password'),
        );

        $response = $this->callPayrightAPI($data, $apiURL, $authToken);

        if (!isset($response['code']) && isset($response['data']['rates'])) {
            $returnArray['configToken']       = $response['data']['configToken'];
            $returnArray['rates']             = $response['data']['rates'];
            $returnArray['conf']              = $response['data']['conf'];
            $returnArray['establishment_fee'] = $response['data']['establishment_fee'];
            return $returnArray;
        } else {
            return 'Error';
        }
    }

    public function DoApiTransactionConfCallPayright($authToken)
    {
        $apiURL = "api/v1/initialTransactionConfiguration";

        $returnArray = array();

        $data = array(
            "merchantusername" => $this->getConfigValue('merchant_username'),
            "merchantpassword" => $this->getConfigValue('merchant_password'),
        );

        $response = $this->callPayrightAPI($data, $apiURL, $authToken);

        if (!isset($response['code']) && isset($response['data']['auth'])) {
            $returnArray['auth']              = $response['data']['auth'];
            $returnArray['configToken']       = $response['data']['configToken'];
            $returnArray['rates']             = $response['data']['rates'];
            $returnArray['conf']              = $response['data']['conf'];
            $returnArray['establishment_fee'] = $response['data']['establishment_fee'];
            return $returnArray;
        } else {
            return "Error";
        }
    }

    public function DoApiTransactionOverview($apiToken, $SugarAuthToken, $configToken, $amount)
    {
        $apiURL      = "api/v1/transactionOverview";
        $returnArray = array();

        $data = array(
            'Token'       => $SugarAuthToken,
            'ConfigToken' => $configToken,
            'saleamount'  => $amount,
        );

        $response = $this->callPayrightAPI($data, $apiURL, $SugarAuthToken);
        if (!isset($response['error'])) {
            $returnArray = $response['data'];
            return $returnArray;
        } else {
            return "Error";
        }
    }

    public function DoApiIntializeTransaction($apiToken, $SugarAuthToken, $configToken, $transData, $ecommClientId, $merchantReference)
    {
        $apiURL      = "api/v1/intialiseTransaction";
        $returnArray = array();

        $decodedTranscationdata = json_decode($transData);

  

        $data = array(
           'Token' => $SugarAuthToken,
           'ConfigToken' =>  $configToken,
           'transactiondata' => $transData,
           'totalAmount' => $decodedTranscationdata->transactionTotal,
           'clientId' => $ecommClientId,
           'merchantReference' => $merchantReference
        );



        $response = $this->callPayrightAPI($data, $apiURL, $SugarAuthToken);

      
      
        if (!isset($response['error'])) {
            $returnArray = $response;
            return $returnArray;
        } else {
            return "Error";
        }
    }

    public function planStatusChange($planId, $status)
    {
        $apiURL = "api/v1/changePlanStatus";

        $authToken              = $this->DoApiCallPayright();
        $getPayRightAccessToken = $authToken['payrightAccessToken'];

        $getApiConfiguration = $this->DoApiTransactionConfCallPayright($getPayRightAccessToken);
        $sugarToken          = $getApiConfiguration['auth']['auth-token'];
        $configToken         = $getApiConfiguration['configToken'];

        $returnArray = array();

        $data = array(
            'Token'       => $sugarToken,
            'ConfigToken' => $configToken,
            'id'          => $planId,
            'status'      => $status,
        );

        $response = $this->callPayrightAPI($data, $apiURL, $authToken);

        if (!isset($response['error'])) {
            $returnArray = $response['data'];
            return $returnArray;
        } else {
            return "Error";
        }
    }

    public function getPlanDataByToken($ecommerceToken)
    {
        $apiURL                 = "api/v1/getEcomTokenData";
        $authToken              = $this->DoApiCallPayright();
        $getPayRightAccessToken = $authToken['payrightAccessToken'];

        $returnArray = array();

        $data = array(
            'ecomToken' => $ecommerceToken,
        );

        $response = $this->callPayrightAPI($data, $apiURL, $authToken);

        if (!isset($response['error'])) {
            return $response;
        } else {
            return "Error";
        }
    }

    public function calculateSingleProductInstallment($saleAmount)
    {   

        $authToken = $this->DoApiCallPayright();

        if ($authToken != 'Error') {
            $configValues = $this->DoApiConfCallPayright($authToken['payrightAccessToken']);

            $getRates = $configValues['rates'];

            if (isset($getRates)) {
                $payrightInstallmentApproval = $this->getMaximumSaleAmount($getRates, $saleAmount);
                if ($payrightInstallmentApproval == 0) {
                    $establishment_fee    = $configValues['establishment_fee'];
                    $accountKeepingFees   = $configValues['conf']['Monthly Account Keeping Fee'];
                    $paymentProcessingFee = $configValues['conf']['Payment Processing Fee'];

                    $LoanTerm      = $this->fetchLoanTermForSale($getRates, $saleAmount);
                    $getMinDeposit = $this->calculateMinDeposit($getRates, $saleAmount, $LoanTerm);

                    $getFrequancy                 = $this->getPaymentFrequancy($accountKeepingFees, $LoanTerm);
                    $calculatedNoofRepayments     = $getFrequancy['numberofRepayments'];
                    $calculatedAccountKeepingFees = $getFrequancy['accountKeepingFees'];

                    $LoanAmount = $saleAmount - $getMinDeposit;

                    $formatedLoanAmount = number_format((float) $LoanAmount, 2, '.', '');

                    $resEstablishmentFees = $this->getEstablishmentFees($LoanTerm, $establishment_fee);

                    $establishmentFeePerPayment = $resEstablishmentFees / $calculatedNoofRepayments;
                    $loanAmountPerPayment       = $formatedLoanAmount / $calculatedNoofRepayments;

                    $CalculateRepayments = $this->calculateRepayment(
                        $calculatedNoofRepayments,
                        $calculatedAccountKeepingFees,
                        $resEstablishmentFees,
                        $LoanAmount,
                        $paymentProcessingFee);

                    $dataResponseArray['LoanAmount']           = $LoanAmount;
                    $dataResponseArray['EstablishmentFee']     = $resEstablishmentFees;
                    $dataResponseArray['minDeposit']           = $getMinDeposit;
                    $dataResponseArray['TotalCreditRequired']  = $this->TotalCreditRequired($formatedLoanAmount, $resEstablishmentFees);
                    $dataResponseArray['Accountkeepfees']      = $accountKeepingFees;
                    $dataResponseArray['processingfees']       = $paymentProcessingFee;
                    $dataResponseArray['saleAmount']           = $saleAmount;
                    $dataResponseArray['noofrepayments']       = $calculatedNoofRepayments;
                    $dataResponseArray['repaymentfrequency']   = 'Fortnightly';
                    $dataResponseArray['LoanAmountPerPayment'] = $CalculateRepayments;
                    return $dataResponseArray;
                } else {
                    return "exceed_amount";
                }
            } else {
                return "API Error";
            }
        } else {
            return "API Error";
        }
    }

    /**
     * Calculate Repayment installment
     * @param int $numberOfRepayments term for sale amount
     * @param int $AccountKeepingFees account keeping fees
     * @param int $establishmentFees establishment fees
     * @param int $LoanAmount loan amount
     * @param int $paymentProcessingFee processing fees for loan amount
     *
     */

    public function calculateRepayment($numberOfRepayments, $AccountKeepingFees, $establishmentFees, $LoanAmount, $paymentProcessingFee)
    {
        $RepaymentAmountInit = ((floatval($establishmentFees) + floatval($LoanAmount)) / $numberOfRepayments);
        $RepaymentAmount     = floatval($RepaymentAmountInit) + floatval($AccountKeepingFees) + floatval($paymentProcessingFee);
        return number_format($RepaymentAmount, 2, '.', ',');
    }

    /**
     * Calculate Minimum deposit trhat needs to be pay for sale amount
     * @param array $getRates
     * @param int $saleAmount amount for purchased product
     * @return float mindeposit
     */

    public function calculateMinDeposit($getRates, $saleAmount, $loanTerm)
    {
        for ($i = 0; $i < count($getRates); $i++) {
            for ($l = 0; $l < count($getRates[$i]); $l++) {
                if ($getRates[$i][2] == $loanTerm) {
                    $per[] = $getRates[$i][1];
                }
            }
        }

        if (isset($per)) {
            $percentage = min($per);
            $value      = $percentage / 100 * $saleAmount;
            return money_format('%.2n', $value);
        } else {
            return 0;
        }
    }

    /**
     * Payment frequancy for loan amount
     * @param float $accountKeepingFees account keeping fees
     * @param int $LoanTerm loan term
     * @param array $returnArray noofpayments and accountkeeping fees
     */

    public function getPaymentFrequancy($accountKeepingFees, $LoanTerm)
    {
        $RepaymentFrequecy = 'Fortnightly';

        if ($RepaymentFrequecy == 'Weekly') {
            $j = floor($LoanTerm * (52 / 12));
            $o = $accountKeepingFees * 12 / 52;
        }

        if ($RepaymentFrequecy == 'Fortnightly') {
            $j = floor($LoanTerm * (26 / 12));
            if ($LoanTerm == 3) {
                $j = 7;
            }
            $o = $accountKeepingFees * 12 / 26;
        }

        if ($RepaymentFrequecy == 'Monthly') {
            $j = parseInt(k);
            $o = $accountKeepingFees;
        }

        $numberofRepayments = $j;
        $accountKeepingFees = $o;

        $returnArray['numberofRepayments'] = $numberofRepayments;
        $returnArray['accountKeepingFees'] = round($accountKeepingFees, 2);

        return $returnArray;
    }

    /**
     * Get the loan term for sale amount
     * @param array $rates rates for merchant
     * @param float $saleAmount sale amount
     * @return float loanamount
     */

    public function fetchLoanTermForSale($rates, $saleAmount)
    {
        $ratesArray = array();
        //$generateLoanTerm = '';

        foreach ($rates as $key => $rate) {
            $ratesArray[$key]['Term'] = $rate['2'];
            $ratesArray[$key]['Min']  = $rate['3'];
            $ratesArray[$key]['Max']  = $rate['4'];

            if (($saleAmount >= $ratesArray[$key]['Min'] && $saleAmount <= $ratesArray[$key]['Max'])) {
                $generateLoanTerm[] = $ratesArray[$key]['Term'];
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
     * @param int $loanTerm loan term for sale amount
     * @return  calculated establishment fees
     */

    public function getEstablishmentFees($LoanTerm, $establishmentFees)
    {
        $fee_bandArray     = array();
        $feebandCalculator = 0;

        foreach ($establishmentFees as $key => $row) {
            $fee_bandArray[$key]['term']            = $row['term'];
            $fee_bandArray[$key]['initial_est_fee'] = $row['initial_est_fee'];
            $fee_bandArray[$key]['repeat_est_fee']  = $row['repeat_est_fee'];

            if ($fee_bandArray[$key]['term'] == $LoanTerm) {
                $h = $row['initial_est_fee'];
            }

            $feebandCalculator++;
        }
        if (isset($h)) {
            return $h;
        } else {
            return 0;
        }
    }

    /**
     * Get the maximum limit for sale amount
     * @param array $getRates get the rates for merchant
     * @param float $saleAmount price of purchased amount
     * @return int allowed loanlimit in form 0 or 1, 0 means sale amount is still in limit and 1 is over limit
     */

    public function getMaximumSaleAmount($getRates, $saleAmount)
    {
        $chkLoanlimit = 0;

        $keys = array_keys($getRates);

        //print_r($keys);

        for ($i = 0; $i < count($getRates); $i++) {
            foreach ($getRates[$keys[$i]] as $key => $value) {
                if ($key == 4) {
                    $getVal[] = $value;
                }
            }
        }

        if (max($getVal) < $saleAmount) {
            $chkLoanlimit = 1;
        }

        return $chkLoanlimit;
    }

    /**
     * Get the total credit required
     * @param int $loanAmount lending amount
     * @param float $establishmentFees establishmentFees
     * @return float total credit allowed
     */

    public static function TotalCreditRequired($LoanAmount, $establishmentFees)
    {
        $totalCreditRequired = (floatval($LoanAmount) + floatval($establishmentFees));
        return number_format((float) $totalCreditRequired, 2, '.', '');
    }

    public function callPayrightAPI($data, $apiURL, $authToken = false)
    {

        $getEnviromentEndpoints = $this->getEnviromentEndpoints();
        $ApiEndpoint = $getEnviromentEndpoints['ApiUrl']; 

        $client = new Zend_Http_Client($ApiEndpoint . $apiURL);
        $client->setMethod(Zend_Http_Client::POST);
        $client->setHeaders(array('Content-Type: application/json', 'Accept: application/json', 'Authorization:' . $authToken));
        $client->setConfig(array('timeout' => 15));
        $client->setParameterPost($data);

        try {
            $json           = $client->request()->getBody();
            $jsonDecoeddata = Mage::helper('core')->jsonDecode($json);
            return $jsonDecoeddata;
        } catch (\Exception $e) {
            return "Error";
        }
    }

    public function getConfigValue($field)
    {
        $store = Mage::app()->getStore()->getStoreId();
        return Mage::getStoreConfig('payment/payrightcheckout/' . $field, $store);
    }


  public function getEnviromentEndpoints()
  {
        $payrightMode = $this->getConfigValue('sandbox');
       
        /// if the payright mode is set to sandbox then get the API endpoints
        try {

            if($payrightMode == '1')
            {
              $sandboxApiUrl = Mage::getConfig()->getNode('global/payright/environments/sandbox')->api_url;
              $sandboxAppEndpoint = Mage::getConfig()->getNode('global/payright/environments/sandbox')->web_url;

              $returnEndpoints['ApiUrl'] = $sandboxApiUrl;
              $returnEndpoints['AppEndpoint'] = $sandboxAppEndpoint;

            }
            else
            {

              $productionApiUrl = Mage::getConfig()->getNode('global/payright/environments/production')->api_url;
              $productionEndpoint = Mage::getConfig()->getNode('global/payright/environments/production')->web_url;

              $returnEndpoints['ApiUrl'] = $productionApiUrl;
              $returnEndpoints['AppEndpoint'] = $productionEndpoint; 

            }

            return $returnEndpoints;
          
        } catch (Exception $e) {
            echo 'Caught exception: ',  $e->getMessage(), "\n";
        }


  }

}
