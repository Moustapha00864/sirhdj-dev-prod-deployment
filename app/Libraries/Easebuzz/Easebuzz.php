<?php
namespace App\Libraries\Easebuzz;

/*
 * Easebuzz class manage all functionalities of easebuzz Payment Gateway
 */
class Easebuzz {
    private $MERCHANT_KEY = "";
    private $SALT = "";
    private $ENV = "";

    function __construct($key, $salt, $env){
        $this->MERCHANT_KEY = $key;
        $this->SALT = $salt;
        $this->ENV = $env;
    }

    public function initiatePaymentAPI($params, $redirect=True){
        include_once('payment.php');
        return initiate_payment($params, $redirect, $this->MERCHANT_KEY, $this->SALT, $this->ENV);
    }

    public function transactionAPI($params){
        include_once('transaction.php');
        $result = get_transaction_details($params, $this->MERCHANT_KEY, $this->SALT, $this->ENV);
        return json_encode($result);
    }

    public function transactionDateAPI($params){
        include_once('transaction_date.php');
        $result = get_transactions_by_date($params, $this->MERCHANT_KEY, $this->SALT, $this->ENV);
        return json_encode($result);
    }

    public function refundAPI($params){
        include_once('refund.php');
        $result = initiate_refund($params, $this->MERCHANT_KEY, $this->SALT, $this->ENV);
        return json_encode($result);
    }

    public function payoutAPI($params){
        include_once('payout.php');
        $result = get_payout_details_by_date($params, $this->MERCHANT_KEY, $this->SALT, $this->ENV);
        return json_encode($result);
    }

    public function easebuzzResponse($params){
        include_once('payment.php');
        $result = easebuzz_response($params, $this->SALT);
        return json_encode($result);
    }
}
?>

