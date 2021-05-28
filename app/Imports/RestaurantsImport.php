<?php

namespace App\Imports;

use App\Models\Restaurant;
use Maatwebsite\Excel\Concerns\ToModel;
use App\Helpers\StringHelper;
use App\Models\RestaurantBranch;
use App\Models\Township;
use Maatwebsite\Excel\Concerns\Importable;
use Propaganistas\LaravelPhone\PhoneNumber;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithUpserts;
use Maatwebsite\Excel\Concerns\WithValidation;

class RestaurantsImport implements ToModel, WithHeadingRow, WithChunkReading, WithUpserts, WithValidation
{
    use Importable;

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
        $newRestaurant =[
            'id' => isset($row['id']) && $this->transformSlugToId($row['id']),
            'slug' => isset($row['id']) ? $row['id'] : StringHelper::generateUniqueSlug(),
            'name' => $row['name'],
            'is_enable' => $row['is_enable'],
        ];
        if (!isset($row['id'])) {
            $restaurant=Restaurant::create($newRestaurant);
            RestaurantBranch::create([
                'slug' => isset($row['slug']) ? $row['slug'] : StringHelper::generateUniqueSlug(),
                'name' => $row['branch_name'],
                'contact_number' => PhoneNumber::make($row['branch_contact_number'], 'MM'),
                'opening_time' => $row['branch_opening_time'],
                'closing_time' => $row['branch_closing_time'],
                'latitude' => $row['branch_latitude'],
                'longitude' => $row['branch_longitude'],
                'address' => $row['branch_address'],
                'township_id' => Township::where('slug', $row['branch_township_slug'])->value('id'),
                'restaurant_id' => $restaurant->id,
            ]);
        }
        return new Restaurant($newRestaurant);
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
            'name' => 'required|unique:restaurants',
            'is_enable' => 'required|boolean',
            'branch_name' => 'required|string',
            'branch_address' => 'required',
            'branch_contact_number' => 'required|phone:MM',
            'branch_opening_time' => 'required|date_format:H:i',
            'branch_closing_time' => 'required|date_format:H:i',
            'branch_latitude' => 'required|numeric',
            'branch_longitude' => 'required|numeric',
            'branch_township_slug' => 'required|exists:App\Models\Township,slug',
        ];
    }

    public function customValidationMessages()
    {
        return [
            'contact_number.phone' => 'Invalid Phone Number',
        ];
    }

    public function transformSlugToId($value)
    {
        $restaurant = Restaurant::where('slug', $value)->first();

        if (!$restaurant) {
            return null;
        }

        return $restaurant->id;
    }
}
