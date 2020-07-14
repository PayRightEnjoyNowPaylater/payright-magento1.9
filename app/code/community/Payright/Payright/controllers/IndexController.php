<?php
// public function indexAction()
// {
// //Get current layout state
// $this->loadLayout();

// $block = $this->getLayout()->createBlock(
// 'Mage_Core_Block_Template',
// 'product.payright',
// array('template' => 'payright/index.phtml')
// );

// $this->getLayout()->getBlock('content')->append($block);

// //Release layout stream... lol... sounds fancy
// $this->renderLayout();
// }

class Payright_Payright_IndexController extends Mage_Core_Controller_Front_Action
{

    public function indexAction()
    {

        // echo "Hello World at " . date('F j, Y');

        $this->loadLayout();

        $this->renderLayout();

    }

}
