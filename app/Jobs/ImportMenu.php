<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Validator;
use App\Helpers\StringHelper;
use App\Models\Menu;
use App\Models\Restaurant;
use App\Models\RestaurantCategory;

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
                'restaurant_slug' => 'required|exists:App\Models\Restaurant,slug',
                'restaurant_category_slug' => 'required|exists:App\Models\RestaurantCategory,slug',
            ];

            $validator = Validator::make(
                $row,
                $rules
            );

            if (!$validator->fails()) {
                $menu = null;
                if (isset($row['id'])) {
                    $menu = Menu::where('slug', $row['id'])->first();
                }

                $restaurant = Restaurant::where('slug', $row['restaurant_slug'])->firstOrFail();

                $menuData = [
                    'slug' => StringHelper::generateUniqueSlug(),
                    'name' => $row['name'],
                    'description' => $row['description'],
                    'price' => $row['price'],
                    'tax' => $row['tax'],
                    'discount' => $row['discount'],
                    'is_enable' => $row['is_enable'],
                    'restaurant_id' => $restaurant->id,
                    'restaurant_category_id' => RestaurantCategory::where('slug', $row['restaurant_category_slug'])->value('id'),
                ];

                if (!$menu) {
                    $menu = Menu::create($menuData);
                    foreach ($restaurant->restaurantBranches as $branch) {
                        $availableMenus = Menu::where('slug', $menu->slug)->pluck('id');
                        $branch->availableMenus()->attach($availableMenus);
                    }
                } else {
                    $menuData['slug'] = $menu->slug;
                    $menu->update($menuData);
                    $oldRestaurantId = $menu->restaurant_id;
                    if ($oldRestaurantId !== $restaurant->id) {
                        $oldRestaurant = Restaurant::where('id', $oldRestaurantId)->firstOrFail();
                        foreach ($oldRestaurant->restaurantBranches as $branch) {
                            $branch->availableMenus()->detach($menu->id);
                        }
                        foreach ($restaurant->restaurantBranches as $branch) {
                            $branch->availableMenus()->attach($menu->id);
                        }
                    }
                }
            }
        }
    }
}
