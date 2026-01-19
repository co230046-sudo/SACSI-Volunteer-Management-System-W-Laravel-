<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class EventTypesTableSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $types = [
            ['type_key' => 'cleanup', 'label' => 'Cleanup Drive'],
            ['type_key' => 'seminar', 'label' => 'Seminar'],
            ['type_key' => 'fundraise', 'label' => 'Fundraise Activity'],

            ['type_key' => 'workshop', 'label' => 'Workshop / Training'],
            ['type_key' => 'tree_planting', 'label' => 'Tree Planting'],
            ['type_key' => 'medical_mission', 'label' => 'Medical Mission'],
            ['type_key' => 'blood_drive', 'label' => 'Blood Donation Drive'],
            ['type_key' => 'outreach', 'label' => 'Community Outreach'],
            ['type_key' => 'orientation', 'label' => 'Volunteer Orientation'],
            ['type_key' => 'disaster_relief', 'label' => 'Disaster Relief Operation'],
            ['type_key' => 'distribution', 'label' => 'Goods Distribution'],
            ['type_key' => 'awareness_campaign', 'label' => 'Awareness Campaign'],
            ['type_key' => 'sports_event', 'label' => 'Sports Event'],
            ['type_key' => 'food_drive', 'label' => 'Food Drive'],
            ['type_key' => 'reforestation', 'label' => 'Reforestation Activity'],
        ];

        foreach ($types as $t) {
            DB::table('event_types')->insert([
                'type_key' => $t['type_key'],
                'label' => $t['label'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
