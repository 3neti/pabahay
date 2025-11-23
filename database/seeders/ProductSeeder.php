<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use LBHurtado\Mortgage\Models\Product;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            // Socialized Housing (₱450K - ₱850K)
            [
                'sku' => 'SOC-001',
                'name' => 'Bungalow Basic',
                'brand' => 'PagAsenso Homes',
                'category' => 'Socialized',
                'description' => 'Affordable single-detached house, 2 bedrooms, 1 bathroom, 24 sqm floor area',
                'price' => 850_000,
                'lending_institution' => 'hdmf',
                'base_priority' => 60,
                'commission_rate' => 0.05,
                'is_featured' => true,
                'boost_multiplier' => 1.2,
            ],
            [
                'sku' => 'SOC-002',
                'name' => 'Rowhouse Compact',
                'brand' => 'PagAsenso Homes',
                'category' => 'Socialized',
                'description' => 'Compact rowhouse unit, 2 bedrooms, 1 bathroom, 22 sqm floor area',
                'price' => 950_000,
                'lending_institution' => 'hdmf',
                'base_priority' => 55,
                'commission_rate' => 0.05,
                'is_featured' => false,
                'boost_multiplier' => 1.0,
            ],
            [
                'sku' => 'SOC-003',
                'name' => 'Townhouse Starter',
                'brand' => 'Vista Communities',
                'category' => 'Socialized',
                'description' => 'Two-storey townhouse, 2 bedrooms, 1.5 bathrooms, 30 sqm floor area',
                'price' => 1_150_000,
                'lending_institution' => 'hdmf',
                'base_priority' => 50,
                'commission_rate' => 0.045,
                'is_featured' => false,
                'boost_multiplier' => 1.0,
            ],
            [
                'sku' => 'SOC-004',
                'name' => 'Condo Studio Unit',
                'brand' => 'Urban Living',
                'category' => 'Socialized',
                'description' => 'Studio condominium unit, 1 bedroom, 1 bathroom, 18 sqm floor area',
                'price' => 1_220_000,
                'lending_institution' => 'hdmf',
                'base_priority' => 45,
                'commission_rate' => 0.04,
                'is_featured' => false,
                'boost_multiplier' => 1.0,
            ],

            // Economic Housing (₱850K - ₱2.5M)
            [
                'sku' => 'ECO-001',
                'name' => 'Casa Verde',
                'brand' => 'Camella Homes',
                'category' => 'Economic',
                'description' => 'Single-detached house, 3 bedrooms, 2 bathrooms, 42 sqm floor area, 60 sqm lot',
                'price' => 1_600_000,
                'lending_institution' => 'hdmf',
                'base_priority' => 70,
                'commission_rate' => 0.06,
                'is_featured' => true,
                'boost_multiplier' => 1.3,
            ],
            [
                'sku' => 'ECO-002',
                'name' => 'Townhouse Deluxe',
                'brand' => 'Camella Homes',
                'category' => 'Economic',
                'description' => 'Two-storey townhouse, 3 bedrooms, 2 bathrooms, 48 sqm floor area',
                'price' => 1_650_000,
                'lending_institution' => 'hdmf',
                'base_priority' => 65,
                'commission_rate' => 0.06,
                'is_featured' => false,
                'boost_multiplier' => 1.0,
            ],
            [
                'sku' => 'ECO-003',
                'name' => 'Heritage Plus',
                'brand' => 'Crown Asia',
                'category' => 'Economic',
                'description' => 'Single-attached house, 3 bedrooms, 2 bathrooms, 55 sqm floor area, 80 sqm lot',
                'price' => 1_850_000,
                'lending_institution' => 'rcbc',
                'base_priority' => 60,
                'commission_rate' => 0.065,
                'is_featured' => true,
                'boost_multiplier' => 1.15,
            ],
            [
                'sku' => 'ECO-004',
                'name' => 'Metro Heights',
                'brand' => 'DMCI Homes',
                'category' => 'Economic',
                'description' => 'Condo 2-bedroom unit, 2 bedrooms, 1 bathroom, 36 sqm floor area',
                'price' => 2_500_000,
                'lending_institution' => 'rcbc',
                'base_priority' => 55,
                'commission_rate' => 0.055,
                'is_featured' => false,
                'boost_multiplier' => 1.0,
            ],
            [
                'sku' => 'ECO-005',
                'name' => 'Belmont Residences',
                'brand' => 'Sta. Lucia Land',
                'category' => 'Economic',
                'description' => 'Two-storey single-detached, 4 bedrooms, 2 bathrooms, 70 sqm floor area, 100 sqm lot',
                'price' => 2_900_000,
                'lending_institution' => 'cbc',
                'base_priority' => 50,
                'commission_rate' => 0.07,
                'is_featured' => false,
                'boost_multiplier' => 1.0,
            ],

            // Open Market (₱2.5M+)
            [
                'sku' => 'OPEN-001',
                'name' => 'Vista Grande',
                'brand' => 'Ayala Land',
                'category' => 'Open Market',
                'description' => 'Premium single-detached, 4 bedrooms, 3 bathrooms, 120 sqm floor area, 180 sqm lot',
                'price' => 3_900_000,
                'lending_institution' => 'rcbc',
                'base_priority' => 75,
                'commission_rate' => 0.08,
                'is_featured' => true,
                'boost_multiplier' => 1.4,
            ],
            [
                'sku' => 'OPEN-002',
                'name' => 'The Palms',
                'brand' => 'Megaworld',
                'category' => 'Open Market',
                'description' => 'Luxury condo 3-bedroom unit, 3 bedrooms, 2 bathrooms, 85 sqm floor area',
                'price' => 4_600_000,
                'lending_institution' => 'rcbc',
                'base_priority' => 70,
                'commission_rate' => 0.075,
                'is_featured' => true,
                'boost_multiplier' => 1.25,
            ],
            [
                'sku' => 'OPEN-003',
                'name' => 'Garden Estates',
                'brand' => 'Brittany Corporation',
                'category' => 'Open Market',
                'description' => 'Executive house and lot, 5 bedrooms, 4 bathrooms, 180 sqm floor area, 250 sqm lot',
                'price' => 6_200_000,
                'lending_institution' => 'cbc',
                'base_priority' => 65,
                'commission_rate' => 0.085,
                'is_featured' => false,
                'boost_multiplier' => 1.0,
            ],
            [
                'sku' => 'OPEN-004',
                'name' => 'Prestige Park',
                'brand' => 'Crown Asia',
                'category' => 'Open Market',
                'description' => 'Premium two-storey, 4 bedrooms, 3.5 bathrooms, 150 sqm floor area, 200 sqm lot',
                'price' => 6_500_000,
                'lending_institution' => 'cbc',
                'base_priority' => 60,
                'commission_rate' => 0.09,
                'is_featured' => false,
                'boost_multiplier' => 1.0,
            ],
            [
                'sku' => 'OPEN-005',
                'name' => 'Horizons Premium',
                'brand' => 'Rockwell Land',
                'category' => 'Open Market',
                'description' => 'Penthouse unit, 4 bedrooms, 3 bathrooms, 150 sqm floor area with balcony',
                'price' => 8_900_000,
                'lending_institution' => 'rcbc',
                'base_priority' => 80,
                'commission_rate' => 0.10,
                'is_featured' => true,
                'boost_multiplier' => 1.5,
            ],
        ];

        foreach ($products as $productData) {
            Product::updateOrCreate(
                ['sku' => $productData['sku']],
                $productData
            );
        }
    }
}
