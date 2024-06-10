# WooCommerce Stripe Payment API

This API enables you to create orders programmatically in WooCommerce and then process the payment using Stripe. Therefore, you need an active Stripe account configured in the WordPress admin.

### To use this API, the following plugins are required:

- WooCommerce
- WooCommerce Stripe Payment Gateway

### The API will accept the following parameters in the payload:

- Array of product IDs with quantity
- Shipping & Billing Address

You can find more detailed information in the Postman collection file.

### Here's a brief rundown of the process:

1. Prepare the cart for checkout, including Product, Quantity, and Address details.
2. Call the API with all parameters. This will create an order in WooCommerce.
3. The API will then create a payment intent in your Stripe account.

From the frontend, a call needs to be made to Stripe using the Stripe PaymentIntentID and card details to process the payment. Upon a successful transaction, a webhook will be called to update the order status to 'Processing'.

Note that the order is created programmatically here, but you can omit this as per your needs. The Stripe CustomerID is created here to save card details on Stripe and retrieve the user's card details from Stripe, which is also optional.

You can install the dependencies via Composer. Run the following command:
```
composer install
```

We hope you find this information helpful for your mobile or PWA app!