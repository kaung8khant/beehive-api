<?php

namespace App\Jobs;

use App\Helpers\StringHelper;
use App\Models\Shop;
use App\Models\Township;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Propaganistas\LaravelPhone\PhoneNumber;

class ImportShop implements ShouldQueue, ShouldBeUnique
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
            if (isset($row['contact_number'])) {
                $row['contact_number'] = str_replace([' ', '-'], '', $row['contact_number']);
            }

            $rules = [
                'name' => ['required', 'unique:shops'],
                'is_enable' => ['required', 'boolean'],
                'is_official' => ['required', 'boolean'],
                'address' => ['nullable'],
                'contact_number' => ['required', 'phone:MM'],
                'opening_time' => ['required', 'date_format:H:i'],
                'closing_time' => ['required', 'date_format:H:i'],
                'latitude' => ['required', 'numeric'],
                'longitude' => ['required', 'numeric'],
                'township_slug' => ['nullable', 'exists:App\Models\Township,slug'],
            ];

            $shop = null;

            if (isset($row['id'])) {
                $shop = Shop::where('slug', $row['id'])->first();
                $rules['name'][1] = Rule::unique('shops')->ignore($shop->id);
            }

            $validator = Validator::make(
                $row,
                $rules,
                [
                    'contact_number.phone' => 'Invalid phone number.',
                ]
            );

            if (!$validator->fails()) {
                $shopData = [
                    'slug' => StringHelper::generateUniqueSlug(),
                    'name' => $row['name'],
                    'contact_number' => PhoneNumber::make($row['contact_number'], 'MM'),
                    'opening_time' => $row['opening_time'],
                    'closing_time' => $row['closing_time'],
                    'latitude' => $row['latitude'],
                    'longitude' => $row['longitude'],
                    'address' => $row['address'],
                    'is_enable' => $row['is_enable'],
                    'is_official' => $row['is_official'],
                    'township_id' => Township::where('slug', $row['township_slug'])->value('id'),
                ];

                if (!$shop) {
                    try {
                        $shop = Shop::create($shopData);
                    } catch (QueryException $e) {
                        $shop = Shop::where('name', $row['name'])->first();
                        $shopData['slug'] = $shop->slug;
                        $shop->update($shopData);
                    }
                } else {
                    $shopData['slug'] = $shop->slug;
                    $shop->update($shopData);
                }
            }
        }
    }
}
