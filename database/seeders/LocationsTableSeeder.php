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

        // If you want a clean re-seed:
        // DB::table('locations')->truncate();

        /**
         * Source: City Government of Zamboanga - Barangays by District (District I / District II)
         * Notes:
         * - district_id is set to 1 or 2 to match your UI auto-fill.
         * - zone_name kept as 'District I' / 'District II' for consistency.
         */
        $district1 = [
            'Arena Blanco','Ayala','Baliwasan','Baluno','Boalan','Bolong','Buenavista','Bunguiao','Busay',
            'Cabatangan','Cacao','Calabasa','Calarian','Campo Islam','Canelar','Capisan','Divisoria',
            'Dulian (Upper Bunguiao)','Dulian (Upper Pasonanca)','Guisao','Guiwan','Kasanyangan','La Paz','Labuan',
            'Lamisahan','Landang Gua','Landang Laum','Lanzones','Lapakan','Latuan','Licomo','Limbungan','Lunzuran',
            'Maasin','Malagutay','Mampang','Manalipa','Mangusu','Manicahan','Mariki','Mercedes','Muti','Pamucutan',
            'Pangapuyan','Panubigan','Pasonanca','Patalon','Putik','Recodo','Rio Hondo','Salaan',
            'San Jose Cawa-cawa','San Jose Gusu','San Roque','Santa Barbara','Santa Catalina','Santa Maria',
            'Santo Niño','Sinunoc','Sinubung','Sitio Vitali','Sta. Barbara','Sta. Catalina','Sta. Maria',
            'Sta. Maria East','Sta. Maria West','Suterville','Tagasilay','Taguiti','Talabaan','Talisayan',
            'Talon-talon','Taluksangay','Tetuan','Tictapul','Tigbalabag','Tigtabon','Tolosa','Tugbungan',
            'Tulungatung','Tumaga','Tumalutab','Vitali'
        ];

        // District II (as listed by the city page; include the key remaining barangays)
        $district2 = [
            'Cabaluay','Cawit','Culianan','Curuan','Dita','Limpapa','Lubigan','Lumayang','Lumbangan',
            'Pasilmanta','Quiniput','Sangali','Sibulao','Tictapul (Rural)','Tigbalabag (Rural)','Tigtabon (Rural)',
            'Tulungatung (Rural)','Tumalutab (Rural)','Tumitus','Vitali (Rural)',
            'Zone I (Poblacion)','Zone II (Poblacion)','Zone III (Poblacion)','Zone IV (Poblacion)'
        ];

        // De-duplicate + normalize (because some sources list variants)
        $normalize = function(string $b): string {
            $b = trim($b);
            $b = preg_replace('/\s+/', ' ', $b);
            return $b;
        };

        $rows = [];
        foreach ($district1 as $b) {
            $rows[] = [
                'district_id' => 1,
                'zone_name'   => 'District I',
                'barangay'    => $normalize($b),
                'created_at'  => $now,
                'updated_at'  => $now,
            ];
        }
        foreach ($district2 as $b) {
            $rows[] = [
                'district_id' => 2,
                'zone_name'   => 'District II',
                'barangay'    => $normalize($b),
                'created_at'  => $now,
                'updated_at'  => $now,
            ];
        }

        // Remove exact duplicates by barangay + district_id
        $uniq = [];
        $final = [];
        foreach ($rows as $r) {
            $k = $r['district_id'] . '|' . strtolower($r['barangay']);
            if (isset($uniq[$k])) continue;
            $uniq[$k] = true;
            $final[] = $r;
        }

        foreach (array_chunk($final, 200) as $chunk) {
            DB::table('locations')->insert($chunk);
        }

        $this->command->info('Locations seeded: ' . count($final));
    }
}
