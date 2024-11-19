<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Recipe;
use App\Models\Category;

class RecipeController extends Controller
{
    public function home()
    {
        // get list
        $recipes = Recipe::select(
            'recipes.id',
            'recipes.title',
            'recipes.description',
            'recipes.created_at',
            'recipes.image',
            'users.name',
        )
            ->join('users', 'users.id', '=', 'recipes.user_id')
            ->orderBy('recipes.created_at', 'desc')
            ->limit(3)
            ->get();

        $populars = Recipe::select('recipes.id', 'recipes.title', 'recipes.description', 'recipes.created_at', 'recipes.image', 'users.name')
            ->join('users', 'users.id', '=', 'recipes.user_id')
            ->orderBy('recipes.views', 'desc')
            ->limit(2)
            ->get();

        return view('home', compact('recipes', 'populars'));
    }


    /**
     * Display a listing of the resource.
     */
    public function index(Request $req)
    {
        $filters = $req->all();
        $query = Recipe::query()->select(
            'recipes.id',
            'recipes.title',
            'recipes.description',
            'recipes.created_at',
            'recipes.image',
            'users.name',
            \DB::raw('AVG(reviews.rating) as rating')
        )
            ->join('users', 'users.id', '=', 'recipes.user_id')
            ->leftJoin('reviews', 'reviews.recipe_id', '=', 'recipes.id')
            ->groupBy('recipes.id')
            ->orderBy('recipes.created_at', 'desc');

        // Filtering
        if (!empty($filters)) {
            // with categories
            if(!empty($filters['categories'])) {
                $query->whereIn('recipes.category_id', $filters['categories']);
            }

            //with rating
            if(!empty($filters['rating'])) {
                $query
                    ->havingRaw('AVG(reviews.rating) >= ?', [$filters['rating']])
                    ->orderBy('rating', 'desc');
            }

            // with title
            if(!empty($filters['title'])) {
                $query->where('recipes.title', 'LIKE',  '%'.$filters['title'].'%');
            }
        }

        $recipes = $query->paginate(8);

        $categories = $populars = Category::all();

        return view('recipes.index', compact('recipes', 'categories', 'filters'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $recipe = Recipe::with(['ingredients', 'steps', 'reviews.user', 'user'])
            ->where('recipes.id', $id)
            ->first();

        $recipe_record = Recipe::find($id);
        $recipe_record->increment('views');
        return view('recipes.show', compact('recipe'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
