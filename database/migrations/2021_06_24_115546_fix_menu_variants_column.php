<?php

use App\Models\Menu;
use Illuminate\Database\Migrations\Migration;

class FixMenuVariantsColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $menus = Menu::pluck('variants', 'id');

        foreach ($menus as $key => $variants) {
            if ($variants) {
                if (isset($variants['name'])) {
                    Menu::where('id', $key)->update(['variants' => [$variants]]);
                }
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
