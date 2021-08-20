<?php

namespace App\Http\Controllers\Cart;

use App\Exceptions\BadRequestException;
use App\Helpers\ResponseHelper;
use App\Helpers\StringHelper;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Menu;
use App\Models\MenuTopping;
use App\Models\MenuVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RestaurantCartController extends Controller
{
    use ResponseHelper;

    public function store(Request $request, Menu $menu)
    {
        $validator = Validator::make($request->all(), [
            'customer_slug' => 'required|exists:App\Models\Customer,slug',
            'variant' => 'required|array',
            'variant.*.name' => 'required',
            'variant.*.value' => 'required',
            'toppings' => 'nullable|array',
            'toppings.*.slug' => 'required|exists:App\Models\MenuTopping',
            'toppings.*.quantity' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return $this->generateResponse($validator->errors()->first(), 422, true);
        }

        $customer = Customer::where('slug', $request->customer_slug)->firstOrFail();

        try {
            $menuVariant = $this->getVariant($menu, $request->variant);
            $toppings = $this->getToppings($menu, $request->toppings);

            $data = [
                'slug' => StringHelper::generateUniqueSlug(),
                'amount' => $menuVariant->price,
                'tax' => $menuVariant->tax,
                'discount' => $menuVariant->discount,
                'promocode' => null,
                'promocode_amount' => 0,
                'commission' => 0,
                'total_amount' => $menuVariant->price,
                'menus' => [
                    [
                        'slug' => $menu->slug,
                        'name' => $menu->name,
                        'description' => $menu->description,
                        'variant' => $menuVariant,
                        'toppings' => $toppings,
                    ],
                ],
                'address' => $customer->primary_address,
            ];

            return $this->generateResponse($data, 200);

        } catch (BadRequestException $e) {
            return $this->generateResponse($e->getMessage(), 400, true);
        }
    }

    private function getVariant($menu, $variant)
    {
        $menuVariants = MenuVariant::where('menu_id', $menu->id)->get();

        $menuVariant = $menuVariants->first(function ($value) use ($variant) {
            return $variant == $value->variant;
        });

        if (!$menuVariant) {
            throw new BadRequestException('The variant must be part of the menu.');
        }

        return $menuVariant;
    }

    private function getToppings($menu, $toppings)
    {
        $menuToppings = [];

        foreach ($toppings as $key => $value) {
            $menuTopping = MenuTopping::where('slug', $value['slug'])->first();

            if ($menuTopping->menu_id != $menu->id) {
                throw new BadRequestException('The selected toppings.' . $key . '.must be part of the menu.');
            }

            $menuToppings[] = $menuTopping;
        }

        return $menuToppings;
    }

    public function update(Request $request, Menu $menu)
    {
        $customer = Customer::where('slug', $request->customer_slug)->firstOrFail();
        $menuVariant = MenuVariant::where('slug', $request->variant_slug)->firstOrFail();
        $toppings = MenuTopping::whereIn('slug', $request->topping_slugs)->get();

        $data = [
            'slug' => StringHelper::generateUniqueSlug(),
            'amount' => $menuVariant->price,
            'tax' => $menuVariant->tax,
            'discount' => $menuVariant->discount,
            'promocode' => null,
            'promocode_amount' => 0,
            'commission' => 0,
            'total_amount' => $menuVariant->price,
            'menus' => [
                [
                    'slug' => $menu->slug,
                    'name' => $menu->name,
                    'description' => $menu->description,
                    'variant' => $menuVariant,
                    'toppings' => $toppings,
                ],
            ],
            'address' => $customer->primary_address,
        ];

        return $this->generateResponse($data, 200);
    }

    public function viewCart(Request $request)
    {
        $customer = Customer::where('slug', $request->customer_slug)->firstOrFail();
        $menu = Menu::where('slug', 'E0FBE079')->firstOrFail();
        $menuVariant = MenuVariant::where('slug', 'E857AEB9')->firstOrFail();
        $toppings = MenuTopping::whereIn('slug', ['E5BCA2D6'])->get();

        $data = [
            'restaurant' => [
                'slug' => StringHelper::generateUniqueSlug(),
                'amount' => $menuVariant->price,
                'tax' => $menuVariant->tax,
                'discount' => $menuVariant->discount,
                'promocode' => null,
                'promocode_amount' => 0,
                'commission' => 0,
                'total_amount' => $menuVariant->price,
                'menus' => [
                    [
                        'slug' => $menu->slug,
                        'name' => $menu->name,
                        'description' => $menu->description,
                        'variant' => $menuVariant,
                        'toppings' => $toppings,
                    ],
                ],
                'address' => $customer->primary_address],
            'shop' => [],
        ];

        return $this->generateResponse($data, 200);
    }

    public function delete(Request $request, Menu $menu)
    {
        $customer = Customer::where('slug', $request->customer_slug)->firstOrFail();
        $menuVariant = MenuVariant::where('slug', 'E857AEB9')->firstOrFail();
        $toppings = MenuTopping::whereIn('slug', ['E5BCA2D6'])->get();

        $data = [
            'slug' => StringHelper::generateUniqueSlug(),
            'amount' => $menuVariant->price,
            'tax' => $menuVariant->tax,
            'discount' => $menuVariant->discount,
            'promocode' => null,
            'promocode_amount' => 0,
            'commission' => 0,
            'total_amount' => $menuVariant->price,
            'menus' => [
                [
                    'slug' => $menu->slug,
                    'name' => $menu->name,
                    'description' => $menu->description,
                    'variant' => $menuVariant,
                    'toppings' => $toppings,
                ],
            ],
            'address' => $customer->primary_address,
        ];

        return $this->generateResponse($data, 200);
    }

    public function deleteCart(Request $request)
    {
        $customer = Customer::where('slug', $request->customer_slug)->firstOrFail();

        return $this->generateResponse('success', 200, true);
    }
}
