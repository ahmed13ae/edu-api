<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;


class FieldSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('fields')->insert([
            ['name' => 'Programming'],
            ['name' => 'Graphic Design'],
            ['name' => 'Marketing'],
            ['name' => 'Business'],
            ['name' => 'Data Science'],
            ['name' => 'AI & Machine Learning'],
            ['name' => 'Languages'],
            ['name' => 'Cybersecurity'],
        ]);
    }
}
