<?php

/* @var $installer Mage_Sales_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();

$connection = $installer->getConnection();

// add columns to sales_flat_order
$salesFlatOrderTable = $installer->getTable('sales_flat_order');
if ($connection->tableColumnExists($salesFlatOrderTable, 'payright_checkout_id') === false) {
    $installer->getConnection()->addColumn($salesFlatOrderTable, "payright_checkout_id", "varchar(255) DEFAULT NULL COMMENT 'PayRight Checkout Id'");
}

if ($connection->tableColumnExists($salesFlatOrderTable, 'payright_plan_id') === false) {
    $installer->getConnection()->addColumn($salesFlatOrderTable, "payright_plan_id", "varchar(255) DEFAULT NULL COMMENT 'PayRight Plan Id'");
}

// add columns to sales_flat_order_payment
$salesFlatOrderPaymentTable = $installer->getTable('sales_flat_order_payment');

if ($connection->tableColumnExists($salesFlatOrderPaymentTable, 'payright_plan_number') === false) {
    $installer->getConnection()->addColumn($salesFlatOrderPaymentTable, "payright_plan_number", "varchar(255) DEFAULT NULL COMMENT 'PayRight Plan Number'");
}

$installer->endSetup();