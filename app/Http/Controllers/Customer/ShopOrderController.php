<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Helpers\StringHelper;
use App\Models\ShopOrder;
use App\Models\ShopOrderStatus;
use App\Models\ShopOrderContact;
use App\Models\ShopOrderItem;
use App\Models\Product;
use App\Models\Menu;
use App\Models\Shop;
use Illuminate\Support\Facades\Log;
use App\Helpers\ResponseHelper;

class ShopOrderController extends Controller
{
    use StringHelper,ResponseHelper;

    protected $customer_id;

    public function __construct()
    {
        if (Auth::guard('customers')->check()) {
            $this->customer_id = Auth::guard('customers')->user()->id;
        }
    }

    public function index(Request $request)
    {
        $shopOrder = ShopOrder::with('contact','items','status')->where("customer_id",$this->customer_id)->paginate()->items();
        return $this->generateResponse($shopOrder, 201);
    }

    public function store(Request $request)
    {
        $request['slug'] = $this->generateUniqueSlug();

        $validatedData = $this->validateOrder($request);
        $validatedData['customer_id'] = $this->customer_id;
       
        $order = ShopOrder::create($validatedData);
        $orderId = $order->id;
        
        $this->createOrderStatus($orderId);
        
        $this->createOrderContact($orderId, $validatedData['customer_info']);
        
        $this->createOrderItems($orderId, $validatedData['order_items'], $request->order_type);
       
        return $this->generateResponse($order->refresh(), 201);
    }

    public function show($slug)
    {
        $shop = ShopOrder::where('slug', $slug)
            ->with('contact','items')
            ->firstOrFail();
        return $this->generateResponse($shop,200);
    }

    public function destroy($slug)
    {
        //destory function
    }
    public function cancelOrder($slug){
        $shopOrderID = $this->getShopOrderId($slug);
        $shopOrderStatus = ShopOrderStatus::where('shop_order_id',$shopOrderID)->firstOrFail();
        $shopOrderStatus->status = "cancelled";
        $shopOrderStatus->update();
        return $this->generateResponse($shopOrderStatus,200);
    }
    private function validateOrder(Request $request)
    {
        $rules = [
            'slug'=>'required',
            'order_date' => 'required|date_format:Y-m-d',
            'special_instruction' => 'nullable',
            'payment_mode' => 'required|in:COD,CBPay,KPay,MABPay',
            'delivery_mode' => 'required|in:package,delivery',
            'customer_info' => 'required',
            'customer_info.customer_name' => 'required|string',
            'customer_info.phone_number' => 'required|string',
            'customer_info.house_number' => 'required|string',
            'customer_info.floor' => 'nullable|string',
            'customer_info.street_name' => 'required|string',
            'customer_info.latitude' => 'nullable|numeric',
            'customer_info.longitude' => 'nullable|numeric',
            'order_items' => 'required|array',
            'order_items.*.product_slug' => '',
            'order_items.*.product_name' => 'required|string',
            'order_items.*.quantity' => 'required|integer',
            'order_items.*.amount' => 'required|numeric',
            'order_items.*.tax' => 'required|numeric',
            'order_items.*.discount' => 'required|numeric',
            'order_item.*.variations'=> 'required',
            'order_item.*.shop_slug'=>'required|string'
        ];


        return $request->validate($rules);
    }

    private function getShopOrderId($slug)
    {
        return ShopOrder::where('slug', $slug)->firstOrFail()->id;
    }

    private function createOrderStatus($orderId, $status = 'pending')
    {
        $shop = ShopOrderStatus::create([
            'shop_order_id' => $orderId,
            'status' => $status,
        ]);
    }
    private function createOrderContact($orderId, $customerInfo)
    {
        $customerInfo['shop_order_id'] = $orderId;
        ShopOrderContact::create($customerInfo);
    }

    private function createOrderItems($orderId, $orderItems, $orderType)
    {

        foreach ($orderItems as $item) {
            $item['shop'] = $this->getShop($item['shop_slug']);
            $item['shop_order_id'] = $orderId;
            $item['item_id'] = $this->getProductId($item['product_slug']);
            $item['variations'] = $item['variations'];
            Log::info(json_encode($item['shop']));
            ShopOrderItem::create($item);
        }
    }
    private function getProductId($slug)
    {
        return Product::where('slug', $slug)->first()->id;
    }
    private function getShop($slug)
    {
        return Shop::where('slug', $slug)->firstOrFail();
    }
}
