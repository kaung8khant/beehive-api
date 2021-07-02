<?php

namespace App\Console\Commands;

use App\Helpers\StringHelper;
use App\Models\Menu;
use App\Models\MenuVariation;
use App\Models\MenuVariationValue;
use Illuminate\Console\Command;

class VariationFix extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:variation';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix variation';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $menus = Menu::with(['menuVariants' => function ($query) {
            $query->orderBy('price', 'asc');
        }])
            ->orderBy('id', 'asc')
            ->get();

        foreach ($menus as $menu) {
            $menuVariants = $menu->menuVariants->filter(function ($variant) {
                return count($variant->variant) === 1;
            });

            if (count($menuVariants) > 0) {
                try {
                    $menu->update([
                        'price' => $menuVariants[0]->price,
                        'tax' => $menuVariants[0]->tax,
                        'discount' => $menuVariants[0]->discount,
                    ]);
                } catch (\Exception $e) {
                    Log::critical('This menu failed at fixing variation ----- ' . $menu);
                }

                if ($menuVariants[0]->variant[0]['name'] !== 'default') {
                    MenuVariation::where('menu_id', $menu->id)->delete();

                    $menuVariation = MenuVariation::create([
                        'slug' => StringHelper::generateUniqueSlug(),
                        'name' => $menuVariants[0]->variant[0]['name'],
                        'menu_id' => $menu->id,
                    ]);

                    foreach ($menuVariants as $variant) {
                        MenuVariationValue::create([
                            'slug' => StringHelper::generateUniqueSlug(),
                            'value' => $variant->variant[0]['value'],
                            'price' => $variant->price - $menu->price,
                            'menu_variation_id' => $menuVariation->id,
                        ]);
                    }
                }
            }
        }

        throw new \Exception('invoking variation fix scheduler!');
    }
}
