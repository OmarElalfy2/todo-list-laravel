<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = User::firstOrFail();
        $tasks = $user->tasks()
            ->with('category')
            ->get();

        return response()->json($tasks);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = User::firstOrFail();
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'nullable|integer',
            'due_date' => 'nullable|date',
            'is_completed' => 'sometimes|boolean',
        ]);
        if (! empty($validated['category_id'])) {
            Category::where('id', $validated['category_id'])
                ->where('user_id', $user->id)
                ->firstOrFail();
        }
        $task = $user->tasks()->create($validated);

        return response()->json($task);
    }

    /**
     * Display the specified resource.
     */
    public function show(Task $task)
    {
        $user = User::firstOrFail();
        abort_unless($task->user_id == $user->id, 404);

        return response()->json(
            $task->load('category')
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Task $task)
    {
        $user = User::firstOrFail();

        abort_unless($task->user_id == $user->id, 404);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'nullable|integer',
            'due_date' => 'nullable|date',
            'is_completed' => 'sometimes|boolean',
        ]);
        //
        if (! empty($validated['category_id'])) {
            Category::where('id', $validated['category_id'])
                ->where('user_id', $user->id)
                ->firstOrFail();
        }

        $task->update($validated);

        return response()->json($task);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Task $task)
    {
        $user = User::firstOrFail();

        abort_unless($task->user_id == $user->id, 404);

        $task->delete();

        return response()->noContent();
    }
}
