<?php

namespace Database\Seeders;

use App\Models\DocumentType;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Dom\Document;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
        $this->call(PackageSeeder::class);
        $this->call(DocumentTypesSeeder::class);
    }
}
