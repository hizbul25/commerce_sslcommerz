<?php
/**
 * Created by PhpStorm.
 * User: hizbul
 * Date: 11/10/19
 * Time: 7:12 PM
 */
namespace Drupal\commerce_sslcommerz\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase ;
use Drupal\commerce_sslcommerz\SslCommerzNotification;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides the QuickPay offsite Checkout payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "sslcommerz_redirect_checkout",
 *   label = @Translation("SSLCommerz (Redirect to sslcommerz)"),
 *   display_label = @Translation("SSLCommerz"),
 *    forms = {
 *     "offsite-payment" = "Drupal\commerce_sslcommerz\PluginForm\OffsiteRedirect\SslCommerzPaymentForm",
 *   },
 *   payment_method_types = {"credit_card"},
 *   credit_card_types = {
 *     "mastercard", "visa", "amex", "othercards",
 *   },
 * )
 */
class RedirectCheckout extends OffsitePaymentGatewayBase
{
    /**
     * @return array
     */
    public function defaultConfiguration()
    {
        return [
                'store_id' => '',
                'store_password' => '',
            ] + parent::defaultConfiguration();
    }

    /**
     * @param array $form
     * @param FormStateInterface $form_state
     * @return array
     */
    public function buildConfigurationForm(array $form, FormStateInterface $form_state)
    {
        $form = parent::buildConfigurationForm($form, $form_state);

        $form['store_id'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Store ID'),
            '#description' => $this->t('This is the store id from the SSLCommerz manager.'),
            '#default_value' => $this->configuration['store_id'],
            '#required' => TRUE,
        ];

        $form['store_password'] = [
            '#type' => 'password',
            '#title' => $this->t('Store Password'),
            '#description' => $this->t('The store password for the same user as used in Agreement ID.'),
            '#default_value' => $this->configuration['store_password'],
            '#required' => TRUE,
        ];

        return $form; 
    }

    /**
     * @param array $form
     * @param FormStateInterface $form_state
     */
    public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
        parent::submitConfigurationForm($form, $form_state);
        $values = $form_state->getValue($form['#parents']);
        $this->configuration['store_id'] = $values['store_id'];
        $this->configuration['store_password'] = $values['store_password'];
    }

    public function onCancel(OrderInterface $order, Request $request)
    {
        $this->messenger()->addMessage($this->t('You have canceled checkout at @gateway but may resume the checkout process here when you are ready.', [
            '@gateway' => $this->getDisplayLabel(),
        ]));
    }

    public function onReturn(OrderInterface $order, Request $request)
    {
        $config = $this->getConfiguration();
        $logger = \Drupal::logger("sslcommerz");
        $sslcommerz = new SslCommerzNotification($config);
        $response = $sslcommerz->orderValidate($order->id(), floatval($order->getTotalPrice()->getNumber()), $order->getTotalPrice()->getCurrencyCode(), $_POST);
        if ($response) {
            $state = 'completed';

        }
        else {
            $state = 'pending';
            \Drupal::messenger()->addMessage(t($sslcommerz->getErrorMessage()), 'error');
        }

        $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
        $payment = $payment_storage->create([
            'state' => $state,
            'amount' => $order->getTotalPrice(),
            'payment_gateway' => $this->entityId,
            'order_id' => $order->id(),
            'remote_id' => $_POST['val_id'],
            'remote_state' => $state == 'completed' ? 'VALIDATED' : 'N\A',
        ]);

        $logger->info('Saving Payment information. Transaction reference: ' . $_POST['val_id']);

        $payment->save();
        \Drupal::messenger()->addMessage(t('Payment was processed'), 'success');

        $logger->info('Payment information saved successfully. Transaction reference: ' . $_POST['val_id']);
    }

}