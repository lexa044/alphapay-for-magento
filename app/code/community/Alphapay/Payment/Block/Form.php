<?php require_once 'abstract-alphapay-api.php';

class Alphapay_Payment_Block_Form extends Abstract_Magento_Alphapay_Api{
    /**
     * {@inheritDoc}
     * @see Abstract_Magento_Alphapayalipay_Api::get_method()
     */
    public function get_method()
    {
        // TODO Auto-generated method stub
        return 'alphapay';
    }
    
}