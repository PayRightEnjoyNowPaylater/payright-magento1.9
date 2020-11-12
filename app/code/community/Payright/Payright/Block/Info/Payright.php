<?php

// app/code/local/Envato/Custompaymentmethod/Block/Info/Custompaymentmethod.php
class Payright_Payright_Block_Info_Payright extends Mage_Payment_Block_Info {
    private $_paymentSpecificInformation;

    protected function _prepareSpecificInformation($transport = null) {
        if (null !== $this->_paymentSpecificInformation) {
            return $this->_paymentSpecificInformation;
        }

        $data = array();

        if ($this->getInfo()->getPayrightPlanNumber()) {
            $data[Mage::helper('payment')->__('Payright Plan Number')] = $this->getInfo()->getPayrightPlanNumber();
        }

        $transport = parent::_prepareSpecificInformation($transport);

        return $transport->setData(array_merge($data, $transport->getData()));
    }
}
