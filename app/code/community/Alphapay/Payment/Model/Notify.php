<?php
class Alphapay_Payment_Model_Notify
{

    public function handleNotify(array $request)
    {
        try {
            $json =isset($GLOBALS['HTTP_RAW_POST_DATA'])?$GLOBALS['HTTP_RAW_POST_DATA']:'';
            if(empty($json)){
                $json = file_get_contents("php://input");
            }
            
            if(empty($json)){
               echo 'fali';
               return;
            }
            
            $object = json_decode($json,false);
            if(!$object){
               Mage::helper('alphapay')->log ( " invalid result:" .$json);
               echo 'fali';
               return;
            }
            
            $partner_code =Mage::helper('alphapay')->getConfigData('mch_id');
            $credential_code = Mage::helper('alphapay')->getConfigData('mch_secret');
            $time=$object->time;
            $nonce_str=$object->nonce_str;
            
            $valid_string="$partner_code&$time&$nonce_str&$credential_code";
            $sign=strtolower(hash('sha256',$valid_string));
            if($sign!=$object->sign){
               Mage::helper('alphapay')->log ( " invalid sign:" .$json);
               echo 'fali';
               return;
            }
            
            $order_ids=explode('-', $object->partner_order_id);
            if(count($order_ids)!=2){
                Mage::helper('alphapay')->log ( " invalid order id:" .$json);
                echo 'fali';
                return;
            }
            
            $order_id=$order_ids[1];
           
            $order = Mage::getModel('sales/order')->loadByIncrementId($order_id);
            if (! $order || ! $order->getId() || ! $order instanceof Mage_Sales_Model_Order||!in_array($order->getState(), array(
        	    Mage_Sales_Model_Order::STATE_NEW,
        	    Mage_Sales_Model_Order::STATE_PENDING_PAYMENT
        	     
        	))) {
               print json_encode(array('return_code'=>'SUCCESS'));
                exit;
            }
            
            $url="https://pay.alphapay.ca//api/v1.0/gateway/partners/$partner_code/orders/{$object->partner_order_id}";
            
            $time=time().'000';
            $nonce_str = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0,10);
            $valid_string="$partner_code&$time&$nonce_str&$credential_code";
            $sign=strtolower(hash('sha256',$valid_string));
            $url.="?time=$time&nonce_str=$nonce_str&sign=$sign";
            
            $head_arr = array();
            $head_arr[] = 'Accept: application/json';
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $head_arr);
            curl_setopt($ch, CURLOPT_TIMEOUT, 120);
            $result = curl_exec($ch);
            curl_close($ch);
            
            $resArr = json_decode($result,false);
            if(!$resArr||$resArr->result_code!='PAY_SUCCESS'){
               Mage::helper('alphapay')->log ( " order callback fail:" .$result);
                echo 'fail';
                exit;
            }
            
            
            
            
            $transaction_id = $resArr->order_id;
            $payment = $order->getPayment();
            $payment->setTransactionId($transaction_id)
            ->setPreparedMessage(Mage::helper('alphapay')->__('transactionId: "%s".', $transaction_id))
            ->registerCaptureNotification($order->getBaseGrandTotal(), true)
            ->setCurrencyCode() // $request['currency']
            ->setParentTransactionId()
            ->setShouldCloseParentTransaction(true)
            ->setIsTransactionClosed(0)
            ->registerCaptureNotification();
            $order->save();
            
            // notify customer
            $invoice = $payment->getCreatedInvoice();
            if ($invoice && ! $order->getEmailSent()) {
                $order->sendNewOrderEmail()
                ->addStatusHistoryComment(Mage::helper('alphapay')->__('Notified customer about invoice #%s.', $invoice->getIncrementId()))
                ->setIsCustomerNotified(true)
                ->save();
            }
            
            $session = Mage::getSingleton('checkout/session');
            $session->setQuoteId($order->getQuoteId());
            $session->getQuote()->setIsActive(false)->save();
            
            print json_encode(array('return_code'=>'SUCCESS'));
            exit;
            
        } catch (Exception $e) {
            Mage::helper('alphapay')->log($e->getMessage());
            echo 'FAIL';
        }
    }

}
