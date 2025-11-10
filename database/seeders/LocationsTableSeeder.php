<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LocationsTableSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        // Map district names to numbers
        $districtMap = [
            'West' => 1,
            'East' => 2,
            'North' => 3,
            'South' => 4,
            'Poblacion' => 5
        ];

        $locations = [
            // **West District**
            ['district' => 'West', 'barangay' => 'Ayala', 'created_at' => $now, 'updated_at' => $now],
            ['district' => 'West', 'barangay' => 'Bagong Calarian', 'created_at' => $now, 'updated_at' => $now],
            ['district' => 'West', 'barangay' => 'Bunguiao', 'created_at' => $now, 'updated_at' => $now],
            ['district' => 'West', 'barangay' => 'Cabaluay', 'created_at' => $now, 'updated_at' => $now],
            ['district' => 'West', 'barangay' => 'Cabatangan', 'created_at' => $now, 'updated_at' => $now],
            ['district' => 'West', 'barangay' => 'Curuan', 'created_at' => $now, 'updated_at' => $now],
            ['district' => 'West', 'barangay' => 'Divisoria', 'created_at' => $now, 'updated_at' => $now],
            ['district' => 'West', 'barangay' => 'Dita', 'created_at' => $now, 'updated_at' => $now],
            ['district' => 'West', 'barangay' => 'Guiwan', 'created_at' => $now, 'updated_at' => $now],
            ['district' => 'West', 'barangay' => 'Latuan', 'created_at' => $now, 'updated_at' => $now],
            ['district' => 'West', 'barangay' => 'Manicahan', 'created_at' => $now, 'updated_at' => $now],
            ['district' => 'West', 'barangay' => 'Mercedes', 'created_at' => $now, 'updated_at' => $now],
            ['district' => 'West', 'barangay' => 'Putik', 'created_at' => $now, 'updated_at' => $now],
            ['district' => 'West', 'barangay' => 'Recodo', 'created_at' => $now, 'updated_at' => $now],
            ['district' => 'West', 'barangay' => 'Rizal', 'created_at' => $now, 'updated_at' => $now],
            ['district' => 'West', 'barangay' => 'San Jose Cawa-Cawa', 'created_at' => $now, 'updated_at' => $now],
            ['district' => 'West', 'barangay' => 'San Roque', 'created_at' => $now, 'updated_at' => $now],
            ['district' => 'West', 'barangay' => 'Santa Barbara', 'created_at' => $now, 'updated_at' => $now],
            ['district' => 'West', 'barangay' => 'Santa Catalina', 'created_at' => $now, 'updated_at' => $now],
            ['district' => 'West', 'barangay' => 'Tetuan', 'created_at' => $now, 'updated_at' => $now],

            // **East District**
            ['district' => 'East', 'barangay' => 'Boalan', 'created_at' => $now, 'updated_at' => $now],
            ['district' => 'East', 'barangay' => 'Bolong', 'created_at' => $now, 'updated_at' => $now],
            ['district' => 'East', 'barangay' => 'Bugsukan', 'created_at' => $now, 'updated_at' => $now],
            ['district' => 'East', 'barangay' => 'Canelar', 'created_at' => $now, 'updated_at' => $now],
            ['district' => 'East', 'barangay' => 'Lamisahan', 'created_at' => $now, 'updated_at' => $now],
            ['district' => 'East', 'barangay' => 'Lantawan', 'created_at' => $now, 'updated_at' => $now],
            ['district' => 'East', 'barangay' => 'La Paz', 'created_at' => $now, 'updated_at' => $now],
            ['district' => 'East', 'barangay' => 'Mampang', 'created_at' => $now, 'updated_at' => $now],
            ['district' => 'East', 'barangay' => 'Mabuhay', 'created_at' => $now, 'updated_at' => $now],
            ['district' => 'East', 'barangay' => 'Mahayag', 'created_at' => $now, 'updated_at' => $now],
            ['district' => 'East', 'barangay' => 'San Jose Gusu', 'created_at' => $now, 'updated_at' => $now],
            ['district' => 'East', 'barangay' => 'Talon-Talon', 'created_at' => $now, 'updated_at' => $now],
            ['district' => 'East', 'barangay' => 'Tugbungan', 'created_at' => $now, 'updated_at' => $now],

            // **North District**
            ['district' => 'North', 'barangay' => 'Cabaluay Norte', 'created_at' => $now, 'updated_at' => $now],
            ['district' => 'North', 'barangay' => 'Calarian Norte', 'created_at' => $now, 'updated_at' => $now],
            ['district' => 'North', 'barangay' => 'Curuan Norte', 'created_at' => $now, 'updated_at' => $now],
            ['district' => 'North', 'barangay' => 'Guisao Norte', 'created_at' => $now, 'updated_at' => $now],
            ['district' => 'North', 'barangay' => 'Lunsay Norte', 'created_at' => $now, 'updated_at' => $now],
            ['district' => 'North', 'barangay' => 'Mampang Norte', 'created_at' => $now, 'updated_at' => $now],
            ['district' => 'North', 'barangay' => 'Mariki', 'created_at' => $now, 'updated_at' => $now],
            ['district' => 'North', 'barangay' => 'Pamucutan', 'created_at' => $now, 'updated_at' => $now],
            ['district' => 'North', 'barangay' => 'Pasonanca', 'created_at' => $now, 'updated_at' => $now],
            ['district' => 'North', 'barangay' => 'Putik Norte', 'created_at' => $now, 'updated_at' => $now],
            ['district' => 'North', 'barangay' => 'Suterville', 'created_at' => $now, 'updated_at' => $now],

            // **South District**
            ['district' => 'South', 'barangay' => 'Arena Blanco', 'created_at' => $now, 'updated_at' => $now],
            ['district' => 'South', 'barangay' => 'Cabaluay Sur', 'created_at' => $now, 'updated_at' => $now],
            ['district' => 'South', 'barangay' => 'Canelar Sur', 'created_at' => $now, 'updated_at' => $now],
            ['district' => 'South', 'barangay' => 'Cawit', 'created_at' => $now, 'updated_at' => $now],
            ['district' => 'South', 'barangay' => 'Dita Sur', 'created_at' => $now, 'updated_at' => $now],
            ['district' => 'South', 'barangay' => 'Divisoria Sur', 'created_at' => $now, 'updated_at' => $now],
            ['district' => 'South', 'barangay' => 'Guiwan Sur', 'created_at' => $now, 'updated_at' => $now],
            ['district' => 'South', 'barangay' => 'Labuan', 'created_at' => $now, 'updated_at' => $now],
            ['district' => 'South', 'barangay' => 'Manicahan Sur', 'created_at' => $now, 'updated_at' => $now],
            ['district' => 'South', 'barangay' => 'Mercedes Sur', 'created_at' => $now, 'updated_at' => $now],
            ['district' => 'South', 'barangay' => 'Putik Sur', 'created_at' => $now, 'updated_at' => $now],
            ['district' => 'South', 'barangay' => 'Recodo Sur', 'created_at' => $now, 'updated_at' => $now],
            ['district' => 'South', 'barangay' => 'Rizal Sur', 'created_at' => $now, 'updated_at' => $now],
            ['district' => 'South', 'barangay' => 'San Jose Sur', 'created_at' => $now, 'updated_at' => $now],
            ['district' => 'South', 'barangay' => 'San Roque Sur', 'created_at' => $now, 'updated_at' => $now],
            ['district' => 'South', 'barangay' => 'Santa Barbara Sur', 'created_at' => $now, 'updated_at' => $now],
            ['district' => 'South', 'barangay' => 'Santa Catalina Sur', 'created_at' => $now, 'updated_at' => $now],
            ['district' => 'South', 'barangay' => 'Tetuan Sur', 'created_at' => $now, 'updated_at' => $now],

            // **Poblacion / Other Barangays**
            ['district' => 'Poblacion', 'barangay' => 'Curuan Proper', 'created_at' => $now, 'updated_at' => $now],
            ['district' => 'Poblacion', 'barangay' => 'Santa Maria', 'created_at' => $now, 'updated_at' => $now],
            ['district' => 'Poblacion', 'barangay' => 'Mampang Proper', 'created_at' => $now, 'updated_at' => $now],
            ['district' => 'Poblacion', 'barangay' => 'Bugo', 'created_at' => $now, 'updated_at' => $now],
            ['district' => 'Poblacion', 'barangay' => 'Lapakan', 'created_at' => $now, 'updated_at' => $now],
            ['district' => 'Poblacion', 'barangay' => 'Vitali', 'created_at' => $now, 'updated_at' => $now],
            ['district' => 'Poblacion', 'barangay' => 'Guisao Proper', 'created_at' => $now, 'updated_at' => $now],
            ['district' => 'Poblacion', 'barangay' => 'Bunguiao Proper', 'created_at' => $now, 'updated_at' => $now],
            ['district' => 'Poblacion', 'barangay' => 'Curuan East', 'created_at' => $now, 'updated_at' => $now],
            ['district' => 'Poblacion', 'barangay' => 'Cabaluay Proper', 'created_at' => $now, 'updated_at' => $now],
            ['district' => 'Poblacion', 'barangay' => 'Divisoria Proper', 'created_at' => $now, 'updated_at' => $now],
            ['district' => 'Poblacion', 'barangay' => 'Canelar Proper', 'created_at' => $now, 'updated_at' => $now],
            ['district' => 'Poblacion', 'barangay' => 'La Paz Proper', 'created_at' => $now, 'updated_at' => $now],
            ['district' => 'Poblacion', 'barangay' => 'Tugbungan Proper', 'created_at' => $now, 'updated_at' => $now],
            ['district' => 'Poblacion', 'barangay' => 'Sta. Maria East', 'created_at' => $now, 'updated_at' => $now],
            ['district' => 'Poblacion', 'barangay' => 'Sta. Maria West', 'created_at' => $now, 'updated_at' => $now],
            ['district' => 'Poblacion', 'barangay' => 'Arena Blanco East', 'created_at' => $now, 'updated_at' => $now],
            ['district' => 'Poblacion', 'barangay' => 'Arena Blanco West', 'created_at' => $now, 'updated_at' => $now],
            ['district' => 'Poblacion', 'barangay' => 'Labuan East', 'created_at' => $now, 'updated_at' => $now],
            ['district' => 'Poblacion', 'barangay' => 'Labuan West', 'created_at' => $now, 'updated_at' => $now],
            ['district' => 'Poblacion', 'barangay' => 'Lunzuran', 'created_at' => $now, 'updated_at' => $now],
            ['district' => 'Poblacion', 'barangay' => 'Manicahan East', 'created_at' => $now, 'updated_at' => $now],
            ['district' => 'Poblacion', 'barangay' => 'Manicahan West', 'created_at' => $now, 'updated_at' => $now],
            ['district' => 'Poblacion', 'barangay' => 'Putik East', 'created_at' => $now, 'updated_at' => $now],
            ['district' => 'Poblacion', 'barangay' => 'Putik West', 'created_at' => $now, 'updated_at' => $now],
            ['district' => 'Poblacion', 'barangay' => 'Rizal East', 'created_at' => $now, 'updated_at' => $now],
            ['district' => 'Poblacion', 'barangay' => 'Rizal West', 'created_at' => $now, 'updated_at' => $now],
            ['district' => 'Poblacion', 'barangay' => 'San Jose East', 'created_at' => $now, 'updated_at' => $now],
            ['district' => 'Poblacion', 'barangay' => 'San Jose West', 'created_at' => $now, 'updated_at' => $now],
            ['district' => 'Poblacion', 'barangay' => 'San Roque East', 'created_at' => $now, 'updated_at' => $now],
            ['district' => 'Poblacion', 'barangay' => 'San Roque West', 'created_at' => $now, 'updated_at' => $now],
            ['district' => 'Poblacion', 'barangay' => 'Santa Barbara East', 'created_at' => $now, 'updated_at' => $now],
            ['district' => 'Poblacion', 'barangay' => 'Santa Barbara West', 'created_at' => $now, 'updated_at' => $now],
            ['district' => 'Poblacion', 'barangay' => 'Santa Catalina East', 'created_at' => $now, 'updated_at' => $now],
            ['district' => 'Poblacion', 'barangay' => 'Santa Catalina West', 'created_at' => $now, 'updated_at' => $now],
            ['district' => 'Poblacion', 'barangay' => 'Tetuan East', 'created_at' => $now, 'updated_at' => $now],
            ['district' => 'Poblacion', 'barangay' => 'Tetuan West', 'created_at' => $now, 'updated_at' => $now],
        ];

        
        foreach ($locations as $loc) {
            DB::table('locations')->updateOrInsert(
                ['barangay' => $loc['barangay']],
                [
                    'district' => $districtMap[$loc['district']], // numeric district
                    'barangay' => $loc['barangay'],
                    'created_at' => $now,
                    'updated_at' => $now
                ]
            );
        }
    }
}
