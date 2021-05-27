<?php

namespace App\Imports;

use App\Helpers\StringHelper;
use App\Models\Menu;
use App\Models\Restaurant;
use App\Models\RestaurantCategory;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithUpserts;
use Maatwebsite\Excel\Concerns\WithValidation;

class MenusImport implements ToModel, WithHeadingRow, WithChunkReading, WithUpserts, WithValidation
{
    public function __construct()
    {
        ini_set('memory_limit', '256M');
    }

    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        $restaurantId = Restaurant::where('slug', $row['restaurant_slug'])->value('id');
        $restaurant = Restaurant::where('slug', $row['restaurant_slug'])->firstOrFail();
        $newMenu =[
            'id' =>  isset($row['id']) && $this->transformSlugToId($row['id'])->id,
            'slug' => isset($row['id']) ? $row['id'] : StringHelper::generateUniqueSlug(),
            'name' => $row['name'],
            'description' => $row['description'],
            'price' => $row['price'],
            'tax' => $row['tax'],
            'discount' => $row['discount'],
            'is_enable' => $row['is_enable'],
            'restaurant_id' => $restaurantId,
            'restaurant_category_id' => RestaurantCategory::where('slug', $row['restaurant_category_slug'])->value('id'),
        ];
        if (!isset($row['id'])) {
            $menu=Menu::create($newMenu);
            foreach ($restaurant->restaurantBranches as $branch) {
                $availableMenus = Menu::where('slug', $menu->slug)->pluck('id');
                $branch->availableMenus()->attach($availableMenus);
            }
        } else {
            $exitingMenu = $this->transformSlugToId($row['id']);
            $oldRestaurantId = $exitingMenu->restaurant_id;
            if ($oldRestaurantId !== $restaurantId) {
                $oldRestaurant = Restaurant::where('id', $oldRestaurantId)->firstOrFail();
                foreach ($oldRestaurant->restaurantBranches as $branch) {
                    $branch->availableMenus()->detach($exitingMenu->id);
                }
                foreach ($restaurant->restaurantBranches as $branch) {
                    $branch->availableMenus()->attach($exitingMenu->id);
                }
            }
        }
        return new Menu($newMenu);
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    /**
     * @return string|array
     */
    public function uniqueBy()
    {
        return ['slug','id'];
    }


    public function rules(): array
    {
        return [
            'name' => 'required',
            'description' => 'nullable',
            'price' => 'required|numeric',
            'tax' => 'required|numeric',
            'discount' => 'required|numeric',
            'is_enable' => 'required|boolean',
            'restaurant_slug' => 'required|exists:App\Models\Restaurant,slug',
            'restaurant_category_slug' => 'required|exists:App\Models\RestaurantCategory,slug',
        ];
    }

    public function transformSlugToId($value)
    {
        $menu = Menu::where('slug', $value)->first();

        if (!$menu) {
            return null;
        }

        return $menu;
    }
}
