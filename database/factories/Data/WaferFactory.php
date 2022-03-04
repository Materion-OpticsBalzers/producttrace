<?php

namespace Database\Factories\Data;

use App\Models\Data\Wafer;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Data\Wafer>
 */
class WaferFactory extends Factory
{
    protected $model = Wafer::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'id' => $this->faker->randomNumber(6, true),
            'raw_lot' => 0,
            'lot_date' => Carbon::now(),
            'rejected' => 0
        ];
    }
}
