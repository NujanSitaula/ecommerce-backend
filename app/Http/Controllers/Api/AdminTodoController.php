<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdminTodo;
use Illuminate\Http\Request;

class AdminTodoController extends Controller
{
    /**
     * List all todos for the authenticated admin
     */
    public function index()
    {
        $todos = AdminTodo::where('user_id', auth()->id())
            ->orderBy('completed')
            ->orderByDesc('created_at')
            ->get();

        return response()->json($this->formatTodos($todos));
    }

    /**
     * Create a new todo
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'text' => ['required', 'string', 'max:2000'],
        ]);

        $todo = AdminTodo::create([
            'user_id' => auth()->id(),
            'text' => trim($validated['text']),
            'completed' => false,
        ]);

        return response()->json($this->formatTodo($todo), 201);
    }

    /**
     * Toggle todo completion
     */
    public function toggle(string $id)
    {
        $todo = AdminTodo::where('user_id', auth()->id())->findOrFail($id);
        $todo->update(['completed' => !$todo->completed]);

        return response()->json($this->formatTodo($todo->fresh()));
    }

    /**
     * Delete a todo
     */
    public function destroy(string $id)
    {
        $todo = AdminTodo::where('user_id', auth()->id())->findOrFail($id);
        $todo->delete();

        return response()->json(null, 204);
    }

    private function formatTodo(AdminTodo $todo): array
    {
        return [
            'id' => (string) $todo->id,
            'text' => $todo->text,
            'completed' => $todo->completed,
            'createdAt' => $todo->created_at->toIso8601String(),
        ];
    }

    private function formatTodos($todos): array
    {
        return $todos->map(fn ($t) => $this->formatTodo($t))->values()->all();
    }
}
