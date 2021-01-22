<?php

/* @var $installer Mage_Sales_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();

// add columns to sales_flat_order_payment
$table = $installer->getTable('sales_flat_order_payment');
$connection = $installer->getConnection();

if ($connection->tableColumnExists($table, 'payright_plan_number') === false) {
    $installer->getConnection()->addColumn($table, "payright_plan_number", "varchar(255) DEFAULT NULL COMMENT 'PayRight Plan Number'");
}

$installer->endSetup();