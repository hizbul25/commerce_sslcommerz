<?php
namespace Drupal\commerce_sslcommerz;
/**
 * Created by PhpStorm.
 * User: hizbul
 * Date: 11/16/19
 * Time: 5:46 PM
 */

class SslCommerzNotification
{
    /**
     * @var string
     */
    private $error = null;

    private $storeId;

    private $storePass;

    private $mode;
    
    public function __construct($config)
    {
        $this->storeId = $config['store_id'];
        $this->storePass = $config['store_password'];
        $this->mode = $config['mode'];
    }

    /**
     * @param string $trxId
     * @param int $amount
     * @param string $currency
     * @param $postData
     * @return bool|string
     */
    public function orderValidate($trxId = '', $amount = 0, $currency = "BDT", $postData)
    {
        if ($postData == '' && $trxId == '' && !is_array($postData)) {
            $this->error = "Please provide valid transaction ID and post request data";
            return $this->error;
        }
        $validation = $this->validate($trxId, $amount, $currency, $postData);
        if ($validation) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param $merchantTransId
     * @param $merchantTransAmount
     * @param $merchantTransCurrency
     * @param $postData
     * @return bool
     */
    protected function validate($merchantTransId, $merchantTransAmount, $merchantTransCurrency, $postData)
    {
        # MERCHANT SYSTEM INFO
        if ($merchantTransId != "" && $merchantTransAmount != 0) {
            # CALL THE FUNCTION TO CHECK THE RESULT
            $postData['store_id'] = $this->storeId;
            $postData['store_pass'] = $this->storePass;
            if ($this->SSLCOMMERZ_hash_verify($this->storePass, $postData)) {
                $val_id = urlencode($postData['val_id']);
                $store_id = urlencode($this->storeId);
                $store_passwd = urlencode($this->storePass);
                $requested_url = ($this->getValidationUrl(). "?val_id=" . $val_id . "&store_id=" . $store_id . "&store_passwd=" . $store_passwd . "&v=1&format=json");
                $handle = curl_init();
                curl_setopt($handle, CURLOPT_URL, $requested_url);
                curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);

                if ($this->mode == 'test') {
                    curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, false);
                    curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
                } else {
                    curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, true);
                    curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, true);
                }
                $result = curl_exec($handle);
                $code = curl_getinfo($handle, CURLINFO_HTTP_CODE);
                if ($code == 200 && !(curl_errno($handle))) {
                    # TO CONVERT AS ARRAY
                    # $result = json_decode($result, true);
                    # $status = $result['status'];
                    # TO CONVERT AS OBJECT
                    $result = json_decode($result);
                    $this->sslc_data = $result;
                    # TRANSACTION INFO
                    $status = $result->status;
                    $tran_date = $result->tran_date;
                    $tran_id = $result->tran_id;
                    $val_id = $result->val_id;
                    $amount = $result->amount;
                    $store_amount = $result->store_amount;
                    $bank_tran_id = $result->bank_tran_id;
                    $card_type = $result->card_type;
                    $currency_type = $result->currency_type;
                    $currency_amount = $result->currency_amount;
                    # ISSUER INFO
                    $card_no = $result->card_no;
                    $card_issuer = $result->card_issuer;
                    $card_brand = $result->card_brand;
                    $card_issuer_country = $result->card_issuer_country;
                    $card_issuer_country_code = $result->card_issuer_country_code;
                    # API AUTHENTICATION
                    $APIConnect = $result->APIConnect;
                    $validated_on = $result->validated_on;
                    $gw_version = $result->gw_version;
                    # GIVE SERVICE
                    if ($status == "VALID" || $status == "VALIDATED") {
                        if ($merchantTransCurrency == "BDT") {
                            if (trim($merchantTransId) == trim($tran_id) && (abs($merchantTransAmount - $amount) < 1) && trim($merchantTransCurrency) == trim('BDT')) {
                                return true;
                            } else {
                                # DATA TEMPERED
                                $this->error = "Data has been tempered";
                                return false;
                            }
                        } else {
                            //echo "trim($merchantTransId) == trim($tran_id) && ( abs($merchantTransAmount-$currency_amount) < 1 ) && trim($merchantTransCurrency)==trim($currency_type)";
                            if (trim($merchantTransId) == trim($tran_id) && (abs($merchantTransAmount - $currency_amount) < 1) && trim($merchantTransCurrency) == trim($currency_type)) {
                                return true;
                            } else {
                                # DATA TEMPERED
                                $this->error = "Data has been tempered";
                                return false;
                            }
                        }
                    } else {
                        # FAILED TRANSACTION
                        $this->error = "Failed Transaction";
                        return false;
                    }
                } else {
                    # Failed to connect with SSLCOMMERZ
                    $this->error = "Failed to connect with SSLCOMMERZ";
                    return false;
                }
            } else {
                # Hash validation failed
                $this->error = "Hash validation failed";
                return false;
            }
        } else {
            # INVALID DATA
            $this->error = "Invalid data";
            return false;
        }
    }
    # FUNCTION TO CHECK HASH VALUE
    protected function SSLCOMMERZ_hash_verify($store_passwd = "", $postData)
    {
        if (isset($postData) && isset($postData['verify_sign']) && isset($postData['verify_key'])) {
            # NEW ARRAY DECLARED TO TAKE VALUE OF ALL POST
            $pre_define_key = explode(',', $postData['verify_key']);
            $new_data = array();
            if (!empty($pre_define_key)) {
                foreach ($pre_define_key as $value) {
                    if (isset($postData[$value])) {
                        $new_data[$value] = ($postData[$value]);
                    }
                }
            }
            # ADD MD5 OF STORE PASSWORD
            $new_data['store_passwd'] = md5($store_passwd);
            # SORT THE KEY AS BEFORE
            ksort($new_data);
            $hash_string = "";
            foreach ($new_data as $key => $value) {
                $hash_string .= $key . '=' . ($value) . '&';
            }
            $hash_string = rtrim($hash_string, '&');
            if (md5($hash_string) == $postData['verify_sign']) {
                return true;
            } else {
                $this->error = "Verification signature not matched";
                return false;
            }
        }
        else {
            $this->error = 'Required data mission. ex: verify_key, verify_sign';
            return false;
        }
    }

    private function getValidationUrl() {
        $mode = $this->mode == 'test' ? 'sandbox' : 'securepay';
        return "https://" . $mode  . ".sslcommerz.com/validator/api/validationserverAPI.php";
    }

    public function getErrorMessage() {
        return $this->error;
    }

}