<?php

namespace App\Http\Requests;

use App\Models\HeroSlide;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateHeroSlideRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'content_type' => [
                'required',
                Rule::in([
                    'image_only',
                    'image_text',
                    'image_text_cta',
                ]),
            ],

            'category' => [
                'required',
                Rule::in([
                    'promotion',
                    'event',
                    'announcement',
                    'coverage_update',
                    'maintenance_advisory',
                ]),
            ],

            'title' => [
                Rule::requiredIf(
                    fn() => in_array(
                        $this->input('content_type'),
                        ['image_text', 'image_text_cta'],
                        true
                    )
                ),
                'nullable',
                'string',
                'max:255',
            ],

            'description' => [
                Rule::requiredIf(
                    fn() => in_array(
                        $this->input('content_type'),
                        ['image_text', 'image_text_cta'],
                        true
                    )
                ),
                'nullable',
                'string',
                'max:1500',
            ],

            // Keep the current image when no replacement is uploaded.
            'image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],

            'alt_text' => [
                'nullable',
                'string',
                'max:255',
            ],

            'cta_text' => [
                Rule::requiredIf(
                    fn() => $this->input('content_type') === 'image_text_cta'
                ),
                'nullable',
                'string',
                'max:100',
            ],

            'cta_url' => [
                Rule::requiredIf(
                    fn() => $this->input('content_type') === 'image_text_cta'
                ),
                'nullable',
                'string',
                'max:2048',
            ],

            'starts_at' => [
                'nullable',
                'date',
            ],

            'ends_at' => [
                'nullable',
                'date',
                Rule::when(
                    $this->filled('starts_at'),
                    ['after:starts_at']
                ),
            ],

            'display_order' => [
                'nullable',
                'integer',
                'min:0',
                'max:999',
            ],

            'is_active' => [
                'required',
                'boolean',
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $heroSlide = $this->route('hero_slide');

            $heroSlideId = $heroSlide instanceof HeroSlide
                ? $heroSlide->id
                : $heroSlide;

            // Exclude the current slide when checking the five-slide limit.
            if ($this->boolean('is_active')) {
                $activeSlideCount = HeroSlide::where('is_active', true)
                    ->when(
                        $heroSlideId,
                        fn($query) =>
                        $query->where('id', '!=', $heroSlideId)
                    )
                    ->count();

                if ($activeSlideCount >= 5) {
                    $validator->errors()->add(
                        'is_active',
                        'Only 5 hero slides can be active at the same time.'
                    );
                }
            }

            // Allow internal links, section links, and normal web URLs.
            $ctaUrl = $this->input('cta_url');

            if (
                $ctaUrl &&
                ! str_starts_with($ctaUrl, '#') &&
                ! str_starts_with($ctaUrl, '/') &&
                ! str_starts_with($ctaUrl, 'http://') &&
                ! str_starts_with($ctaUrl, 'https://')
            ) {
                $validator->errors()->add(
                    'cta_url',
                    'The CTA link must be a valid website or internal Rincomm link.'
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'image.max' =>
            'The carousel image must not be larger than 5 MB.',

            'image.mimes' =>
            'The carousel image must be JPG, JPEG, PNG, or WebP.',

            'title.required' =>
            'A title is required for this slide type.',

            'description.required' =>
            'A description is required for this slide type.',

            'cta_text.required' =>
            'Button text is required when the slide includes a CTA.',

            'cta_url.required' =>
            'A button link is required when the slide includes a CTA.',

            'ends_at.after' =>
            'The end date must be later than the start date.',
        ];
    }
}
