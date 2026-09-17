<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCondimentRequest;
use App\Http\Requests\UpdateCondimentRequest;
use App\Models\Condiment;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CondimentController extends Controller
{
    public function index(): View
    {
        return view('condiments.index', [
            'condiments' => Condiment::query()->orderBy('name')->get(),
        ]);
    }

    public function store(StoreCondimentRequest $request): RedirectResponse
    {
        Condiment::query()->create([
            'name' => $request->validated('name'),
            'price' => $request->validated('price'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('condiments.index')->with('success', 'Add-on added.');
    }

    public function update(UpdateCondimentRequest $request, Condiment $condiment): RedirectResponse
    {
        $condiment->update([
            'name' => $request->validated('name'),
            'price' => $request->validated('price'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('condiments.index')->with('success', 'Add-on updated.');
    }

    public function destroy(Condiment $condiment): RedirectResponse
    {
        $condiment->delete();

        return redirect()->route('condiments.index')->with('success', 'Add-on deleted.');
    }
}
