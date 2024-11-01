=== WooCommerce Trustpay Gateway ===
Contributors: trustpay
Tags: woocommerce, woo, gateway, payment, payments, woocommerce gateway, checkout
Requires at least: 3.8
Tested up to: 4.7.3
Stable tag: 1.4.0
License: GPLv3 or later

Trustpay payment gateway for WooCommerce.

== Description ==

The [Trustpay](http://www.trustpay.biz/) Payment Gateway adds CREDIT CARDS, MOBILE WALLETS, VOUCHERS and other payment gateway integrations for most popular WordPress shopping cart - WooCommerce.

https://www.youtube.com/watch?v=S0mVQ1Mf_xc

= Requirements =

Must have applied and acquired a TrustPay Merchant Account [trustpay.biz](http://www.trustpay.biz/).

= About Trustpay =
TrustPay is a specialised emerging economy merchant services provider. We enable developers and product providers to directly transact and collect payments from a variety of sources in the emerging market of choice.

No Set up fees, no Monthly fees, No minimums. We charge a fixed rate of $0.03 USD per Transaction and deduct the acquiring fee charged from the source, the applicable banking fees, sales taxes where required from the settlement amount.

= Territories =
Our Territories are updated regularly.

Please visit www.trustpay.biz for Territory updates.

== Installation ==

**There are 2 ways to install WooCommerce Trustpay Gateway on your website:**

Upload the plugin to your blog plugin directory using ftp client or web file manager and activate it or;

Visit your blog admin panel and:

1. Click Plugins in the menu
2. Click Add New
3. In search field type "WooCommerce Trustpay Gateway" (without quotes)
4. Find WooCommerce Trustpay Gateway in the list and click Install Now.

== Screenshots ==

1. Trustpay settings.

== Frequently Asked Questions ==

= I have some troubles with plugin what should I do? =

Please use WooCommerce Trustpay Gateway support forum to address any issues you encounter while using TrustPay payment gateway.

== Change Log ==
= 1.4.0 =
* Updated: Removed default values for success and failure postback URLs. By default users will be redirected to WooCommerce order success/failure pages.
* Updated: Failing WooCommerce order payment status (upon successful payment) fixed.

= 1.3.2 =
* New: Added link to local PDF documentation (Plugins page).
* New: Added option to pick branding logo for payment gateway.
* Updated: Plugin assets updated.

= 1.3.1 =
* Updated: Added option to choose an customer identification method passed to payment gateway.

= 1.3.0 =
* Updated: Removed OAuth plugin dependency.
* Updated: Tested up to version bump.
* Updated: Documentation updated (getting-started.pdf).
* Updated: Fixed multi-currency payment / WooCommerce Currency Switcher issue.

= 1.2.0 =
* Updated: Improved UIX when paying with credit cards.
* Updated: Code review and cleanup.

= 1.1.5 =
* Updated: Shopping cart is cleared after successful payment.
* Updated: Failing payment issue fixed, when shared secret includes special characters.

= 1.1.4 =
* Updated: Tested up to version bumped to 4.5.2.

= 1.1.3 =
* Updated: Core updates to address recent TrustPay API changes.
* Updated: Minor readme.txt changes.

= 1.1.2 =
* New: Added getting started guide (in plugin directory).
* Updated: Minor readme.txt changes.

= 1.1.1 =
* Updated: readme.txt updated.

= 1.1.0 =
* New: Plugin dependency checking and notifications added.
* Updated: Multisite support added.
* Updated: Issue fixed with static Transaction ID preventing failed transactions to be retried.
* Updated: Issue fixed with failing OAuth authentication.
* Updated: Code clean up.

= 1.0.0 =
* Initial release
