<?php

namespace Database\Seeders;

use App\Models\Package;
use App\Models\SafariTour;
use Illuminate\Database\Seeder;

class PackageAndSafariSeeder extends Seeder
{
    public function run(): void
    {
        $packages = [
            ['lesson_count' => 4, 'price_try' => 7000.00, 'badge_label' => 'Başlangıç', 'sort_order' => 1, 'is_active' => true],
            ['lesson_count' => 8, 'price_try' => 13200.00, 'badge_label' => 'Popüler', 'sort_order' => 2, 'is_active' => true, 'is_featured' => true],
            ['lesson_count' => 12, 'price_try' => 18600.00, 'badge_label' => 'İleri Seviye', 'sort_order' => 3, 'is_active' => true],
            ['lesson_count' => 24, 'price_try' => 33600.00, 'badge_label' => 'Avantajlı', 'sort_order' => 4, 'is_active' => true],
        ];

        foreach ($packages as $pkg) {
            Package::query()->updateOrCreate(['lesson_count' => $pkg['lesson_count']], $pkg);
        }

        $safaris = [
            [
                'name' => 'Orman Parkuru Safari Paketi',
                'duration_minutes' => 60,
                'price_per_person' => 1500.00,
                'description' => 'Çam ormanları arasında sakin ve huzurlu bir başlangıç seviyesi safari turu.',
                'features' => ['Rehber eğitmen eşliği', 'Kask ve güvenlik yeleği', 'Tek veya grup katılımı'],
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'Göl & Doğa Safarisi Paketi',
                'duration_minutes' => 90,
                'price_per_person' => 2250.00,
                'description' => 'Orman içerisinden göl kıyısına uzanan geniş rotada eşsiz manzara eşliğinde dörtnala ve tırıs.',
                'features' => ['Uzun orman & göl rotası', 'Fotoğraf & video çekimi', 'Mola ikramları'],
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Gün Batımı VIP Safari Turu',
                'duration_minutes' => 120,
                'price_per_person' => 3500.00,
                'description' => 'Golden hour ışığında özel rota, profesyonel drone video kaydı ve kulüp terasında gurme ikramlar.',
                'features' => ['Özel rehber eşliği', 'Drone ve 4K çekim', 'VIP teras ikramı'],
                'sort_order' => 3,
                'is_active' => true,
            ],
        ];

        foreach ($safaris as $safari) {
            SafariTour::query()->updateOrCreate(['name' => $safari['name']], $safari);
        }
    }
}
