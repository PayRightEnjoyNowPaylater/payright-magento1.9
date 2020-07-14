# Payright-magento1.9
PayRight Extension for Magento 1.9

# 1.1 New Payright Installation
This section outlines the steps to install the Payright plugin for the first time.

Note: [MAGENTO] refers to the root folder where Magento is installed.

1. Download the Magento-Payright plugin - Available as a .zip or tar.gz file from the Payright GitHub directory.
2. Unzip the file
3. Copy all files in: 
 <br>/app/code/community/ 
 <br>**to:**
 <br>MAGENTO]/app/code/community
4. Copy all files in: 
  <br>/app/design/frontend/base/default/layout 
  <br>**to:** 
  <br>[MAGENTO]/app/design/frontend/base/default/layout
5. Copy all files in: 
  <br>/app/design/frontend/base/default/template 
  <br>**to:**
  <br>[MAGENTO]/app/design/frontend/base/default/template
6. Copy all files in: 
  <br>/app/etc/modules/ 
  <br>**to:** 
  <br>[MAGENTO]/app/etc/modules
7. Copy all files in: 
  <br> /js/ 
  <br>**to:** 
  <br>[MAGENTO]/js/
8. Copy all files in: 
  <br> /skin/frontend/base/default/   
  <br> **to:** 
  <br> [MAGENTO]/skin/frontend/base/default/
9. Login to Magento Admin and navigate to System/Cache Management
10. Flush the cache storage by selecting Flush Cache Storage from System/Cache management

# 1.2	Payright Merchant Setup
Complete the below steps to configure the merchant’s Payright Merchant Credentials in Magento Admin.

Note: Prerequisite for this section is to obtain a Payright Merchant Username, Merchant Password, Client Username, Client Password and an Api Key from Payright.

1. Navigate to Magento Admin/System/Configuration/Sales/Payment Methods/Payright
2. Enter the Username, Password and API key.
3. Enter the Merchant Name and Merchant Password.
4. Enable Payright plugin by selecting "yes" from the Enabled field.
5. Configure the Payright API Mode (Sandbox Mode for testing on a staging instance and Production Mode for a live website and legitimate transactions).
6. Save the configuration.

# 1.3	Payright Display Configuration

1. Navigate to System/Configuration/Sales/Payment Methods/Payright
2. Configure the display of the Payright installments details on Product Pages (individual product display pages).
3. Enter a Minimum amount to display the installments.
4. Login to Magento Admin and navigate to System/Cache Management.
5. Flush the cache storage by selecting Flush Cache Storage
