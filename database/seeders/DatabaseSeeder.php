<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            EpsSeeder::class,
            AfpSeeder::class,
            ArpSeeder::class,
            CcfSeeder::class,
            PaymentOperatorSeeder::class,
            ClientTypeSeeder::class,
            ContributorTypeSeeder::class,
            NoveltyTypeSeeder::class,
            AccountingRegistrySeeder::class,
            UserSeeder::class,
            AffiliateWithBeneficiarySeeder::class,
        ]);
    }
}
