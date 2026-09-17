<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\InventoryLocation;
use App\Models\InventoryUnit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CatalogExtrasController extends Controller
{
    public function units(): View
    {
        return view('inventory.units.index', [
            'units' => InventoryUnit::query()->orderBy('dimension')->orderBy('code')->get(),
        ]);
    }

    public function storeUnit(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:inventory_units,code'],
            'name' => ['required', 'string', 'max:80'],
            'dimension' => ['required', 'in:mass,volume,count,other'],
            'to_base' => ['required', 'numeric', 'min:0.00000001'],
        ]);
        InventoryUnit::query()->create($data + ['is_custom' => true]);

        return back()->with('success', 'Custom unit added.');
    }

    public function locations(): View
    {
        return view('inventory.locations.index', [
            'locations' => InventoryLocation::query()->orderByDesc('is_default')->orderBy('name')->get(),
        ]);
    }

    public function storeLocation(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'code' => ['nullable', 'string', 'max:40'],
        ]);

        if ($request->boolean('is_default')) {
            InventoryLocation::query()->update(['is_default' => false]);
        }

        InventoryLocation::query()->create($data + [
            'is_default' => $request->boolean('is_default'),
            'is_active' => true,
        ]);

        return back()->with('success', 'Location added.');
    }
}
