# FraudLabs Pro Plugin for Sylius
Protect your Sylius eCommerce store from fraud with [FraudLabs Pro](https://www.fraudlabspro.com/). This plugin integrates seamlessly into the Sylius checkout process, automatically screening orders for fraud risk using the FraudLabs Pro API.

By utilizing Security through Ambiguity, it securely halts fraudulent transactions during the checkout flow without exposing your security rules to bad actors.

## Requirements

- PHP 8.2 or higher

- Sylius 2.0 or higher

## Installation
1. Require the plugin via Composer:

Bash
composer require fraudlabspro/sylius-fraudlabs-pro-plugin
2. Register the plugin:

Add the bundle to your `config/bundles.php` file (if Symfony Flex didn't do it automatically):

```PHP
return [
    // ...
    FraudLabsPro\SyliusFraudLabsProPlugin\FraudLabsProSyliusFraudLabsProPlugin::class => ['all' => true],
];
```

3. Update your database:

Because this plugin adds a `ChannelConfiguration` entity to securely store your API keys per channel, you must run database migrations:
```Bash
bin/console doctrine:migrations:diff
bin/console doctrine:migrations:migrate
```

4. Clear the application cache:

```Bash
bin/console cache:clear
```

## Usage & Configuration

Once installed, the plugin is managed entirely from your Sylius Admin panel. You do not need to configure any YAML files!

1. Log into your Sylius Admin dashboard.

2. Navigate to **Configuration -> Channels** in the left sidebar.

3. Edit the channel you want to protect.

4. Scroll to the **FraudLabs Pro** section at the bottom of the page.

5. Check the **Enable FraudLabs Pro Validator** box.

6. Enter your **FraudLabs Pro API Key** (You can find this in your [Merchant Dashboard](https://www.fraudlabspro.com/merchant/dashboard)).

7. Click Save changes.

The plugin will now automatically screen all checkouts processed through that channel!

## How It Works
When a customer attempts to complete their order, this plugin silently sends the transaction data (billing details, IP address, etc.) to the FraudLabs Pro API.

- If the transaction is approved, the checkout proceeds normally.

- If the transaction is flagged as `REJECT` by your FraudLabs Pro rules, the order is halted, and the customer is safely redirected back to the checkout summary with a generic error message ("An error occurred while processing your order. Please review your details and try again.") to prevent hinting to fraudsters.

## License
This plugin is licensed under the MIT License.