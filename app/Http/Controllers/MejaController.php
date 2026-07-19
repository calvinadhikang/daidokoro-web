<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMejaRequest;
use App\Http\Requests\UpdateMejaRequest;
use App\Models\Meja;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class MejaController extends Controller
{
    public function index(): Response
    {
        $mejas = Meja::query()
            ->orderBy('code')
            ->get();

        return Inertia::render('admin/mejas/index', [
            'mejas' => $mejas,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/mejas/create');
    }

    public function store(StoreMejaRequest $request): RedirectResponse
    {
        $meja = Meja::query()->create($request->validated());

        return redirect()
            ->route('admin.mejas.show', $meja)
            ->with('success', 'Table created successfully.');
    }

    public function show(Meja $meja): Response
    {
        return Inertia::render('admin/mejas/show', [
            'meja' => $meja,
            'qrUrl' => route('table.entry', ['code' => $meja->code]),
        ]);
    }

    public function update(UpdateMejaRequest $request, Meja $meja): RedirectResponse
    {
        $meja->update($request->validated());

        return redirect()
            ->route('admin.mejas.show', $meja)
            ->with('success', 'Table updated successfully.');
    }

    public function destroy(Meja $meja): RedirectResponse
    {
        $meja->delete();

        return redirect()
            ->route('admin.mejas.index')
            ->with('success', 'Table deleted successfully.');
    }
}
