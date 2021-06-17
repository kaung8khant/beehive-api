<?php

use App\Models\Menu;
use Illuminate\Database\Migrations\Migration;

class MigrateMenusTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $menus = Menu::pluck('variants', 'id');

        foreach ($menus as $key => $menu) {
            if ($menu) {
                $values = [];
                foreach ($menu['values'] as $value) {
                    $values[] = [
                        'value' => $value,
                    ];
                }

                $data = [
                    'name' => $menu['name'],
                    'values' => $values,
                    'ui' => 'button',
                ];

                Menu::where('id', $key)->update(['variants' => $data]);
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
