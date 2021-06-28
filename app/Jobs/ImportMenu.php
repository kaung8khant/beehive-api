<?php

namespace App\Jobs;

use App\Helpers\StringHelper;
use App\Models\Menu;
use App\Models\MenuVariant;
use App\Models\Restaurant;
use App\Models\RestaurantCategory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Validator;

class ImportMenu implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $uniqueKey;
    protected $rows;

    /**
     * The number of seconds after which the job's unique lock will be released.
     *
     * @var int
     */
    public $uniqueFor = 3600;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($uniqueKey, $rows)
    {
        ini_set('max_execution_time', 300);

        $this->uniqueKey = $uniqueKey;
        $this->rows = $rows;
    }

    /**
     * The unique ID of the job.
     *
     * @return string
     */
    public function uniqueId()
    {
        return $this->uniqueKey;
    }


    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        foreach ($this->rows as $key => $row) {
            $rules = [
                'name' => 'required',
                'description' => 'nullable',
                'price' => 'required|numeric',
                'tax' => 'required|numeric',
                'discount' => 'required|numeric',
                'is_enable' => 'required|boolean',
            ];

            $validator = Validator::make(
                $row,
                $rules
            );

            if (!$validator->fails()) {
                $menuVariant = null;

                if (isset($row['id'])) {
                    $menuVariant = MenuVariant::where('slug', $row['menu_variant_slug'])->first();
                }

                $restaurant = Restaurant::where('slug', $row['restaurant_slug'])->firstOrFail();

                $menuData = [
                    'slug' => StringHelper::generateUniqueSlug(),
                    'name' => $row['name'],
                    'description' => $row['description'],
                    'is_enable' => $row['is_enable'],
                    'restaurant_id' => $restaurant->id,
                    'variants' => [],
                    'restaurant_category_id' => RestaurantCategory::where('slug', $row['restaurant_category_slug'])->value('id'),
                ];

                if (!$menuVariant) {
                    $menu = Menu::create($menuData);
                    $standardMenuVariant = [
                        'menu_id' => $menu->id,
                        'slug' => StringHelper::generateUniqueSlug(),
                        'variant' => json_decode('[{"value":"Standard"}]'),
                        'price' => $row['price'],
                        'tax' => $row['tax'],
                        'discount' => $row['discount'],
                    ];
                    MenuVariant::create($standardMenuVariant);

                    // NOTE:: i don't think we need this
                    foreach ($restaurant->restaurantBranches as $branch) {
                        $availableMenus = Menu::where('slug', $menu->slug)->pluck('id');
                        $branch->availableMenus()->attach($availableMenus);
                    }
                } else {
                    $menu = $menuVariant->menu;
                    $menu['name'] = $row['name'];
                    $menu['description'] = $row['description'];
                    $menu['is_enable'] = $row['is_enable'];
                    $menu->update($menuData);

                    $menuVariantData = [
                        'menu_id' => $menuVariant->menu->id,
                        'slug' => $row['menu_variant_slug'],
                        'price' => $row['price'],
                        'tax' => $row['tax'],
                        'discount' => $row['discount'],
                    ];

                    $menuVariant->update($menuVariantData);
                }
            }
        }
    }
}
