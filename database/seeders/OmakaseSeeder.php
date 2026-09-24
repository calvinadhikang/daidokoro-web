<?php

namespace Database\Seeders;

use App\Models\OmakaseMenu;
use App\Models\OmakaseSession;
use Illuminate\Database\Seeder;

class OmakaseSeeder extends Seeder
{
    /**
     * Seed omakase session and menu data.
     */
    public function run(): void
    {
        $sessionNames = [
            'Kaiseki Evening',
            'Summer Omakase',
            'Chef\'s Selection',
            'Premium Tasting',
            'Seasonal Journey',
            'Ocean\'s Bounty',
        ];

        $session = OmakaseSession::query()->firstOrCreate(
            ['date' => '2026-07-10'],
            ['name' => $sessionNames[array_rand($sessionNames)]],
        );

        $menus = [
            'CAWAN MUSI IKURA',
            'OCEAN P/YUMEKASAGO',
            'LONGFINNED B.E/CHIKAMEKINTOKI',
            'RED SEABREAM/MADAI',
            'B.T SEAPERCH/NODOGURO',
            'B.POMPRET/SHIMAGATSUO',
            'MIROR DORY/KAGAMIDAI',
            'ALFONSINO KINMEDAI',
            'TORO MEKAJIKI',
            'AKAMI BLUE FIN',
            'OTORO BLUE FIN',
            'SASHIMI',
            'NEGITORO',
            'CHEESECAKE',
        ];

        $session->omakaseMenus()->delete();

        foreach ($menus as $name) {
            OmakaseMenu::query()->create([
                'omakase_session_id' => $session->id,
                'name' => $name,
            ]);
        }
    }
}
