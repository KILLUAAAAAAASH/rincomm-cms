<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreHeroSlideRequest;
use App\Http\Requests\UpdateHeroSlideRequest;
use App\Models\HeroSlide;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Throwable;

class HeroSlideController extends Controller
{
    public function index(): View
    {
        $heroSlides = HeroSlide::query()
            ->orderBy('display_order')
            ->orderByDesc('created_at')
            ->get();

        return view('admin.hero-slides.index', compact('heroSlides'));
    }

    public function create(): View
    {
        return view('admin.hero-slides.create');
    }

    public function store(
        StoreHeroSlideRequest $request
    ): RedirectResponse {
        $data = $request->validated();

        $image = $data['image'];
        unset($data['image']);

        $imagePath = $image->store(
            'hero-slides',
            'public'
        );

        try {
            $data = $this->normalizeSlideData($data);

            $data['image_path'] = $imagePath;

            HeroSlide::create($data);
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($imagePath);

            throw $exception;
        }

        return redirect()
            ->route('admin.hero-slides.index')
            ->with(
                'success',
                'Hero slide created successfully.'
            );
    }

    public function show(HeroSlide $heroSlide): View
    {
        return view(
            'admin.hero-slides.show',
            compact('heroSlide')
        );
    }

    public function edit(HeroSlide $heroSlide): View
    {
        return view(
            'admin.hero-slides.edit',
            compact('heroSlide')
        );
    }

    public function update(
        UpdateHeroSlideRequest $request,
        HeroSlide $heroSlide
    ): RedirectResponse {
        $data = $request->validated();

        $newImagePath = null;
        $oldImagePath = $heroSlide->image_path;

        if (isset($data['image'])) {
            $newImagePath = $data['image']->store(
                'hero-slides',
                'public'
            );

            unset($data['image']);

            $data['image_path'] = $newImagePath;
        }

        try {
            $data = $this->normalizeSlideData($data);

            $heroSlide->update($data);
        } catch (Throwable $exception) {
            if ($newImagePath) {
                Storage::disk('public')
                    ->delete($newImagePath);
            }

            throw $exception;
        }

        if (
            $newImagePath &&
            $oldImagePath &&
            $oldImagePath !== $newImagePath
        ) {
            Storage::disk('public')
                ->delete($oldImagePath);
        }

        return redirect()
            ->route('admin.hero-slides.index')
            ->with(
                'success',
                'Hero slide updated successfully.'
            );
    }

    public function destroy(
        HeroSlide $heroSlide
    ): RedirectResponse {
        $imagePath = $heroSlide->image_path;

        $heroSlide->delete();

        if ($imagePath) {
            Storage::disk('public')->delete($imagePath);
        }

        return redirect()
            ->route('admin.hero-slides.index')
            ->with(
                'success',
                'Hero slide deleted successfully.'
            );
    }

    private function normalizeSlideData(
        array $data
    ): array {
        /*
         * Image-only slides should not keep hidden
         * title, description, or CTA information.
         */
        if ($data['content_type'] === 'image_only') {
            $data['title'] = null;
            $data['description'] = null;
            $data['cta_text'] = null;
            $data['cta_url'] = null;
        }

        /*
         * Image + text slides do not use a CTA.
         */
        if ($data['content_type'] === 'image_text') {
            $data['cta_text'] = null;
            $data['cta_url'] = null;
        }

        /*
         * Use zero when Admin does not provide
         * a custom display order.
         */
        $data['display_order'] =
            $data['display_order'] ?? 0;

        return $data;
    }
}