<?php

namespace Database\Seeders;

use App\Models\Variety;
use Illuminate\Database\Seeder;

class VarietySeeder extends Seeder
{
    /**
     * Global varieties (user_id = NULL) curated for availability in Austria / Central Europe.
     *
     * Idempotent: keyed on name via updateOrCreate, safe to re-run in production.
     *
     * @var array<int, array{name: string, origin: string}>
     */
    private array $varieties = [
        // International commercial standards
        ['name' => 'Granny Smith', 'origin' => 'Australia, 1868'],
        ['name' => 'Golden Delicious', 'origin' => 'USA (West Virginia), 1890s'],
        ['name' => 'Red Delicious', 'origin' => 'USA (Iowa), 1870s'],
        ['name' => 'Gala', 'origin' => 'New Zealand, 1930s'],
        ['name' => 'Fuji', 'origin' => 'Japan, 1930s/1962'],
        ['name' => 'Braeburn', 'origin' => 'New Zealand, 1950s'],
        ['name' => 'Jonagold', 'origin' => 'USA, 1943'],
        ['name' => 'Elstar', 'origin' => 'Netherlands, 1955'],
        ['name' => 'Idared', 'origin' => 'USA, 1942'],
        ['name' => 'Pink Lady (Cripps Pink)', 'origin' => 'Australia, 1973'],
        ['name' => 'Jazz (Scifresh)', 'origin' => 'New Zealand, 1980s'],
        ['name' => 'Envy', 'origin' => 'New Zealand, 2000s'],
        ['name' => 'Kanzi', 'origin' => 'Belgium, 2000s'],
        ['name' => 'Opal', 'origin' => 'Czech Republic, 1999'],
        ['name' => 'Cosmic Crisp', 'origin' => 'USA (Washington), 2019'],
        ['name' => 'Honeycrisp', 'origin' => 'USA (Minnesota), 1960/1991'],
        ['name' => 'Ambrosia', 'origin' => 'Canada, 1990s'],
        ['name' => 'Cameo', 'origin' => 'USA (Washington), 1987'],
        ['name' => 'Rubens', 'origin' => 'Italy, 1990s'],
        ['name' => 'Wellant', 'origin' => 'Netherlands, 1980s'],

        // German / Austrian classics and heirlooms
        ['name' => 'Boskoop (Schöner aus Boskoop)', 'origin' => 'Niederlande, 1856'],
        ['name' => 'Kronprinz Rudolf', 'origin' => 'Steiermark, Österreich, 19. Jh.'],
        ['name' => 'Ilzer Rosenapfel', 'origin' => 'Oberösterreich, alte Sorte'],
        ['name' => 'Steirischer Maschanzker', 'origin' => 'Steiermark, Österreich, alte Sorte'],
        ['name' => 'Gravensteiner', 'origin' => 'Dänemark / Norddeutschland, 17. Jh.'],
        ['name' => 'Berlepsch', 'origin' => 'Deutschland, 1880'],
        ['name' => 'Cox Orange', 'origin' => 'England, 1830'],
        ['name' => 'Topaz', 'origin' => 'Tschechien, 1984'],
        ['name' => 'Rubinette', 'origin' => 'Schweiz, 1966'],
        ['name' => 'Berner Rosenapfel', 'origin' => 'Schweiz, alte Sorte'],
        ['name' => 'Alkmene', 'origin' => 'Deutschland, 1930'],
        ['name' => 'Holsteiner Cox', 'origin' => 'Deutschland, frühes 20. Jh.'],
        ['name' => 'Ontarioapfel', 'origin' => 'Kanada, 1820'],
        ['name' => 'Roter Eiserapfel', 'origin' => 'Mitteleuropa, alte Sorte'],
        ['name' => 'Lavanttaler Bananenapfel', 'origin' => 'Kärnten, Österreich, alte Sorte'],
        ['name' => 'Weißer Klarapfel', 'origin' => 'Baltikum, 19. Jh.'],
        ['name' => 'James Grieve', 'origin' => 'Schottland, 1890s'],
        ['name' => 'Goldparmäne', 'origin' => 'Frankreich, 18. Jh.'],
        ['name' => 'Landsberger Renette', 'origin' => 'Deutschland, 1850s'],
        ['name' => 'Champagner Renette', 'origin' => 'Frankreich, 17. Jh.'],

        // Additional varieties plausible in Austrian markets and orchards
        ['name' => 'Jonathan', 'origin' => 'USA (New York), 1826'],
        ['name' => 'Jonalord', 'origin' => 'Niederlande, 1980s'],
        ['name' => 'Melrose', 'origin' => 'USA (Ohio), 1937'],
        ['name' => 'Discovery', 'origin' => 'England, 1949'],
        ['name' => 'Delbarestivale', 'origin' => 'Frankreich, 1953'],
        ['name' => 'Šampion (Shampion)', 'origin' => 'Tschechien, 1970s'],
        ['name' => 'Rubinola', 'origin' => 'Tschechien, 1980s'],
        ['name' => 'Rajka', 'origin' => 'Tschechien, 1970s'],
        ['name' => 'Karneval', 'origin' => 'Tschechien, 1970s'],
        ['name' => 'Sirius', 'origin' => 'Tschechien, 1980s'],
        ['name' => 'Angold', 'origin' => 'Ungarn, 1970s'],
        ['name' => 'Florina', 'origin' => 'Frankreich, 1977'],
        ['name' => 'Retina', 'origin' => 'Tschechien, 1965'],
        ['name' => 'Rewena', 'origin' => 'Deutschland, 1980s'],
        ['name' => 'Regine', 'origin' => 'Deutschland, 1970s'],
        ['name' => 'Reglindis', 'origin' => 'Deutschland, 1980s'],
        ['name' => 'Pinova', 'origin' => 'Deutschland, 1986'],
        ['name' => 'Natyra (Magic Star)', 'origin' => 'Belgien, 2000s'],
        ['name' => 'Story (Diwa)', 'origin' => 'Schweiz, 2000s'],
        ['name' => 'Junami', 'origin' => 'Schweiz, 2009'],
        ['name' => 'Crimson Crisp', 'origin' => 'USA, 2005'],
        ['name' => 'Sonya', 'origin' => 'New Zealand, 1980s'],
        ['name' => 'Rome Beauty', 'origin' => 'USA (Ohio), 1848'],
        ['name' => 'Winesap', 'origin' => 'USA, 18th century'],
        ['name' => 'Northern Spy', 'origin' => 'USA (New York), 1800'],
        ['name' => 'Mutsu (Crispin)', 'origin' => 'Japan, 1930'],
        ['name' => 'Spartan', 'origin' => 'Canada, 1926'],
        ['name' => 'McIntosh', 'origin' => 'Canada, 1811'],
        ['name' => 'Cortland', 'origin' => 'USA (New York), 1898'],
        ['name' => 'Empire', 'origin' => 'USA (New York), 1966'],
        ['name' => 'Winter Banana', 'origin' => 'USA (Indiana), 1876'],
        ['name' => 'Court Pendu Plat', 'origin' => 'Frankreich, sehr alte Sorte'],
        ['name' => "Calville Blanc d'Hiver", 'origin' => 'Frankreich, 16. Jh.'],
        ['name' => 'Reinette du Canada', 'origin' => 'Frankreich, 17. Jh.'],
        ['name' => "Ashmead's Kernel", 'origin' => 'England, 1700'],
        ['name' => 'Egremont Russet', 'origin' => 'England, 1872'],
        ['name' => 'Blenheim Orange', 'origin' => 'England, 1740'],
        ['name' => 'Ribston Pippin', 'origin' => 'England, 1707'],
        ['name' => 'Worcester Pearmain', 'origin' => 'England, 1874'],
        ['name' => "Laxton's Superb", 'origin' => 'England, 1897'],
        ['name' => "Bramley's Seedling", 'origin' => 'England, 1809'],
        ['name' => 'Katja (Katy)', 'origin' => 'Schweden, 1947'],
        ['name' => 'Akane', 'origin' => 'Japan, 1937'],
        ['name' => 'Summerred', 'origin' => 'Kanada, 1970s'],
        ['name' => 'Vista Bella', 'origin' => 'USA, 1974'],
        ['name' => 'Piros', 'origin' => 'Tschechien, 1965'],
        ['name' => 'Resi', 'origin' => 'Tschechien, 1965'],
        ['name' => 'Rheinischer Bohnapfel', 'origin' => 'Deutschland, 18. Jh.'],
        ['name' => 'Danziger Kantapfel', 'origin' => 'Polen / Deutschland, alte Sorte'],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ($this->varieties as $variety) {
            Variety::query()->updateOrCreate(
                ['name' => $variety['name'], 'user_id' => null],
                ['origin' => $variety['origin']],
            );
        }
    }
}
