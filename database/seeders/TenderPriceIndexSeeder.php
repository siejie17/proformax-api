<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TenderPriceIndexSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            ["year" => 1997, "index" => 265.85],
            ["year" => 1998, "index" => 253.06],
            ["year" => 1999, "index" => 257.68],
            ["year" => 2000, "index" => 258.28],
            ["year" => 2001, "index" => 261.25],
            ["year" => 2002, "index" => 250.65],
            ["year" => 2003, "index" => 246.81],
            ["year" => 2004, "index" => 288.59],
            ["year" => 2005, "index" => 312.56],
            ["year" => 2006, "index" => 412.62],
            ["year" => 2007, "index" => 426.13],
            ["year" => 2008, "index" => 458.76],
            ["year" => 2009, "index" => 477.15],
            ["year" => 2010, "index" => 398.59],
            ["year" => 2011, "index" => 411.36],
            ["year" => 2012, "index" => 429.32],
            ["year" => 2013, "index" => 437.35],
            ["year" => 2014, "index" => 453.99],
            ["year" => 2015, "index" => 469.54],
            ["year" => 2016, "index" => 484.69],
            ["year" => 2017, "index" => 501.62],
            ["year" => 2018, "index" => 523.22],
            ["year" => 2019, "index" => 559.80],
            ["year" => 2020, "index" => 575.02],
            ["year" => 2021, "index" => 600.14],
            ["year" => 2022, "index" => 619.71],
            ["year" => 2023, "index" => 656.13],
            ["year" => 2024, "index" => 667.67],
        ];

        DB::table('tender_price_indices')->insert($data);
    }
}
