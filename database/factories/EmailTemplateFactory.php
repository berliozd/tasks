<?php

namespace Database\Factories;

use App\Models\Directory;
use App\Models\EmailTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EmailTemplate>
 */
class EmailTemplateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence(3),
            'subject' => $this->faker->sentence(5),
            'language' => $this->faker->randomElement(array_keys(EmailTemplate::LANGUAGES)),
            'body' => $this->faker->paragraphs(2, true),
            'directory_id' => Directory::factory(),
        ];
    }
}
