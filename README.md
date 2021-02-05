## payright-magento1.9

PayRight payment method plugin for Magento v1.9, follow the steps below for 
configuration setup and installation.

### Security Warning

30 June 2020, Adobe no longer offers security updates for the Magento v1.x platform. 

Hence, this means that the stores running Magento v1.x will be vulnerable to cyber attacks.

It is recommended to upgrade your store to Magento v2.x, and install our Magento v2.x Payright plugin.

### 1.1 New Payright Installation

This section outlines the steps to install the Payright plugin for the first time.

>  [MAGENTO] refers to the installed Magento file directory. Such as `/var/www/magento1.9`

#### Steps

1. Download the PayRight plugin, as a `.zip` or `tar.gz` file from the Payright GitHub directory.
2. Unzip the file.
3. To copy all files, use the `copy_files.sh` bash script and define your Magento v1.x installation path. 
   Or, you may copy files / folders individually like below:
    1. Copy all files in `/app/code/community/` to `[MAGENTO]/app/code/community`.
    1. Copy all files in `/app/design/frontend/base/default/layout` to `[MAGENTO]/app/design/frontend/base/default/layout`.
    1. Copy all files in `/app/design/frontend/base/default/template` to `[MAGENTO]/app/design/frontend/base/default/template`.
    1. Copy all files in `/app/etc/modules/`  to `[MAGENTO]/app/etc/modules`.
    1. Copy all files in `/js/`  to `[MAGENTO]/js/`.
    1. Copy all files in `/skin/frontend/base/default/` to `[MAGENTO]/skin/frontend/base/default/`.
1. Login to Magento Admin and navigate to **System** > **Cache Management**.
1. Flush the cache storage by selecting **Flush Cache Storage** from **System** > **Cache Management**.

### 1.2  Payright Merchant Setup

Complete the below steps to configure the merchant’s Payright merchant configuration settings in Magento Admin.

#### Requirements

1. Acquire a Payright merchant **Access Token** from Payright. This is obtained when creating a new **Access Token** for a merchant store.

#### Steps

1. Login to Magento Admin and navigate to **System** > **Configuration** > **Sales** > **Payment Methods** > **Payright**.
1. Enter your store **Access Token**.
1. Enable the Payright plugin by selecting "yes" from the Enabled field.
1. Configure the Payright API Mode
   1. **Sandbox Mode** for testing on a staging instance.
   1. **Production Mode** for a live website and legitimate transactions.
1. Save the configuration settings.

### 1.3  Payright Display Configuration

#### Steps

1. Login to Magento Admin and navigate to **System** > **Configuration** > **Sales** > **Payment Methods** > **Payright**.
1. Configure the display of the Payright installments details on Product Pages (individual product display pages).
1. Enter a **Minimum Amount** to display the installments.
1. Then, navigate to **System** > **Cache Management**.
1. Flush the cache storage by selecting **Flush Cache Storage** from **System** > **Cache Management**.