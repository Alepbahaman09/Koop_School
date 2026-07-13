<?php

namespace App\Http\Controllers;

use App\Models\HomeBanner;
use App\Services\ExpiredHomeBannerCleanup;
use App\Services\SupabaseStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HomeBannerController extends Controller
{
    public function __construct(
        private readonly SupabaseStorage $storage,
        private readonly ExpiredHomeBannerCleanup $expiredBannerCleanup,
    ) {}

    public function index()
    {
        $this->expiredBannerCleanup->run();

        $banners = HomeBanner::latest()
            ->paginate(12);

        return view('home-banners', compact('banners'));
    }

    public function cleanupExpired(): JsonResponse
    {
        return response()->json([
            'deleted_ids' => $this->expiredBannerCleanup->run(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validatedData($request);
        $validated['image_url'] = $this->storage->uploadImage(
            $request->file('image'),
            HomeBanner::IMAGE_BUCKET,
            'banners',
        );
        unset($validated['image']);

        HomeBanner::create($validated);

        return back()->with('success', 'Home banner created successfully.');
    }

    public function update(Request $request, HomeBanner $homeBanner)
    {
        $validated = $this->validatedData($request, imageRequired: false);
        $oldImage = $homeBanner->image_url;

        if ($request->hasFile('image')) {
            $validated['image_url'] = $this->storage->uploadImage(
                $request->file('image'),
                HomeBanner::IMAGE_BUCKET,
                'banners',
            );
        }

        unset($validated['image']);
        $homeBanner->update($validated);

        if ($request->hasFile('image')) {
            $this->storage->deletePublicFile($oldImage, HomeBanner::IMAGE_BUCKET);
        }

        return back()->with('success', 'Home banner updated successfully.');
    }

    public function destroy(HomeBanner $homeBanner)
    {
        $this->storage->deletePublicFile($homeBanner->image_url, HomeBanner::IMAGE_BUCKET);
        $homeBanner->delete();

        return back()->with('success', 'Home banner deleted successfully.');
    }

    private function validatedData(Request $request, bool $imageRequired = true): array
    {
        $validated = $request->validate(
            [
                'title' => 'required|string|max:80',
                'message' => 'required|string|max:240',
                'label' => 'nullable|string|max:40',
                'tone' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
                'image' => [
                    $imageRequired ? 'required' : 'nullable',
                    'image',
                    'max:5120',
                    'dimensions:width=1200,height=520',
                ],
                'is_active' => 'boolean',
                'expires_at' => ['nullable', 'date', 'after:now'],
            ],
            ['image.dimensions' => 'The banner image must be cropped to 1200 × 520 pixels.'],
        );

        $validated['is_active'] = $request->boolean('is_active');

        return $validated;
    }
}
