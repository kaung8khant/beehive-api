<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Restaurant;
use App\Models\Customer;

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
     * Set the favorite restaurant  for favorite_restaurant table.
     *
     * @param  int  $slug
     * @return \Illuminate\Http\Response
     */
    public function setFavoriteRestaurant($slug)
    {
        $customer = Customer::where('id', $this->customer_id)->firstOrFail();

        $restaurant = Restaurant::where("id",$slug)->firstOrFail();

        try {

            $customer->restaurants()->attach($slug);

          } catch (\Illuminate\Database\QueryException $e) {

            return "Restaurant is already exit.";
          }

        return response()->json(['message' => 'Success.'], 200);
    }

     /**
     * remove the favorite restaurant  for favorite_restaurant table.
     *
     * @param  int  $slug
     * @return \Illuminate\Http\Response
     */
    public function removeFavoriteRestaurant($slug)
    {
        $customer = Customer::where('id', $this->customer_id)->firstOrFail();

        $restaurant = Restaurant::where("id",$slug)->firstOrFail();

        $customer->restaurants()->detach($slug);

        return response()->json(['message' => 'Success.'], 200);
    }
}
