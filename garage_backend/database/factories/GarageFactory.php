<?php

namespace Database\Factories;

use App\Models\Garage;
use Illuminate\Database\Eloquent\Factories\Factory;

class GarageFactory extends Factory
{
    protected $model = Garage::class;

    public function definition(): array
    {
        return [
            'code' => 'C' . str_pad($this->faker->unique()->numberBetween(1, 999), 3, '0', STR_PAD_LEFT),
            'type' => 'mobil',
            'daily_price' => 30000,
            'electricity_price_per_kwh' => null,
            'status' => 'available',
        ];
    }
}
