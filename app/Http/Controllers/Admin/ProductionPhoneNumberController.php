<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Production;
use App\Models\ProductionPhoneNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProductionPhoneNumberController extends Controller
{
    public function store(Request $request, Production $production): RedirectResponse
    {
        $data = $this->validated($request);
        $maxOrder = (int) $production->phoneNumbers()->max('sort_order');

        $production->phoneNumbers()->create([
            ...$data,
            'sort_order' => $maxOrder + 1,
        ]);

        return redirect()
            ->route('admin.productions.show', $production)
            ->with('success', 'Numret är tillagt.');
    }

    public function update(Request $request, Production $production, ProductionPhoneNumber $phoneNumber): RedirectResponse
    {
        $this->ensureBelongsToProduction($production, $phoneNumber);

        $phoneNumber->update($this->validated($request));

        return redirect()
            ->route('admin.productions.show', $production)
            ->with('success', 'Numret är uppdaterat.');
    }

    public function destroy(Production $production, ProductionPhoneNumber $phoneNumber): RedirectResponse
    {
        $this->ensureBelongsToProduction($production, $phoneNumber);

        $phoneNumber->delete();

        return redirect()
            ->route('admin.productions.show', $production)
            ->with('success', 'Numret är borttaget.');
    }

    /**
     * @return array{label: string, phone: string}
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
        ]);
    }

    private function ensureBelongsToProduction(Production $production, ProductionPhoneNumber $phoneNumber): void
    {
        if ((int) $phoneNumber->production_id !== (int) $production->id) {
            abort(404);
        }
    }
}
