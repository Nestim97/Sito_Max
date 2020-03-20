<?php

use PayPal\Api\Payer;
use PayPal\Api\Item;
use PayPal\Api\ItemList;
use PayPal\Api\Details;
use PayPal\Api\Amount;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Transaction;
use PayPal\Api\Payment;

require_once '../../../inc/init.php'; 
require   ROOT_PATH . 'shop/payments/paypal/start.php';

if (! defined('ROOT_URL')) {
  die;
}

if (!$loggedInUser) {
  exit;
}

// if (!isset($_POST['product'], $_POST['price'])) {  // dettagli di cio che hai comparato
//   die;
// }

$cartMgr = new CartManager();
$orderMgr = new OrderManager();

$cartId = $cartMgr->getCurrentCartId();
if ($cartMgr->isEmptyCart($cartId)){
  die('cart is empty');
}

$address = $orderMgr->getUserAddress($loggedInUser->id);
if(!$address) { 
  die('address_not_found');
}

// $orderId = $orderMgr->createOrderFromCart($cartId, $loggedInUser->id);
// $orderItems = $orderMgr->getOrderItems($orderId);
// $orderTotal = $orderMgr->getOrderTotal($orderId)[0];

$cartItems = $cartMgr->getCartItems($cartId);
$cartTotal = $cartMgr->getCartTotal($cartId)[0];

// Paypal info
$shipping = 0.00;

$items = [];
foreach($cartItems as $item) {
  $product = $item['product_name'];
  $price = $item['single_price']; 
  $quantity = $item['quantity'];

  // Uno per ogni elemento del carrello
  $item = new Item();
  $item->setName($product)
    ->setCurrency('EUR')
    ->setQuantity($quantity)
    ->setPrice($price);

  array_push($items, $item);
}

var_dump($items);

$totPrice = $cartTotal['total'];
$itemList = new ItemList();
$itemList->setItems($items);

$total = $totPrice + $shipping;
var_dump($totPrice);

$payer = new Payer();
$payer->setPaymentMethod('paypal');

$details = new Details();
$details->setShipping($shipping)
  ->setSubtotal($totPrice);

$amount = new Amount();
$amount->setCurrency('EUR')   
  ->setTotal($total)
  ->setDetails($details);

$transaction = new Transaction();
$transaction->setAmount($amount)
  ->setItemList($itemList)
  ->setDescription('Pagamento Ordine su ' . SITE_NAME) // descrizione qui
  ->setInvoiceNumber(uniqid()); // salvare il num. di fattura

$redirectUrls = new RedirectUrls();
$redirectUrls->setReturnUrl(ROOT_URL . 'shop/payments/paypal/pay.php?success=true')
  ->setCancelUrl(ROOT_URL . 'shop/payments/paypal/pay.php?success=false');

$payment = new Payment();
$payment->setIntent('sale')
  ->setPayer($payer)
  ->setRedirectUrls($redirectUrls)
  ->setTransactions([$transaction]);

//die;

try {
  $result = $payment->create($paypal);
} catch (Exception $e) {
  die($e);
}

// $orderMgr->SavePaymentDetails($orderId, $result->id, $result->state);

//var_dump($result); die;

echo $approvalUrl = $payment->getApprovalLink();

header("Location: {$approvalUrl}");










