<?php

namespace Database\Seeders;

use App\Models\CompanyData;
use Illuminate\Database\Seeder;

class CompanyDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Seed a single CompanyData entry named "expense" with an empty JSON payload
        CompanyData::updateOrCreate(
            ['name' => 'expense'],
            ['data' => [], 'user_id' => 1],
        );
    }
}
