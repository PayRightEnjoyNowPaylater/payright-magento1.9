<?php

/* @var $installer Mage_Sales_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();

// add columns to sales_flat_order
$table = $installer->getTable('sales_flat_order');
$connection = $installer->getConnection();

if ($connection->tableColumnExists($table, 'payright_ecom_token') === false) {
    $installer->getConnection()->addColumn($table, "payright_ecom_token", "varchar(255) DEFAULT NULL COMMENT 'PayRight Ecomm Token'");
}
if ($connection->tableColumnExists($table, 'payright_plan_id') === false) {
    $installer->getConnection()->addColumn($table, "payright_plan_id", "varchar(255) DEFAULT NULL COMMENT 'PayRight Plan Id'");
}

$installer->endSetup();