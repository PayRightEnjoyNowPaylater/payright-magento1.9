<?php
/* @var $installer Mage_Sales_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();

// Add columns to sales_flat_order table.
$table = $installer->getTable('sales_flat_order');
$installer->getConnection()->addColumn($table, "payright_ecom_token", "varchar(255) DEFAULT NULL COMMENT 'PayRight Ecomm Token'");
$installer->getConnection()->addColumn($table, "payright_plan_id", "varchar(255) DEFAULT NULL COMMENT 'PayRight Plan Id'");

$installer->endSetup();