<?php

namespace Database\Factories;

use App\Models\Quote;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Quote>
 */
class QuoteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'book_id' => BookFactory::new(),
            'text' => fake()->paragraph(),
            'page_number' => (string) fake()->numberBetween(1, 400),
        ];
    }
}
