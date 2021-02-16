<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Restaurant;

class RestaurantController extends Controller
{
    protected $customer_id;
     /**
     * Create a new ShopController instance.
     *
     * @return void
     */
     public function __construct()
        {
            if (Auth::guard('customers')->check()) {
                $this->customer_id = Auth::guard('customers')->user()->id;
            }
        }

     /**
     * Set the favorite shop  for favorite_shop table.
     *
     * @param  int  $slug
     * @return \Illuminate\Http\Response
     */
    public function setFavoriteRestaurant($slug)
    {
        $customer = Customer::where('slug', $customer_id)->firstOrFail();

        $restaurantId = Restaurant::where('slug', $slug)->firstOrFail();

        $customer->shops()->attach($restaurantId);

        return response()->json(['message' => 'Success.'], 200);
    }
}