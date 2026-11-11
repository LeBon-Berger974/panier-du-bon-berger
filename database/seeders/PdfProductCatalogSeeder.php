<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PdfProductCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $catalogue = [
            'Ail', 'Artichauts', 'Aubergine', 'Bananes', 'Betterave', 'Bibasse', 'Brèdes', 'Brocoli',
            'Carottes', 'Chou', 'Chou-fleur', 'Citron', 'Courgette', 'Fraise', 'Gingembre', 'Goyavier',
            'Mangues', 'Oignons verts', 'Papayes', 'Pêches', 'Persil', 'Poireaux', 'Poivron',
            'Pommes de terre', 'Radis', 'Romarin', 'Salades', 'Songe', 'Thym', 'Tomates',
        ];

        $aliases = [
            'Carottes' => ['carrotes 🥕', 'carrotes'],
            'Pommes de terre' => ['pattates🥔', 'pattates'],
            'Tomates' => ['tomates 🍅'],
        ];

        DB::transaction(function () use ($catalogue, $aliases): void {
            foreach ($catalogue as $nom) {
                $produit = DB::table('produits')
                    ->whereRaw('LOWER(nom) = ?', [mb_strtolower($nom)])
                    ->first();

                if (! $produit) {
                    foreach ($aliases[$nom] ?? [] as $alias) {
                        $produit = DB::table('produits')
                            ->whereRaw('LOWER(nom) = ?', [mb_strtolower($alias)])
                            ->first();

                        if ($produit) {
                            DB::table('produits')->where('id', $produit->id)->update([
                                'nom' => $nom,
                                'updated_at' => now(),
                            ]);
                            break;
                        }
                    }
                }

                if ($produit) {
                    continue;
                }

                DB::table('produits')->insert([
                    'nom' => $nom,
                    'unite' => null,
                    'photo' => null,
                    'saison' => null,
                    'stock' => 0,
                    'seuil_alerte' => 5,
                    'actif' => false,
                    'desactive_stock' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }
}
