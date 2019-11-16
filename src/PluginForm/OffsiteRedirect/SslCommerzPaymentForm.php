<?php
/**
 * Created by PhpStorm.
 * User: hizbul
 * Date: 11/11/19
 * Time: 7:58 AM
 */
namespace Drupal\commerce_sslcommerz\PluginForm\OffsiteRedirect;

use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm as BasePaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Serialization\Json;

class SslCommerzPaymentForm extends BasePaymentOffsiteForm
{
    const SSLCOMMERZ_TEST_URL = 'https://sandbox.sslcommerz.com/gwprocess/v4/api.php';
    const SSLCOMMERZ_LIVE_URL = 'https://securepay.sslcommerz.com/gwprocess/v4/api.php';

    /**
     * {@inheritdoc}
     */
    public function buildConfigurationForm(array $form, FormStateInterface $form_state)
    {
        $form = parent::buildConfigurationForm($form, $form_state);
        /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
        $payment = $this->entity;

        /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface $payment_gateway_plugin */
        $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();

        $configuration = $payment_gateway_plugin->getConfiguration();
        $paymentMode = $configuration['mode'];

        $data['store_id'] = $configuration['store_id'];
        $data['store_passwd'] = $configuration['store_password'];

        $data['currency'] = $payment->getAmount()->getCurrencyCode();
        $data['tran_id'] = $payment->getOrderId();
        $data['total_amount'] = $payment->getAmount()->getNumber();

        $order = $payment->getOrder();
        $billing_address = $order->getBillingProfile()->get('address');
        $data['product_category'] = 'pharmacy_medicine';
        $data['emi_option'] = 0;
        $data['cus_name'] = $billing_address->given_name . ' ' . $billing_address->family_name;;
        $data['cus_email'] = 'hizbul25@gmail.com';
        $data['cus_add1'] = $billing_address->address_line1;
        $data['cus_city'] = $billing_address->locality;
        $data['cus_postcode'] = $billing_address->postal_code;
        $data['cus_country'] = $billing_address->country_code;
        $data['cus_phone'] = '01918019009';
        $data['shipping_method'] = 'NO';
        $data['product_profile'] = 'physical-goods ';
        $data['num_of_item'] = 1;
        $data['product_name'] = "Test1";
        $data['product_category'] = "Test Category";

        $data['success_url'] = $form['#return_url'];
        $data['cancel_url'] = $form['#cancel_url'];

        $res = $this->getRedirectUrl($data, $paymentMode);
        if ($res['status'] != 'SUCCESS') {
            throw new PaymentGatewayException('Could not get the redirect URL.');
        }
        return $this->buildRedirectForm($form, $form_state, $res['GatewayPageURL'], $data, self::REDIRECT_POST);
    }

    /**
     * @param array $data
     * @param string $mode
     * @return mixed
     */
    private function getRedirectUrl(array $data, $mode = 'test') {
        $url = $mode == 'test' ? self::SSLCOMMERZ_TEST_URL : self::SSLCOMMERZ_LIVE_URL;
        $response = \Drupal::httpClient()->post($url, [
            'verify' => true,
            'form_params' => $data
        ])->getBody()->getContents();

        return Json::decode($response);
    }
}