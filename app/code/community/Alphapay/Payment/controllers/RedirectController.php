<?php
class Alphapay_Payment_RedirectController extends Mage_Core_Controller_Front_Action {
    protected function _expireAjax() {
        if (!Mage::getSingleton('checkout/session')->getQuote()->hasItems()) {
            $this->getResponse()->setHeader('HTTP/1.1','403 Session Expired');
            exit;
        }
    }
    

    public function indexAction() {
        $orderId = $this->getRequest()->get('orderId');
        if ($orderId) {
            $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);
        } else {
            $order = Mage::helper('alphapay')->getOrder();
        }
        
    	if(!$order||!$order instanceof Mage_Sales_Model_Order||!in_array($order->getState(), array(
    	    Mage_Sales_Model_Order::STATE_NEW,
    	    Mage_Sales_Model_Order::STATE_PENDING_PAYMENT
    	     
    	))){
    	    $this->_redirect('checkout/onepage/success', array('_secure'=>true));
    	    return;
    	}
    	
    	$time=time().'000';
    	$order_id =$order->getRealOrderId();
    	$new_id = date('ymdHis').'-'.$order_id ;
    	
    	$callback = Mage::getUrl('alphapay/redirect/callback',array('id' =>$new_id));
    	
    	$partner_code =Mage::helper('alphapay')->getConfigData('mch_id');
    	$credential_code = Mage::helper('alphapay')->getConfigData('mch_secret');
    	$nonce_str = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0,10);
    	$valid_string="$partner_code&$time&$nonce_str&$credential_code";
    	$sign=strtolower(hash('sha256',$valid_string));
    	
    	$channel=null;
    	if(Mage::helper('alphapay')->isWeixinClient ()){
    	    $url ="https://pay.alphapay.ca//api/v1.0/wechat_jsapi_gateway/partners/$partner_code/orders/$new_id";
    	    
    	}else{
    	    
    	    if(Mage::helper('alphapay')->is_app_client()&&(1==Mage::helper('alphapay')->getConfigData('enable_h5'))){
    	        $url ="https://pay.alphapay.ca//api/v1.0/h5_payment/partners/$partner_code/orders/$new_id";
    	        $channel = 'Wechat';
    	    }else{
    	        $url ="https://pay.alphapay.ca//api/v1.0/gateway/partners/$partner_code/orders/$new_id";
    	    }
    	    
    	}
    	
    	$url.="?time=$time&nonce_str=$nonce_str&sign=$sign";
    	$head_arr = array();
    	$head_arr[] = 'Content-Type: application/json';
    	$head_arr[] = 'Accept: application/json';
    	$head_arr[] = 'Accept-Language: '.Mage::app()->getLocale()->getLocaleCode();
    	
    	$data =new stdClass();
    	
    	$order_items = $order->getAllItems();
    	$order_title ='';
    	if($order_items){
        	foreach($order_items as $item) {
        	    $order_title= $item->getName();
        	    break;
        	}
    	}
    	if(empty($order_title)){
    	    $order_title=Mage::app()->getStore()->getName();
    	}
    	
    	$data->description = mb_strimwidth($order_title, 0, 32,'...',"utf-8");
    	$data->price = (int) (round($order->getGrandTotal(), 2) * 100);
    	$data->currency = $order->getOrderCurrencyCode();
    	$data->notify_url= Mage::getUrl('alphapay/notify');
    	if($channel){
    	    $data->channel =$channel;
    	}
    	$data =json_encode($data);
    
    	try {
    	    $ch = curl_init();
    	    curl_setopt($ch, CURLOPT_URL, $url);
    	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    	    curl_setopt($ch, CURLOPT_PUT, true);
    	    curl_setopt($ch, CURLOPT_HTTPHEADER, $head_arr);
    	    $temp = tmpfile();
    	    fwrite($temp, $data);
    	    fseek($temp, 0);
    	    curl_setopt($ch, CURLOPT_INFILE, $temp);
    	    curl_setopt($ch, CURLOPT_INFILESIZE, strlen($data));
    	    curl_setopt($ch, CURLOPT_TIMEOUT, 120);
    	    $response = curl_exec($ch);
    	    $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    	    $error=curl_error($ch);
    	    curl_close($ch);
    	    if($httpStatusCode!=200){
    	        throw new Exception("invalid httpstatus:{$httpStatusCode} ,response:$response,detail_error:".$error,$httpStatusCode);
    	    }
    	    
    	    $result =$response;
    	
    	    if($temp){
    	        fclose($temp);
    	        unset($temp);
    	    }
    	
    	    $resArr = json_decode($result,false);
    	    if(!$resArr){
    	        Mage::helper('alphapay')->log ( print_r($resArr,true));
    	        throw new Exception(Mage::helper('alphapay')->__('This request has been rejected by the remote service!'));
    	    }
    	
    	    if(!isset($resArr->result_code)||$resArr->result_code!='SUCCESS'){
    	        Mage::helper('alphapay')->log ( print_r($resArr,true));
    	        throw new Exception(Mage::helper('alphapay')->__(sprintf('ERROR CODE:%s',$resArr->result_code.$resArr->return_msg)));
    	    }
    	    	
    	    if(!Mage::helper('alphapay')->isWeixinClient ()&&!(Mage::helper('alphapay')->is_app_client()&&(1==Mage::helper('alphapay')->getConfigData('enable_h5')))){
    	        if(Mage::helper('alphapay')->getConfigData('qrcode_location')=='local'){
    	            $this->qrcode($resArr,$order,$order_title,$new_id);
    	            return;
    	        }
    	    }
    	    
    	    $time=time().'000';
    	    	
    	    $nonce_str = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0,10);
    	    $valid_string="$partner_code&$time&$nonce_str&$credential_code";
    	    $sign=strtolower(hash('sha256',$valid_string));
    	   
    	    
    	    $this->_redirectUrl($resArr->pay_url.(strpos($resArr->pay_url, '?')==false?'?':'&')."directpay=true&time=$time&nonce_str=$nonce_str&sign=$sign&redirect=".urlencode($callback));    	   
    	} catch (Exception $e) {
    	    Mage::helper('alphapay')->log ( $e->getMessage());
    	    echo $e->getMessage();
    	    return;
    	}
    }
    
    public function queryAction() {
        $new_order_id = trim($this->getRequest()->getParam('id'));
        $order_ids=explode('-', $new_order_id);
        $order_id = count($order_ids)==2?$order_ids[1]:0;
        
        $order = Mage::getModel('sales/order')->loadByIncrementId($order_id);
        if (! $order || ! $order->getId() || ! $order instanceof Mage_Sales_Model_Order) {
            echo json_encode(array('status'=>'not-paid','message'=>'unknow order'));
            exit;
        }
    
        if(in_array($order->getState(), array(
            Mage_Sales_Model_Order::STATE_NEW,
            Mage_Sales_Model_Order::STATE_PENDING_PAYMENT
    
        ))){
            $resArr = $this->QueryOrderStatus($new_order_id);

            if($resArr&&$resArr->result_code=='PAY_SUCCESS'){
                
                $this->checkout_success($resArr,$order,1);
            }
            
            echo json_encode(array('status'=>'not-paid','message'=>''));
            exit;
        }
        
        echo json_encode(array('status'=>'paid','message'=>Mage::getUrl('checkout/onepage/success')));
        exit;
    }
    
    protected function _getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }
    
    private function qrcode($resArr,$order,$subject,$new_id){
        $total_fee = round($order->getGrandTotal(), 2);
        ?>
       <!DOCTYPE html>
        	<html>
        	<head>
            <meta charset="utf-8">
            <meta http-equiv="X-UA-Compatible" content="IE=edge">
            <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
            <meta name="keywords" content="">
            <meta name="description" content="">   
            <title>微信支付收银台</title>
            <style>
                 *{margin:0;padding:0;}
                  body{padding-top: 50px; background: #f2f2f4;}
                 .clearfix:after { content: "."; display: block; height: 0; clear: both; visibility: hidden; }
                .clearfix { display: inline-block; }
                * html .clearfix { height: 1%; }
                .clearfix { display: block; }
                  .wx-title{height:35px;line-height:35px;text-align:center;font-size:30px;margin-bottom:20px;font-weight:300;}
                  .qrbox{max-width: 900px;margin: 0 auto;background:#f9f9f9;padding:35px 20px 20px 50px;border:1px solid #ddd;border-top:3px solid #44b549;}
                  
                  .qrbox .left{width: 40%;
                    float: left;    
                     display: block;
                    margin: 0px auto;}
                  .qrbox .left .qrcon{
                    border-radius: 10px;
                    background: #fff;
                    overflow: visible;
                    text-align: center;
                    padding-top:25px;
                    color: #555;
                    box-shadow: 0 3px 3px 0 rgba(0, 0, 0, .05);
                    vertical-align: top;
                    -webkit-transition: all .2s linear;
                    transition: all .2s linear;
                  }
                    .qrbox .left .qrcon .logo{width: 100%;}
                    .qrbox .left .qrcon .title{font-size: 16px;margin: 10px auto;width: 100%;}
                    .qrbox .left .qrcon .price{font-size: 22px;margin: 0px auto;width: 100%;}
                    .qrbox .left .qrcon .bottom{border-radius: 0 0 10px 10px;
                    width: 100%;
                    background: #32343d;
                    color: #f2f2f2;padding:15px 0px;text-align: center;font-size: 14px;}
                   .qrbox .sys{width: 60%;float: right;text-align: center;padding-top:20px;font-size: 12px;color: #ccc}
                   .qrbox img{max-width: 100%;}
                   @media (max-width : 767px){
                .qrbox{padding:20px;}
                    .qrbox .left{width: 90%;float: none;}   
                    .qrbox .sys{display: none;}
                   }
                   
                   @media (max-width : 320px){
                   body{padding-top:35px;}
                  }
                  @media ( min-width: 321px) and ( max-width:375px ){
                body{padding-top:35px;}
                  }
            </style>
            </head>
            
            <body>
             <div class="wx-title">微信支付收银台</div>
              <div class="qrbox clearfix">
              <div class="left">
                 <div class="qrcon">
                   <h5><img src="<?php print  Mage::getDesign()->getSkinUrl('images/alphapay/alphapay.png');?>" alt=""></h5>
                     <div class="title"><?php print $subject;?></div>
                     <?php 
                     $symbol = Mage::app()->getLocale()->currency($order->getOrderCurrencyCode())->getSymbol();
                     ?>
                     <div class="price"> <?php echo $symbol.$total_fee;?></div>
                     <div align="center"><img src="<?php echo $resArr->qrcode_img?>" style="width: 250px;height: 250px;"/></div>
                     <div class="bottom">

                     <?php if(!Mage::helper('alphapay')->isWeixinClient()&&!(Mage::helper('alphapay')->is_app_client())){ ?>
                            请使用微信"扫一扫"<br/>
            				扫描二维码支付
                     <?php }else{?>
                            请截图保存此支付二维码<br/>
                            使用微信"扫一扫" 扫码二维码支付
                     <?php }?>
                     </div>
                 </div>
                 
          </div>
             <div class="sys"><img src="<?php print  Mage::getDesign()->getSkinUrl('images/alphapay/wechat-sys.png');?>" alt=""></div>
          </div>
          <script src="<?php print  Mage::getDesign()->getSkinUrl('js/alphapay/jquery-2.1.4.js');?>"></script>
             <script type="text/javascript">
             (function($){
            		window.view={
        				query:function () {
        			        $.ajax({
        			            type: "POST",
        			            url: '<?php echo Mage::getUrl('alphapay/redirect/query',array('id' => $new_id))?>',
        			            timeout:60000,
        			            cache:false,
        			            dataType:'json',
        			            success:function(data){
        			                if (data && data.status=='paid') {
        				                $('#weixin-notice').css('color','green').text('已支付成功，跳转中...');
        			                    location.href = data.message;
        			                    return;
        			                }
        			                
        			                setTimeout(function(){window.view.query();}, 2000);
        			            },
        			            error:function(){
        			            	setTimeout(function(){window.view.query();}, 2000);
        			            }
        			        });
        			    }
            		};
            		window.view.query();
            		
            	})(jQuery);
            	</script>
        	</body>
        </html>
       <?php 
    } 
    
    public function callbackAction(){
        $new_order_id = trim($this->getRequest()->getParam('id'));
        $order_ids=explode('-', $new_order_id);
        $order_id = count($order_ids)==2?$order_ids[1]:0;
        
        $order = Mage::getModel('sales/order')->loadByIncrementId($order_id);
        if (! $order || ! $order->getId() || ! $order instanceof Mage_Sales_Model_Order) {
            $this->_redirect('checkout/cart/', array('_secure'=>true));
        }

        // if not paid
		if($order->getBaseTotalDue() != 0){
            sleep(0.25);

            $resArr = $this->QueryOrderStatus($new_order_id);
            
            if($resArr&&$resArr->result_code=='PAY_SUCCESS'){
                $this->checkout_success($resArr,$order,2);
            }else{
                sleep(0.25);

                if($resArr&&$resArr->result_code=='PAY_SUCCESS'){
                    $this->checkout_success($resArr,$order,2);
                }else{
                    //Get the customer session and check if the customer is logged in
                    if (Mage::getSingleton('customer/session')->isLoggedIn()) {
                        $this->_redirect('sales/order/view', array('order_id'=>$order->getId()));
                    }else{
                        $this->_redirect('checkout/cart/', array('_secure'=>true));
                    }
                }
            }

        }else{
            $this->_redirect('checkout/onepage/success', array('_secure'=>true));
        }


        
        
    }
    

    private function QueryOrderStatus($new_order_id){

        $partner_code =Mage::helper('alphapay')->getConfigData('mch_id');
        $credential_code = Mage::helper('alphapay')->getConfigData('mch_secret');
        $url="https://pay.alphapay.ca//api/v1.0/gateway/partners/$partner_code/orders/$new_order_id";
        
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
        
        return json_decode($result,false);
    }

    private function checkout_success($resArr,$order,$type){
        $alphapay_order_id= $resArr->partner_order_id;

        $payment = $order->getPayment();
        $payment->setTransactionId($alphapay_order_id)
            ->setCurrencyCode() // $request['currency']
            ->setPreparedMessage(Mage::helper('alphapayalipay')->__('transactionId: "%s".', $alphapay_order_id))
            ->setParentTransactionId()
            ->setShouldCloseParentTransaction(true)
            ->setIsTransactionClosed(0)
            ->registerCaptureNotification($order->getBaseGrandTotal(), true);
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

        if($type == 1){
            echo json_encode(array('status'=>'paid','message'=>Mage::getUrl('checkout/onepage/success')));
            exit;
        }

        if($type == 2){
            $this->_redirect('checkout/onepage/success', array('_secure'=>true));
        }

    }
}

?>
