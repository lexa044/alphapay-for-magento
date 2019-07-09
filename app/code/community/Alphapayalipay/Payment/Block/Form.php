<?php require_once 'abstract-alphapayalipay-api.php';

class Alphapayalipay_Payment_Block_Form extends Abstract_Magento_Alphapayalipay_Api{
    /**
     * {@inheritDoc}
     * @see Abstract_Magento_Alphapayalipay_Api::get_method()
     */
    public function get_method()
    {
        // TODO Auto-generated method stub
        return 'alphapayalipay';
    }
    
}