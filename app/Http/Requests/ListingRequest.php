<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

class ListingRequest extends FormRequest
{
    public function authorize(): bool
    {
        $listing = $this->route('listing');

        return $listing === null || $this->user()->can('update', $listing);
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['product', 'service'])],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['required', 'string', 'max:5000'],
            'category_id' => ['bail', 'required', 'integer', 'exists:categories,id'],
            'subcategory_id' => ['bail', 'required', 'integer', $this->belongsTo('subcategories', 'category_id')],
            'country_id' => ['bail', 'required', 'integer', 'exists:countries,id'],
            'state_id' => ['bail', 'required', 'integer', $this->belongsTo('states', 'country_id')],
            'city_id' => ['bail', 'required', 'integer', $this->belongsTo('cities', 'state_id')],
            'area_id' => ['bail', 'required', 'integer', $this->belongsTo('areas', 'city_id')],
            'price' => ['required', 'numeric', 'regex:/\A\d{1,10}(?:\.\d{1,2})?\z/'],
            'images' => ['sometimes', 'array', 'list', 'max:5'],
            'images.*' => [
                'bail', 'required', 'image', 'mimes:jpg,jpeg,png,webp', 'extensions:jpg,jpeg,png,webp', 'max:5120',
                Rule::dimensions()->minWidth(1)->minHeight(1),
            ],
            'remove_images' => ['sometimes', 'array', 'list', 'max:5'],
            'remove_images.*' => ['bail', 'required', 'integer', 'distinct', Rule::exists('listing_images', 'id')
                ->where('listing_id', $this->route('listing')?->getKey() ?? 0)],
        ];
    }

    public function attributes(): array
    {
        return [
            'category_id' => 'category', 'subcategory_id' => 'subcategory', 'country_id' => 'country',
            'state_id' => 'state', 'city_id' => 'city', 'area_id' => 'area', 'images.*' => 'image',
        ];
    }

    public function messages(): array
    {
        return [
            'price.regex' => 'Enter a non-negative price with up to ten digits and two decimal places.',
            'subcategory_id.exists' => 'Choose a subcategory in the selected category.',
            'state_id.exists' => 'Choose a state in the selected country.',
            'city_id.exists' => 'Choose a city in the selected state.',
            'area_id.exists' => 'Choose an area in the selected city.',
            'images.*.max' => 'Each image must be 5 MB or smaller.',
            'images.*.dimensions' => 'The file could not be read as an image.',
        ];
    }

    public function listingAttributes(): array
    {
        return Arr::except($this->validated(), ['country_id', 'state_id', 'images', 'remove_images']);
    }

    private function belongsTo(string $table, string $parent): Exists
    {
        $value = $this->input($parent);

        return Rule::exists($table, 'id')->where($parent, is_scalar($value) ? $value : null);
    }
}
