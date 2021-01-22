<?php

/* @var $installer Mage_Sales_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();

// add columns to sales_flat_order
$table = $installer->getTable('sales_flat_order');
$connection = $installer->getConnection();

if ($connection->tableColumnExists($table, 'payright_checkout_id') === false) {
    $installer->getConnection()->addColumn($table, "payright_checkout_id", "varchar(255) DEFAULT NULL COMMENT 'PayRight Checkout Id'");
}

$installer->endSetup();