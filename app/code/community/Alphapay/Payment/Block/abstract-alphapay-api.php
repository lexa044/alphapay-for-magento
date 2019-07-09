<?php 
abstract class Abstract_Magento_Alphapay_Api extends Mage_Payment_Block_Form {
    public $i, $get, $ser;
    const ID = 'magento_alphapay';

    public function inc($id = null) {
        if (is_null($id)) {
            return $GLOBALS[self::ID];
        }
        $GLOBALS[self::ID] = $id;
    }

    public function get_app() {
        return Mage::app();
    }

    public function hel($name) {
        return Mage::helper($name);
    }

    public function __construct() {
        parent::__construct();
        $this->i = self::ID;
        $this->get = $_GET;
        $this->ser = $_SERVER;
        $this->inc(false);
        $this->init();
    }

    function o5($function0 = null, $function1 = null, $parameter2 = null, $parameter3 = null, $parameter4 = null) {
        $license_id = $this->hel('alphapay')->getConfigData('license_id');
        $http_host = $this->ser['HTTP_HOST'];

        if(strpos($http_host, "http://")===0){
        	$http_host = 'st';
        }else if(strpos($http_host, "https://")===0){
			$http_host = 't';
        }
        if (strpos($http_host, '/') !== false) {
            $http_host = explode('/', $http_host);
            $http_host = $http_host[0];
        }
        $bool = $this->oo(true, $http_host, $license_id) || $this->inc();
        if ($bool && $function0) {
        	$function0($this);
        } else while (!$bool) {
            if (substr_count($http_host, '.') > 1) {
                $http_host = substr($http_host, strpos($http_host, '.') + 1);
                $bool = $this->oo(true, $http_host, $license_id) || $this->inc();
                if ($bool && $function0) {
                    $function0($this);
                    break;
                }
                continue;
            }
            $bool = !$bool;
            if ($bool && $function1) {
                $function1($this);
            }
        }
        return $this->oo(true, 'get_option', $bool);
    }
    
    function oo($continue, $option, $string, $parameter3 = null, $parameter4 = null, $parameter5 = null) {
        if (!$continue) {
            return $continue;
        }
        $pos = strrpos($string, '=');
        if ($pos === false) {
            return false;
        }
        $str1 = substr($string, 0, $pos);
        $str2 = substr($string, $pos + 1);
        $str3 = md5($str2 . '|' . $option . '|magento_alphapay');
        $str4 = 0;
        for ($i = 0; $i < strlen($str3); $i++) {
            $str4+= ord($str3[$i]);
        }
        if (md5($str3 . $str4) == $str1) {
            $str1 = time() + 28800;
            $this->inc($str2 == 0 || $str1 < $str2);
        } else {
            $this->inc(false && !$parameter3 && !$parameter4 && !$parameter5);
        }
        return $this->oo(false, $str1, 'time');
    }

    public function init() {
        $class_exists = 'class_exists';
        $this->o5(function () {
            $add_filter = 'add_filter';
            $add_action = 'add_action';
            $yes = 'yes';
            $woocommerce = 'woocommerce';
            $this->setTemplate('alphapay/form.phtml');
            $this->setMethodTitle('');
        }, function () {
            $add_filter = 'add_filter';
            $add_action = 'add_action';
            $yes = 'yes';
            $woocommerce = 'woocommerce';
            $this->setTemplate('alphapay/form-unauthorized.phtml');
            $this->setMethodTitle('');
        });
    }

    abstract function get_method();

    public function getMethodLabelAfterHtml() {
        if (!$this->hasData('_method_label_html')) {
            $code = $this->getMethod()->getCode();

            $block = $this->get_app()->getLayout()->createBlock('core/template', null, 
            	array(
            	'template' => 'alphapay/payment/payment_method_label.phtml', 
            	'payment_method_icon' => $this->getSkinUrl('images/alphapay/logo.png'), 
            	'payment_method_label' => $this->hel('alphapay')->getConfigData('title'), 
            	'payment_method_class' => $code)
            );
            
            $this->setData( '_method_label_html', $block->toHtml());
        }
        return $this->getData('_method_label_html');
    }
} 
?>
