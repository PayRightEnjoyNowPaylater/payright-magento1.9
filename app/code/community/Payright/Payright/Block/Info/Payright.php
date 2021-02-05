<?php

class Payright_Payright_Block_Info_Payright extends Mage_Payment_Block_Info {
    /**
     * Prepare specific information.
     *
     * @param null $transport
     * @return mixed
     */
    protected function _prepareSpecificInformation($transport = null) {
        if (null !== $this->_paymentSpecificInformation) {
            return $this->_paymentSpecificInformation;
        }

        $info = $this->getInfo();

        $transport = new Varien_Object();
        $transport = parent::_prepareSpecificInformation($transport);
        $transport->addData(array(
            Mage::helper('payment')->__('Payright Plan Number') => $info->getPayrightPlanNumber(),
        ));
        return $transport;
    }
}
