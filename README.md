# payright-magento1.9

PayRight payment method plugin for Magento v1.9

# 1.1 New Payright Installation

This section outlines the steps to install the Payright plugin for the first time.

>  [MAGENTO] refers to the root folder where Magento is installed.

## Steps

1. Download the PayRight plugin, as a `.zip` or `tar.gz` file from the Payright GitHub directory.
2. Unzip the file.
3. Copy all files in `/app/code/community/` to `[MAGENTO]/app/code/community`.
4. Copy all files in `/app/design/frontend/base/default/layout` to `[MAGENTO]/app/design/frontend/base/default/layout`.
5. Copy all files in `/app/design/frontend/base/default/template` to `[MAGENTO]/app/design/frontend/base/default/template`.
6. Copy all files in `/app/etc/modules/`  to `[MAGENTO]/app/etc/modules`.
7. Copy all files in ` /js/`  to `[MAGENTO]/js/`.
8. Copy all files in `/skin/frontend/base/default/` to `[MAGENTO]/skin/frontend/base/default/`.
9. Login to Magento Admin and navigate to **System** > **Cache Management**.
10. Flush the cache storage by selecting **Flush Cache Storage** from **System** > **Cache Management**.

# 1.2  Payright Merchant Setup

Complete the below steps to configure the merchant’s Payright merchant configuration settings in Magento Admin.

## Requirements

1. Acquire a Payright merchant **Access Token** from Payright. This is obtained when creating a new **Access Token** for a merchant store.

## Steps

1. Login to Magento Admin and navigate to **System** > **Configuration** > **Sales** > **Payment Methods** > **Payright**.
2. Enter your store **Access Token**.
3. Enable the Payright plugin by selecting "yes" from the Enabled field.
4. Configure the Payright API Mode
   1. **Sandbox Mode** for testing on a staging instance.
   2. **Production Mode** for a live website and legitimate transactions.
5. Save the configuration settings.

# 1.3  Payright Display Configuration

## Steps

1. Login to Magento Admin and navigate to **System** > **Configuration** > **Sales** > **Payment Methods** > **Payright**.
2. Configure the display of the Payright installments details on Product Pages (individual product display pages).
3. Enter a **Minimum Amount** to display the installments.
4. Then, navigate to **System** > **Cache Management**.
5. Flush the cache storage by selecting **Flush Cache Storage** from **System** > **Cache Management**.