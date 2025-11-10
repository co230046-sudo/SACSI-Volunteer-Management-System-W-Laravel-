<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CoursesTableSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $courses = [
            // SITEAO
            ['college' => 'SITEAO', 'course_name' => 'BS Biology'],
            ['college' => 'SITEAO', 'course_name' => 'BS Biomedical Engineering'],
            ['college' => 'SITEAO', 'course_name' => 'BS Civil Engineering'],
            ['college' => 'SITEAO', 'course_name' => 'BS Computer Science'],
            ['college' => 'SITEAO', 'course_name' => 'BS Electronics Engineering'],
            ['college' => 'SITEAO', 'course_name' => 'BS Information Technology'],
            ['college' => 'SITEAO', 'course_name' => 'BS Mathematics'],
            ['college' => 'SITEAO', 'course_name' => 'BS New Media and Computer Animation'],
            ['college' => 'SITEAO', 'course_name' => 'Associate in Electronics Engineering Technology'],

            // MAO
            ['college' => 'MAO', 'course_name' => 'BS Accountancy'],
            ['college' => 'MAO', 'course_name' => 'BS Business Administration Major in Financial Management'],
            ['college' => 'MAO', 'course_name' => 'BS Business Administration Major in Marketing Management'],
            ['college' => 'MAO', 'course_name' => 'BS Entrepreneurship'],
            ['college' => 'MAO', 'course_name' => 'BS Legal Management'],
            ['college' => 'MAO', 'course_name' => 'BS Management Accounting'],
            ['college' => 'MAO', 'course_name' => 'BS Office Administration'],

            // NAO
            ['college' => 'NAO', 'course_name' => 'BS Nursing'],

            // SLA
            ['college' => 'SLA', 'course_name' => 'BA Communication'],
            ['college' => 'SLA', 'course_name' => 'BA English Language Studies'],
            ['college' => 'SLA', 'course_name' => 'BA Philosophy'],
            ['college' => 'SLA', 'course_name' => 'BS Psychology'],

            // SOM
            ['college' => 'SOM', 'course_name' => 'Bachelor of Early Childhood Education'],
            ['college' => 'SOM', 'course_name' => 'Bachelor of Elementary Education'],
            ['college' => 'SOM', 'course_name' => 'Bachelor of Physical Education'],
            ['college' => 'SOM', 'course_name' => 'Bachelor of Secondary Education Major in English'],
            ['college' => 'SOM', 'course_name' => 'Bachelor of Secondary Education Major in Filipino'],
            ['college' => 'SOM', 'course_name' => 'Bachelor of Secondary Education Major in Mathematics'],
            ['college' => 'SOM', 'course_name' => 'Bachelor of Secondary Education Major in Science'],
            ['college' => 'SOM', 'course_name' => 'Bachelor of Secondary Education Major in Social Studies'],
            ['college' => 'SOM', 'course_name' => 'Certificate in Professional Education'],
        ];

        foreach ($courses as $course) {
            DB::table('courses')->updateOrInsert(
                ['course_name' => $course['course_name']],
                array_merge($course, ['created_at' => $now, 'updated_at' => $now])
            );
        }
    }
}
