<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Customer;
use App\Models\Shop;

class ShopController extends Controller
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

    public function setFavoriteShop($slug)
    {
        $customer = Customer::where('id', $this->customer_id)->firstOrFail();
        $shop = Shop::where("id",$slug)->firstOrFail();
        try {

            $customer->shops()->attach($slug);

          } catch (\Illuminate\Database\QueryException $e) {

            return "Shop is already exit.";
          }

        return response()->json(['message' => 'Success.'], 200);
    }



     /**
     * remove the favorite shop  for favorite_shop table.
     *
     * @param  int  $slug
     * @return \Illuminate\Http\Response
     */

    public function removeFavoriteShop($slug)
    {
        $customer = Customer::where('id', $this->customer_id)->firstOrFail();

        $shop = Shop::where("id",$slug)->firstOrFail();

        $customer->shops()->detach($slug);

        return response()->json(['message' => 'Success.'], 200);
    }


}