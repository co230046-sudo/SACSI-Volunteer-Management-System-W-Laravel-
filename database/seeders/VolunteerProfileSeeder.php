<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\VolunteerProfile;
use App\Models\Course;
use Illuminate\Support\Str;

class VolunteerProfileSeeder extends Seeder
{
    private $barangays = [
        "Ayala", "Bubuan", "Tumaga", "Camino Nuevo", "Sta. Maria",
        "Divisoria", "Putik", "Canelar", "San Roque", "Tetuan"
    ];

    private $days = [
        "Monday"    => "Do you have a Monday class?",
        "Tuesday"   => "Do you have a Tuesday class?",
        "Wednesday" => "Do you have a Wednesday class?",
        "Thursday"  => "Do you have a Thursday class?",
        "Friday"    => "Do you have a Friday class?",
        "Saturday"  => "Do you have a Saturday class?"
    ];

    /**
     * Generate a GForm-style schedule.
     * Example: [M] 11:00–12:20;[A] 12:30–1:50
     */
    private function generateDaySchedule()
    {
        // 40% chance of no class
        if (rand(0, 9) < 4) {
            return "";
        }

        $blocks = [];
        $blockCount = rand(1, 3); // 1–3 blocks

        for ($i = 0; $i < $blockCount; $i++) {

            $startHour = rand(7, 17); // 7am–5pm
            $endHour   = $startHour + 1;

            $startMin  = rand(0,1) ? "00" : "30";
            $endMin    = $startMin;

            // choose M = Morning, A = Afternoon
            $tag = $startHour < 12 ? "[M]" : "[A]";

            $blocks[] = sprintf(
                "%s %d:%s–%d:%s",
                $tag,
                $startHour, $startMin,
                $endHour,   $endMin
            );
        }

        return implode(";", $blocks);
    }

    /**
     * Generate full weekly schedule in your Import format
     */
    private function generateWeeklyClassSchedule()
    {
        $schedule = [];

        foreach ($this->days as $day => $question) {

            $hasClass = rand(0, 9) < 6;  // 60% chance yes

            $schedule[$question] = $hasClass
                ? "Yes, I have a {$day} class"
                : "No, I do not have a {$day} class";

            $schedule["{$day} Schedule"] = $hasClass
                ? $this->generateDaySchedule()
                : "";
        }

        return $schedule;
    }


    public function run(): void
    {
        $courses = Course::pluck('course_id')->toArray();

        if (empty($courses)) {
            dd("⚠ No courses found. Seed your courses first.");
        }

        for ($i = 1; $i <= 50; $i++) {

            $first = fake()->firstName();
            $middle = fake()->randomLetter();
            $last = fake()->lastName();

            $fullName = "{$first} {$middle}. {$last}";
            $schoolId = "24" . rand(1000, 9999);
            $email    = "co{$schoolId}@adzu.edu.ph";

            // Weekly schedule formatted like Google Forms
            $class = $this->generateWeeklyClassSchedule();

            VolunteerProfile::create([

                'full_name'  => $fullName,

                'course_id'  => fake()->randomElement($courses),
                'year_level' => fake()->numberBetween(1, 4),

                // GForm-like combined class schedule
                'class_schedule' => json_encode($class),

                'barangay' => fake()->randomElement($this->barangays),
                'district' => fake()->numberBetween(1, 2),

                'profile_picture_url'  => 'https://drive.google.com/file/d/' . Str::uuid() . '/view?usp=sharing',
                'profile_picture_path' => null,
            ]);
        }
    }
}
