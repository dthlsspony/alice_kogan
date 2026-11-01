<?php

namespace Database\Factories;

use App\Models\Book;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Book>
 */
class BookFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(2),
            'author' => fake()->name(),
            'description' => fake()->sentence(),
            'isbn' => fake()->unique()->isbn13(),
        ];
    }
}
