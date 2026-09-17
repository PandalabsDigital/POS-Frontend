<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\InventorySupplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function index(Request $request): View
    {
        $suppliers = InventorySupplier::query()
            ->withCount('purchases')
            ->when($request->filled('q'), fn ($query) => $query->where('name', 'like', '%'.$request->string('q').'%'))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('inventory.suppliers.index', compact('suppliers'));
    }

    public function create(): View
    {
        return view('inventory.suppliers.form', ['supplier' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        InventorySupplier::query()->create($this->validated($request) + ['is_active' => $request->boolean('is_active', true)]);

        return redirect()->route('inventory.suppliers.index')->with('success', 'Supplier added.');
    }

    public function edit(InventorySupplier $supplier): View
    {
        $supplier->load('items');

        return view('inventory.suppliers.form', compact('supplier'));
    }

    public function update(Request $request, InventorySupplier $supplier): RedirectResponse
    {
        $supplier->update($this->validated($request) + ['is_active' => $request->boolean('is_active', true)]);

        return redirect()->route('inventory.suppliers.index')->with('success', 'Supplier updated.');
    }

    public function destroy(InventorySupplier $supplier): RedirectResponse
    {
        $supplier->delete();

        return redirect()->route('inventory.suppliers.index')->with('success', 'Supplier deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'contact_name' => ['nullable', 'string', 'max:80'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:120'],
            'address' => ['nullable', 'string', 'max:191'],
            'tax_number' => ['nullable', 'string', 'max:80'],
            'payment_terms' => ['nullable', 'string', 'max:80'],
            'currency_code' => ['nullable', 'string', 'max:3'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);
    }
}
