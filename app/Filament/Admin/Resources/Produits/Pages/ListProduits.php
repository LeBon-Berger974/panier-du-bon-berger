<?php

namespace App\Filament\Admin\Resources\Produits\Pages;

use App\Filament\Admin\Resources\Produits\ProduitResource;
use App\Models\Produit;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Facades\FilamentView;
use Filament\Tables\View\TablesRenderHook;
use Illuminate\Support\Facades\Blade;

class ListProduits extends ListRecords
{
    protected static string $resource = ProduitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function mount(): void
    {
        FilamentView::registerRenderHook(
            TablesRenderHook::TOOLBAR_SEARCH_AFTER,
            function (): string {
                $actifs = Produit::query()
                    ->where('actif', true)
                    ->count();

                $total = Produit::query()->count();

                return Blade::render(<<<'BLADE'
                    <div class="flex items-center gap-2 whitespace-nowrap rounded-lg border border-gray-200 px-3 py-2 text-sm dark:border-gray-700">
                        <span class="font-medium text-success-600 dark:text-success-400">
                            Produits actifs :
                        </span>
                        <span class="font-semibold text-gray-950 dark:text-white">
                            {{ $actifs }} / {{ $total }}
                        </span>
                    </div>
                BLADE, [
                    'actifs' => $actifs,
                    'total' => $total,
                ]);
            },
            scopes: self::class,
        );

        parent::mount();
    }
}
