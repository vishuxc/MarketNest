<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\City;
use App\Models\Country;
use App\Models\State;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\JsonResponse;

class ListingOptionsController extends Controller
{
    public function subcategories(Category $category): JsonResponse
    {
        return $this->options($category->subcategories());
    }

    public function states(Country $country): JsonResponse
    {
        return $this->options($country->states());
    }

    public function cities(State $state): JsonResponse
    {
        return $this->options($state->cities());
    }

    public function areas(City $city): JsonResponse
    {
        return $this->options($city->areas());
    }

    private function options(HasMany $relation): JsonResponse
    {
        return response()->json($relation->orderBy('name')->get(['id', 'name']));
    }
}
