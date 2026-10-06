<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\User;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = User::firstOrFail();
        $categories = $user->categories;

        return response()->json($categories);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);
        $user = User::firstOrFail();
        $category = $user->categories()->create($validated);

        return response()->json($category);
    }

    /**
     * Display the specified resource.
     */
    public function show(Category $category)
    {
        $user = User::firstOrFail();
        abort_unless($category->user_id == $user->id, 404);

        return response()->json($category);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Category $category)
    {
        $user = User::firstOrFail();
        // return 404 if the user not releated to this cat he updating
        abort_unless($category->user_id == $user->id, 404);
        // validate updated name of cat
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);
        $category->update($validated);

        return response()->json($category);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category)
    {
        $user = User::firstOrFail();
        abort_unless($category->user_id == $user->id, 404);
        $category->delete();

        return response()->noContent();

    }
}
