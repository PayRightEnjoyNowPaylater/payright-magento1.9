<?php

class Payright_Payright_Model_Regionlist {

    /**
     * Specify the store region / country, to switch between
     *
     */
    public function toOptionArray() {
        return array(
            array('value' => 0, 'label' => Mage::helper('payright')->__('Australia')),
            array('value' => 1, 'label' => Mage::helper('payright')->__('New Zealand')),
        );
    }
}