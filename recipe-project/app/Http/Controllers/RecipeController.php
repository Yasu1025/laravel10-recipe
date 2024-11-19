<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Models\Recipe;
use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Step;

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
            DB::raw('AVG(reviews.rating) as rating')
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
        $categories = $populars = Category::all();
        return view('recipes.create', compact('categories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $req)
    {
        $posts = $req->all();
        $uuid = Str::uuid()->toString();
        $image = $req->file('image');
        // Upload image to S3
        // $path = Storage::disk('s3')->putFile('recipe', $image, 'public');
        // $url = Storage::disk('s3')->url($path);
        $dummy_image = 'https://source.unsplash.com/random/?breakfast';

        try {
            DB::beginTransaction();
            Recipe::insert([
                'id' => $uuid,
                'title' => $posts['title'],
                'description' => $posts['description'],
                'category_id' => $posts['category'],
                'image' => $dummy_image,
                'user_id' => Auth::id()
            ]);

            $ingredients = [];
            foreach ($posts['ingredients'] as $key => $ingredient) {
                $ingredients[$key] = [
                    'recipe_id' => $uuid,
                    'name' => $ingredient['name'],
                    'quantity' => $ingredient['quantity']

                ];
            }
            Ingredient::insert($ingredients);

            $steps = [];
            foreach ($posts['steps'] as $key => $step) {
                $steps[$key] = [
                    'recipe_id' => $uuid,
                    'step_number' => $key + 1,
                    'description' => $step

                ];
            }
            Step::insert($steps);

            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            \Log::debug(print_r($th->getMessage(), true));
            throw $th;
        }

        return redirect()->route('recipe.show', ['id' => $uuid]);
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
