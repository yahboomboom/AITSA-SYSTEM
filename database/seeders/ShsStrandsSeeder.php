<?php

namespace Database\Seeders;

use App\Models\ShsStrand;
use Illuminate\Database\Seeder;

class ShsStrandsSeeder extends Seeder
{
    public function run(): void
    {
        $strands = [
            [
                'code' => 'HUMSS-ARTS', 
                'name' => 'Arts, Social Sciences, and Humanities'
            ],
            [
                'code' => 'ABM-ENTREP', 
                'name' => 'Business and Entrepreneurship'
            ],
            [
                'code' => 'TECHPRO-TVL', 
                'name' => 'TechPro (Technical-Professional) Electives'
            ],
        ];

        foreach ($strands as $strand) {
            ShsStrand::updateOrCreate(['code' => $strand['code']], $strand);
        }
    }
}