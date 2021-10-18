<?php

namespace App\Exports;

use App\Models\MenuVariant;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\FromCollection;

class MenuPriceBookExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths
{
    public function stringifyVariant($variants)
    {
        $implode = array();
        foreach ($variants as $variant) {
            $implode[] = implode(':', $variant);
        }
        return implode(' / ', $implode);
    }

    public function collection()
    {
        $menuVariants = MenuVariant::with("menu")->get()->groupBy('menu_id')->all();
        $exportData = [];
        foreach ($menuVariants as $group) {
            foreach ($group as $variant) {
                $data = [
                'id' => $variant->menu->slug,
                'name' => $variant->menu->name,
                'variant_slug' =>  $variant->slug,
                'variant' =>  $this->stringifyVariant($variant->variant),
                'price' =>$variant->price ? $variant->price : '0',
                'is_enable' =>  $variant->is_enable ? '1' : '0',
            ];
                array_push($exportData, $data);
            }
        };

        return collect([
            $exportData
        ]);
    }


    public function headings(): array
    {
        return [
            'id',
            'name',
            'variant_slug',
            'variant',
            'price',
            'is_enable',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold text.
            1 => ['font' => ['bold' => true]],
            'A' => ['alignment' => ['horizontal' => 'center']],
            'B' => ['alignment' => ['horizontal' => 'center']],
            'C' => ['alignment' => ['horizontal' => 'center']],
            'D' => ['alignment' => ['horizontal' => 'center']],
            'E' => ['alignment' => ['horizontal' => 'center']],
            'F' => ['alignment' => ['horizontal' => 'center']],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15,
            'B' => 30,
            'C' => 20,
            'D' => 30,
            'E' => 20,
            'F' => 20,
        ];
    }
}
