<?php

namespace App\Helpers;

use App\Exceptions\BadRequestException;
use App\Exceptions\ForbiddenException;
use App\Helpers\SmsHelper;
use App\Helpers\StringHelper;
use App\Jobs\AssignOrder;
use App\Jobs\SendPushNotification;
use App\Jobs\SendSms;
use App\Models\Customer;
use App\Models\Menu;
use App\Models\MenuOption;
use App\Models\MenuOptionItem;
use App\Models\MenuTopping;
use App\Models\MenuVariant;
use App\Models\MenuVariationValue;
use App\Models\RestaurantBranch;
use App\Models\RestaurantOrder;
use App\Models\RestaurantOrderContact;
use App\Models\RestaurantOrderDriver;
use App\Models\RestaurantOrderItem;
use App\Models\RestaurantOrderStatus;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

trait RestaurantOrderHelper
{
    public static function validateOrder($request, $customerSlug = false)
    {
        $rules = [
            'slug' => 'required|unique:restaurant_orders',
            'required_if:order_type,schedule',
            'special_instruction' => 'nullable',
            'payment_mode' => 'required|in:COD,CBPay,KPay,MABPay',
            'delivery_mode' => 'required|in:pickup,delivery',
            'restaurant_branch_slug' => 'required|exists:App\Models\RestaurantBranch,slug',
            'promo_code' => 'nullable|string|exists:App\Models\Promocode,code',
            'order_type' => 'nullable|string',
            'customer_info' => 'required',
            'customer_info.customer_name' => 'required|string',
            'customer_info.phone_number' => 'required|string',
            'address' => 'required',
            'address.house_number' => 'nullable|string',
            'address.floor' => 'nullable|integer|min:0|max:50',
            'address.street_name' => 'nullable|string',
            'address.latitude' => 'nullable|numeric',
            'address.longitude' => 'nullable|numeric',
            'delivery_fee' => 'nullable|numeric',
            'order_items' => 'required|array',
            'order_items.*.slug' => 'required|exists:App\Models\Menu,slug',
            'order_items.*.quantity' => 'required|integer',
            'order_items.*.variation_value_slugs' => 'nullable|array',
            'order_items.*.topping_ slugs' => 'nullable|array',
            // 'order_items.*.variation_value_slugs.*' => 'required|exists:App\Models\MenuVariationValue,slug',
            'order_items.*.topping_slugs.*.slug' => 'required|exists:App\Models\MenuTopping,slug',
            'order_items.*.topping_slugs.*.value' => 'required|integer',
        ];

        if ($customerSlug) {
            $rules['customer_slug'] = 'required|string|exists:App\Models\Customer,slug';
        }

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return $validator->errors()->first();
        }

        $validatedData = $validator->validated();
        $validatedData['order_type'] = $request->order_type ? $request->order_type : 'instant';

        if ($validatedData['order_type'] === 'instant') {
            $validatedData['order_date'] = Carbon::now();
        } else {
            $validatedData['order_date'] = $request->order_date;
        }

        return $validatedData;
    }

    public static function checkOpeningTime($slug)
    {
        $restaurantBranch = RestaurantBranch::where('slug', $slug)->first();

        $openingTime = Carbon::parse($restaurantBranch->opening_time);
        $closingTime = Carbon::parse($restaurantBranch->closing_time);
        $now = Carbon::now();

        if ($now->lt($openingTime) || $now->gt($closingTime)) {
            throw new ForbiddenException("Ordering is not available yet at this hour, Please place your order @ {$openingTime->format('H:i')} am - {$closingTime->format('h:i')} pm. Thank you for shopping with Beehive.");
        }
    }

    public static function prepareRestaurantVariations($validatedData)
    {
        $orderItems = [];
        $subTotal = 0;
        $tax = 0;

        foreach ($validatedData['order_items'] as $key => $value) {
            $menu = self::getMenu($value['slug']);
            $variations = self::prepareVariations($value);
            $toppings = collect(self::prepareToppings($value['topping_slugs'], $menu->id));
            $amount = $menu->price + $variations->sum('price') + $toppings->sum('price');

            $subTotal += ($amount - $menu->discount) * $value['quantity'];

            $tax += ($amount - $menu->discount) * $menu->tax * 0.01 * $value['quantity'];
            $menu['amount'] = $amount;
            $menu['quantity'] = $value['quantity'];
            $menu['variations'] = $variations;
            $menu['toppings'] = $toppings;
            $menu['tax'] = ($amount - $menu->discount) * $menu->tax * 0.01;

            array_push($orderItems, $menu->toArray());
        }

        $validatedData['product_id'] = $menu['id'];
        $validatedData['order_items'] = $orderItems;
        $validatedData['subTotal'] = $subTotal;
        $validatedData['tax'] = $tax;

        if (!isset($validatedData['delivery_fee'])) {
            $validatedData['delivery_fee'] = 0;
        }

        $restaurantBranch = self::getRestaurantBranch($validatedData['restaurant_branch_slug']);

        $validatedData['restaurant_branch_info'] = $restaurantBranch;
        $validatedData['restaurant_id'] = $restaurantBranch->restaurant->id;
        $validatedData['restaurant_branch_id'] = $restaurantBranch->id;

        return $validatedData;
    }

    public static function checkVariationsExist($menus)
    {
        foreach ($menus as $key => $value) {
            $variationsCount = Menu::where('slug', $value['slug'])->first()->menuVariations()->count();

            if ($variationsCount > 0 && empty($value['variation_value_slugs'])) {
                return 'The order_items.' . $key . '.variation_value_slugs is required.';
            }
        }
    }

    public static function getRestaurantBranch($slug)
    {
        return RestaurantBranch::with('restaurant')->where('slug', $slug)->first();
    }

    public static function getTax()
    {
        return Setting::where('key', 'tax')->first()->value;
    }

    public static function createOrderStatus($orderId, $orderStatus = 'pending')
    {
        RestaurantOrder::where('id', $orderId)->update(['order_status' => $orderStatus]);

        $paymentStatus = null;

        if ($orderStatus === 'delivered') {
            $paymentStatus = 'success';
        } elseif ($orderStatus === 'cancelled') {
            $paymentStatus = 'failed';
        }

        if ($paymentStatus) {
            RestaurantOrder::where('id', $orderId)->update(['payment_status' => $paymentStatus]);
        }

        RestaurantOrderStatus::create([
            'status' => $orderStatus,
            'restaurant_order_id' => $orderId,
        ]);
    }

    public static function createOrderContact($orderId, $customerInfo, $address)
    {
        $customerInfo = array_merge($customerInfo, $address);
        $customerInfo['restaurant_order_id'] = $orderId;
        RestaurantOrderContact::create($customerInfo);
    }

    public static function createOrderItems($orderId, $orderItems)
    {
        foreach ($orderItems as $item) {
            $menu = self::getMenu($item['slug']);

            $item['menu_name'] = $item['name'];
            $item['restaurant_order_id'] = $orderId;
            $item['menu_id'] = $menu->id;
            $item['restaurant_id'] = $menu->restaurant_id;
            $item['category'] = $menu->restaurantCategory->name;

            RestaurantOrderItem::create($item);
        }
    }

    private static function prepareVariations($orderItem)
    {
        $variations = [];

        if (isset($orderItem['variation_value_slugs'])) {
            $menuId = Menu::where('slug', $orderItem['slug'])->value('id');

            foreach ($orderItem['variation_value_slugs'] as $variationValueSlug) {
                $variationValue = self::getMenuVariationValue($variationValueSlug);

                if ($variationValue && $variationValue->menuVariation->menu_id === $menuId) {
                    $variation = [
                        'name' => $variationValue->menuVariation->name,
                        'value' => $variationValue->value,
                        'price' => (int) $variationValue->price,
                    ];

                    array_push($variations, $variation);
                }
            }
        }

        return collect($variations);
    }

    private static function prepareToppings($toppings, $menuId)
    {
        self::checkToppings($toppings, $menuId);

        return collect($toppings)->map(function ($topping) {
            $menuTopping = MenuTopping::where('slug', $topping['slug'])->first();

            return [
                'name' => $menuTopping->name,
                'value' => $topping['value'],
                'price' => $menuTopping->price * $topping['value'],
            ];
        });
    }

    private static function checkToppings($toppings, $menuId)
    {
        collect($toppings)->map(function ($item) use ($menuId) {
            $menuTopping = MenuTopping::where('slug', $item['slug'])->first();

            if ($menuTopping->menu_id !== $menuId) {
                $menu = Menu::where('id', $menuId)->first();
                throw new BadRequestException('The \'' . $menuTopping->name . '\' topping is not part of the menu \'' . $menu->name . '\'.');
            }
        });
    }

    private static function prepareOptions($optionItems, $menuId)
    {
        self::checkOptionItemDuplicate($optionItems);
        self::checkOptions($optionItems, $menuId);

        return collect($optionItems)->map(function ($item) {
            $menuOptionItem = MenuOptionItem::where('slug', $item)->first();

            return [
                'name' => $menuOptionItem->name,
                'price' => $menuOptionItem->price,
            ];
        });
    }

    private static function checkOptions($optionItems, $menuId)
    {
        $optionsCount = collect($optionItems)->map(function ($item) use ($menuId) {
            $menuOptionItem = MenuOptionItem::with('menuOption')->where('slug', $item)->first();

            if ($menuOptionItem->menuOption->menu_id !== $menuId) {
                $menu = Menu::where('id', $menuId)->first();
                throw new BadRequestException('The \'' . $menuOptionItem->name . '\' option is not part of the menu \'' . $menu->name . '\'.');
            }

            return $menuOptionItem->menu_option_id;
        })->countBy();

        foreach ($optionsCount as $menuOptionId => $count) {
            $menuOption = MenuOption::with('menu')->where('id', $menuOptionId)->first();

            if ($count > $menuOption->max_choice) {
                throw new BadRequestException('Maximum options for \'' . $menuOption->name . '\' of \'' . $menuOption->menu->name . '\' is ' . $menuOption->max_choice . '.');
            }
        }
    }

    private static function checkOptionItemDuplicate($optionItems)
    {
        $itemsCount = array_count_values($optionItems);

        foreach ($itemsCount as $itemSlug => $count) {
            if ($count > 1) {
                $menuOptionItem = MenuOptionItem::where('slug', $itemSlug)->first();
                throw new BadRequestException('There are duplicate options for \'' . $menuOptionItem->menuOption->menu->name . '\'.');
            }
        }
    }

    private static function getMenu($slug)
    {
        return Menu::with('restaurantCategory')->where('slug', $slug)->first();
    }

    private static function getMenuVariationValue($slug)
    {
        return MenuVariationValue::with('menuVariation')->where('slug', $slug)->first();
    }

    public static function getBranches($request)
    {
        $query = RestaurantBranch::with('restaurant');
        return self::getBranchQuery($query, $request);
    }

    public static function getBranchQuery($query, $request)
    {
        $radius = CacheHelper::getRestaurantSearchRadius();

        return $query->with('restaurant')
            ->with('restaurant.availableTags')
            ->selectRaw('id, slug, name, address, contact_number, opening_time, closing_time, is_enable, restaurant_id,
            ( 6371 * acos( cos(radians(?)) *
                cos(radians(latitude)) * cos(radians(longitude) - radians(?))
                + sin(radians(?)) * sin(radians(latitude)) )
            ) AS distance', [$request->lat, $request->lng, $request->lat])
            ->whereHas('restaurant', function ($q) {
                $q->where('is_enable', 1);
            })
            ->where('is_enable', 1)
            ->having('distance', '<', $radius);
    }

    public static function validateOrderV3($request, $customerSlug = false)
    {
        $rules = self::getRulesV3($customerSlug);

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return $validator->errors()->first();
        }

        $validatedData = $validator->validated();
        $validatedData['order_type'] = $request->order_type ? $request->order_type : 'instant';

        if ($validatedData['order_type'] === 'instant') {
            $validatedData['order_date'] = Carbon::now();
        } else {
            $validatedData['order_date'] = $request->order_date;
        }

        if (Auth::guard('customers')->check()) {
            $validatedData['customer_id'] = Auth::guard('customers')->user()->id;
        } else {
            $validatedData['customer_id'] = Customer::where('slug', $validatedData['customer_slug'])->first()->id;
        }

        return $validatedData;
    }

    private static function getRulesV3($customerSlug = false)
    {
        $rules = [
            'slug' => 'required|unique:restaurant_orders',
            'order_date' => 'required_if:order_type,schedule',
            'special_instruction' => 'nullable',
            'payment_mode' => 'required|in:COD,CBPay,KPay,MABPay',
            'delivery_mode' => 'nullable|in:pickup,delivery',
            'restaurant_branch_slug' => 'required|exists:App\Models\RestaurantBranch,slug',
            'promo_code' => 'nullable|string|exists:App\Models\Promocode,code',
            'order_type' => 'nullable|in:instant,pickup,schedule',
            'customer_info' => 'required',
            'customer_info.customer_name' => 'required|string',
            'customer_info.phone_number' => 'required|string',
            'address' => 'required',
            'address.house_number' => 'nullable|string',
            'address.floor' => 'nullable|integer|min:0|max:50',
            'address.street_name' => 'nullable|string',
            'address.latitude' => 'nullable|numeric',
            'address.longitude' => 'nullable|numeric',
            'delivery_fee' => 'nullable|numeric',
            'order_items' => 'required|array',
            'order_items.*.slug' => 'required|exists:App\Models\Menu,slug',
            'order_items.*.quantity' => 'required|integer',
            'order_items.*.variant_slug' => 'required|exists:App\Models\MenuVariant,slug',
            'order_items.*.topping_slugs' => 'nullable|array',
            'order_items.*.topping_slugs.*.slug' => 'required|exists:App\Models\MenuTopping,slug',
            'order_items.*.topping_slugs.*.value' => 'required|integer',
            'order_items.*.option_items' => 'nullable|array',
            'order_items.*.option_items.*' => 'required|exists:App\Models\MenuOptionItem,slug',
            'order_items.*.special_instruction' => 'nullable|string',
        ];

        if ($customerSlug) {
            $rules['customer_slug'] = 'required|string|exists:App\Models\Customer,slug';
        }

        return $rules;
    }

    public static function prepareRestaurantVariants($validatedData)
    {
        $orderItems = [];
        $subTotal = 0;
        $tax = 0;
        $commission = 0;
        $restaurantBranch = self::getRestaurantBranch($validatedData['restaurant_branch_slug']);

        foreach ($validatedData['order_items'] as $key => $value) {
            $menuId = Menu::where('slug', $value['slug'])->value('id');
            $menuVariant = MenuVariant::with('menu')->where('slug', $value['variant_slug'])->where('is_enable', 1)->first();

            if (!$menuVariant) {
                throw new ForbiddenException('The order_items.' . $key . '.variant is disabled.', 403);
            }

            if ($menuId !== $menuVariant->menu->id) {
                throw new BadRequestException('The order_items.' . $key . '.variant_slug must be part of the menu.');
            }

            if (!isset($value['option_items'])) {
                $value['option_items'] = [];
            }

            $toppings = collect(self::prepareToppings($value['topping_slugs'], $menuId));
            $options = collect(self::prepareOptions($value['option_items'], $menuId));

            $item['slug'] = $value['slug'];
            $item['name'] = $menuVariant->menu->name;
            $item['quantity'] = $value['quantity'];
            $item['amount'] = $menuVariant->price + $toppings->sum('price') + $options->sum('price');
            $item['tax'] = ($item['amount'] - $menuVariant->discount) * $menuVariant->tax * 0.01;
            $item['discount'] = $menuVariant->discount;
            $item['variant'] = $menuVariant->variant;
            $item['toppings'] = $toppings;
            $item['options'] = $options;
            $item['special_instruction'] = isset($value['special_instruction']) ? $value['special_instruction'] : null;
            $item['menu_id'] = $menuId;

            $subTotal += ($item['amount'] - $menuVariant->discount) * $value['quantity'];
            $tax += ($item['amount'] - $menuVariant->discount) * $menuVariant->tax * 0.01 * $value['quantity'];

            if ($restaurantBranch->restaurant->commission > 0) {
                $commission += ($item['amount'] * $value['quantity']) * $restaurantBranch->restaurant->commission * 0.01;
            }

            array_push($orderItems, $item);
        }

        $validatedData['order_items'] = $orderItems;
        $validatedData['subTotal'] = $subTotal;
        $validatedData['tax'] = $tax;
        $validatedData['commission'] = $commission;

        if (!isset($validatedData['delivery_fee'])) {
            $validatedData['delivery_fee'] = 0;
        }

        $validatedData['restaurant_branch_info'] = $restaurantBranch;
        $validatedData['restaurant_id'] = $restaurantBranch->restaurant->id;
        $validatedData['restaurant_branch_id'] = $restaurantBranch->id;

        return $validatedData;
    }

    public static function notifySystem($order, $phoneNumber, $messageService)
    {
        self::sendPushNotifications($order, $order->restaurant_branch_id);
        self::sendSmsNotifications($order->restaurant_branch_id, $phoneNumber, $messageService);
        // self::assignBiker($order->order_type, $order->slug);
    }

    public static function sendPushNotifications($order, $branchId, $message = null)
    {
        $order = json_decode(json_encode($order), true);
        self::sendAdminPushNotifications($order, $message);
        self::sendVendorPushNotifications($order, $branchId, $message);
        self::sendDriverPushNotifications($order, $message);
    }

    private static function sendAdminPushNotifications($order, $message = null)
    {
        $admins = User::whereHas('roles', function ($query) {
            $query->where('name', 'Admin');
        })->pluck('slug');

        $request = new Request();
        $request['slugs'] = $admins;
        $request['message'] = $message ? $message : 'A restaurant order has been received.';
        $request['data'] = self::preparePushData($order);

        $appId = config('one-signal.admin_app_id');
        $fields = OneSignalHelper::prepareNotification($request, $appId);
        $uniqueKey = StringHelper::generateUniqueSlug();

        SendPushNotification::dispatch($uniqueKey, $fields, 'admin');
    }

    private static function sendVendorPushNotifications($order, $branchId, $message = null)
    {
        $vendors = User::where('restaurant_branch_id', $branchId)->pluck('slug');
        $request = new Request();
        $request['slugs'] = $vendors;
        $request['message'] = $message ? $message : 'An order has been received.';
        $request['data'] = self::preparePushData($order);
        $request['android_channel_id'] = '28503fba-9837-4521-896e-7897e2e8b150';
        $request['url'] = 'http://www.beehivevendor.com/status?&slug=' . $order['slug'] . '&orderStatus=' . $order['order_status'];

        $appId = config('one-signal.vendor_app_id');
        $fields = OneSignalHelper::prepareNotification($request, $appId);
        $uniqueKey = StringHelper::generateUniqueSlug();

        SendPushNotification::dispatch($uniqueKey, $fields, 'vendor');
    }

    private static function sendDriverPushNotifications($order, $message = null)
    {
        $orderID = RestaurantOrder::where('slug', $order['slug'])->pluck('id');
        $driverID = RestaurantOrderDriver::where('restaurant_order_id', $orderID)->whereHas('status', function ($q) {
            $q->where('status', 'accepted');
        })->first();

        if ($driverID) {
            $driver = User::where('id', $driverID->user_id)->pluck('slug');
            $request = new Request();
            $request['slugs'] = $driver;
            $request['message'] = $message ? $message : 'An order has been updated.';

            $request['data'] = self::preparePushData($order, "driver_order_update");
            $request['android_channel_id'] = config('one-signal.android_channel_id');
            $request['url'] = 'hive://beehivedriver/job?&slug=' . $order['slug'] . '&orderStatus=' . $order['order_status'];

            $appId = config('one-signal.admin_app_id');

            $fields = OneSignalHelper::prepareNotification($request, $appId);
            $uniqueKey = StringHelper::generateUniqueSlug();

            $response = OneSignalHelper::sendPush($fields, 'admin');
        }
    }

    private static function preparePushData($order, $type = "restaurant_order")
    {
        unset($order['created_by']);
        unset($order['updated_by']);
        unset($order['restaurant_order_items']);

        return [
            'type' => $type,
            'body' => $order,
        ];
    }

    public static function sendSmsNotifications($branchId, $customerPhoneNumber, $messageService)
    {
        // self::sendAdminSms();
        self::sendVendorSms($branchId, $messageService);
        self::sendCustomerSms($customerPhoneNumber, $messageService);
    }

    private static function sendAdminSms()
    {
        $admins = User::whereHas('roles', function ($query) {
            $query->where('name', 'Admin');
        })->pluck('phone_number');

        $message = 'A restaurant order has been received.';
        $smsData = SmsHelper::prepareSmsData($message);
        $uniqueKey = StringHelper::generateUniqueSlug();

        if (count($admins) > 0) {
            SendSms::dispatch($uniqueKey, $admins, $message, 'order', $smsData);
        }
    }

    private static function sendVendorSms($branchId, $messageService)
    {
        $vendors = RestaurantBranch::where('id', $branchId)->value('notify_numbers');

        $message = 'An order has been received.';
        $smsData = SmsHelper::prepareSmsData($message);
        $uniqueKey = StringHelper::generateUniqueSlug();

        if ($vendors !== null && count($vendors) > 0) {
            SendSms::dispatch($uniqueKey, $vendors, $message, 'order', $smsData, $messageService);
        }
    }

    private static function sendCustomerSms($phoneNumber, $messageService)
    {
        $message = 'Your order has successfully been created.';
        $smsData = SmsHelper::prepareSmsData($message);
        $uniqueKey = StringHelper::generateUniqueSlug();

        SendSms::dispatch($uniqueKey, [$phoneNumber], $message, 'order', $smsData, $messageService);
    }

    private static function assignBiker($orderType, $orderSlug)
    {
        if ($orderType === 'instant') {
            $uniqueKey = StringHelper::generateUniqueSlug();
            AssignOrder::dispatch($uniqueKey, $orderSlug, 'restaurant');
        }
    }
}
