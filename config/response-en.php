<?php

return [
    'restaurant_cart' => [
        'same_branch_err' => 'You can only order from same branch.',
        'variant_err' => 'The variant must be part of the menu.',
        'topping_err' => 'The selected toppings.%d must be part of the menu.',
        'topping_qty_err' => 'The selected toppings.%d.quantity cannot be higher than %d.',
        'no_item' => 'There is no such item in the cart.',
        'empty' => 'Your cart is empty.',

        'address_succ' => 'The new address can be delivered.',
        'address_err' => 'Sorry, your address cannot be delivered.',
    ],

    'shop_cart' => [
        'variant_err' => 'The variant must be part of the product.',
        'no_item' => 'There is no such item in the cart.',
        'empty' => 'Your cart is empty.',

        'address_succ' => 'The new address can be delivered.',
        'address_err' => 'Sorry, your address cannot be delivered.',
    ],

    'restaurant' => [
        'enable' => 'You cannot order from %s at the moment. Please contact support.',
    ],

    'restaurant_branch' => [
        'enable' => 'You cannot order from %s branch at the moment. Please contact support.',
    ],

    'menu' => [
        'enable' => 'You cannot order %s at the moment. Please contact support.',
    ],

    'shop' => [
        'enable' => 'You cannot order from %s at the moment. Please contact support.',
    ],

    'product' => [
        'enable' => 'You cannot order %s at the moment. Please contact support.',
    ],

    'restaurant_order' => [
        'order_sts_succ' => 'The order has successfully been %s.',
        'order_sts_err' => 'The order has already been %s.',
        'payment_err' => "You cannot change the order status to 'pick up' or 'delivered' when payment status is pending or cancelled.",
    ],

    'shop_order' => [
        'order_sts_succ' => 'The order has successfully been %s.',
        'order_sts_err' => 'The order has already been %s.',
        'payment_err' => "You cannot change the order status to 'pick up' or 'delivered' when payment status is pending or cancelled.",
    ],

    'promo_code' => [
        'not_found' => 'Promocode not found.',
        'invalid' => 'Invalid promocode.',
        'invalid_rest' => 'Invalid promocode usage for restaurant.',
        'invalid_shop' => 'Invalid promocode usage for shop.',
    ],
];
