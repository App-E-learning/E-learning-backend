<?php

namespace Database\Seeders;

use App\Models\Matiere;
use Illuminate\Database\Seeder;

class MatiereSeeder extends Seeder
{
    public function run(): void
    {
        Matiere::firstOrCreate(['nom' => 'Mathématiques']);
    }
}