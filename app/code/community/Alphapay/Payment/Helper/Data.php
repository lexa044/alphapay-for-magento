<?php


class Alphapay_Payment_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
	 * @desc  Get default settings
	 * @param string $code
	 * @return mixed
	 */
	public function getConfigData($code, $storeId = null) {
		if (null === $storeId) {
			$storeId = Mage::app()->getStore()->getStoreId();
		}
		
		return trim(Mage::getStoreConfig("payment/alphapay/$code", $storeId));
	}

	/**
	 * @desc Get order
	 * @param string $orderId
	 * @return Mage_Sales_Model_Order 
	 */
	public function getOrder($orderId = null) {
		if (null === $orderId) {
			$orderId = Mage::getSingleton('checkout/session')->getLastRealOrderId();
		}
	
		return Mage::getModel('sales/order')->loadByIncrementId($orderId);
	}
	public  function isWeixinClient(){
	    return strripos($_SERVER['HTTP_USER_AGENT'],'micromessenger')!=false;
	}
	public function is_app_client()
	{
	    if (! isset($_SERVER['HTTP_USER_AGENT'])) {
	        return false;
	    }
	
	    $u = strtolower($_SERVER['HTTP_USER_AGENT']);
	    if ($u == null || strlen($u) == 0) {
	        return false;
	    }
	
	    preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/', $u, $res);
	
	    if ($res && count($res) > 0) {
	        return true;
	    }
	
	    if (strlen($u) < 4) {
	        return false;
	    }
	
	    preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/', substr($u, 0, 4), $res);
	    if ($res && count($res) > 0) {
	        return true;
	    }
	
	    $ipadchar = "/(ipad|ipad2)/i";
	    preg_match($ipadchar, $u, $res);
	    if ($res && count($res) > 0) {
	        return true;
	    }
	
	    return false;
	}
	
	public function guid()
	{
	    $guid = '';
	    if (function_exists('com_create_guid')) {
	
	        $guid = com_create_guid();
	    } else {
	        mt_srand((double) microtime() * 10000); // optional for php 4.2.0 and up.
	        $charid = strtoupper(md5(uniqid(rand(), true)));
	        $hyphen = chr(45); // "-"
	        $uuid = chr(123) . // "{"
	        substr($charid, 0, 8) . $hyphen . substr($charid, 8, 4) . $hyphen . substr($charid, 12, 4) . $hyphen . substr($charid, 16, 4) . $hyphen . substr($charid, 20, 12) . chr(125); // "}"
	        $guid = $uuid;
	    }
	
	    return str_replace('-', '', trim($guid, '{}'));
	}
	
	public function log($message, $level = null) {
	    try {
	        $logActive = Mage::getStoreConfig('dev/log/active');
	    } catch (Exception $e) {
	        $logActive = true;
	    }
	
	    if (! Mage::getIsDeveloperMode() && ! $logActive) {
	        return;
	    }
	
	    static $loggers = array();
	
	    $level = is_null($level) ? Zend_Log::DEBUG : $level;
	    $file = 'log-'.date('d') . '.log';
	     
	    try {
	        if (! isset($loggers[$file])) {
	            $logDir = Mage::getBaseDir('var') . DS . 'log' . DS . date("Y-m");
	            $logFile = $logDir . DS . $file;
	
	            if (! @is_dir($logDir)&&!@mkdir($logDir, 0777)) {
	                return;
	            }
	
	            if (! file_exists($logFile)) {
	                @file_put_contents($logFile, '');
	                @chmod($logFile, 0777);
	            }
	
	            $format = '%timestamp% %priorityName% (%priority%): %message%' . PHP_EOL;
	            $formatter = new Zend_Log_Formatter_Simple($format);
	            $writerModel = (string) Mage::getConfig()->getNode('global/log/core/writer_model');
	            if (! Mage::app() || ! $writerModel) {
	                $writer = new Zend_Log_Writer_Stream($logFile);
	            } else {
	                $writer = new $writerModel($logFile);
	            }
	            $writer->setFormatter($formatter);
	            $loggers[$file] = new Zend_Log($writer);
	        }
	
	        if (is_array($message) || is_object($message)) {
	            $message = print_r($message, true);
	        }
	
	        $stack = "[";
	        $debugInfo = debug_backtrace ();
	        foreach ( $debugInfo as $key => $val ) {
	            if (array_key_exists ( "file", $val )) {
	                $stack .= ",file:" . $val ["file"];
	            }
	            if (array_key_exists ( "line", $val )) {
	                $stack .= ",line:" . $val ["line"];
	            }
	            if (array_key_exists ( "function", $val )) {
	                $stack .= ",function:" . $val ["function"];
	            }
	        }
	        $stack .= "]";
	        $loggers[$file]->log($stack.$message, $level);
	    } catch (Exception $e) {}
	}
}
