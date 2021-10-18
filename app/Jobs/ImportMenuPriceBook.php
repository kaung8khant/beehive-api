<?php

namespace App\Jobs;

use App\Models\MenuVariant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Validator;

class ImportMenuPriceBook implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $uniqueKey;
    protected $rows;
    protected $userId;

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
    public function __construct($uniqueKey, $rows, $userId)
    {
        ini_set('max_execution_time', 300);

        $this->uniqueKey = $uniqueKey;
        $this->rows = $rows;
        $this->userId = $userId;
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
        foreach ($this->rows as $row) {
            $rules = [
                'variant_slug' => 'required|exists:App\Models\MenuVariant,slug',
                'price' => 'required|numeric|max:99999999',
            ];

            $validator = Validator::make(
                $row,
                $rules
            );

            if (!$validator->fails()) {
                $menuVariant = MenuVariant::where('slug', $row['variant_slug'])->first();
                $menuVariantData = [
                        'slug' => $row['variant_slug'],
                        'price' => $row['price'],
                    ];

                $menuVariant->update($menuVariantData);
            }
        }
    }
}
