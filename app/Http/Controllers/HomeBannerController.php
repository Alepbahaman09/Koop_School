<?php

namespace App\Http\Controllers;

use App\Models\HomeBanner;
use Illuminate\Http\Request;

class HomeBannerController extends Controller
{
    public function index()
    {
        $banners = HomeBanner::orderBy('sort_order')
            ->latest()
            ->paginate(12);

        return view('home-banners', compact('banners'));
    }

    public function store(Request $request)
    {
        HomeBanner::create($this->validatedData($request));

        return back()->with('success', 'Home banner created successfully.');
    }

    public function update(Request $request, HomeBanner $homeBanner)
    {
        $homeBanner->update($this->validatedData($request));

        return back()->with('success', 'Home banner updated successfully.');
    }

    public function destroy(HomeBanner $homeBanner)
    {
        $homeBanner->delete();

        return back()->with('success', 'Home banner deleted successfully.');
    }

    private function validatedData(Request $request): array
    {
        $validated = $request->validate([
            'title' => 'required|string|max:80',
            'message' => 'required|string|max:240',
            'label' => 'nullable|string|max:40',
            'tone' => 'required|in:blue,green,orange,purple',
            'sort_order' => 'nullable|integer|min:0|max:999',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'is_active' => 'boolean',
        ]);

        $validated['sort_order'] = (int) ($validated['sort_order'] ?? 0);
        $validated['is_active'] = $request->boolean('is_active');

        return $validated;
    }
}
