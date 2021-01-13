<?php

class Payright_Payright_Model_RegionList {

    /**
     * Specify the store region / country, to switch between
     *
     */
    public function toOptionArray() {
        return array(
            array(
                'value' => 'AU',
                'label' => 'Australia',
            ),
            array(
                'value' => 'NZ',
                'label' => 'New Zealand',
            )
        );
    }
}