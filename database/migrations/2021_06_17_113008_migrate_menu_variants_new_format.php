<?php

use App\Models\MenuVariant;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MigrateMenuVariantsNewFormat extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $menuVariants = MenuVariant::pluck('variant', 'id');

        foreach ($menuVariants as $key => $variant) {
            if (count($variant) === 1) {
                $_key = array_keys($variant)[0];
                $_value = $variant[$_key];

                $data = [
                    'name' => $_key == 'name' ? 'default' : $_key,
                    'value' => $_value,
                ];

                MenuVariant::where('id', $key)->update(['variant' => [$data]]);
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
