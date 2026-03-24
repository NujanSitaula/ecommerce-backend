<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReviewResource;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminReviewController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->query('perPage', 20);
        $perPage = $perPage > 0 ? $perPage : 20;

        $status = $request->query('status');
        $productId = $request->query('product_id');
        $search = $request->query('search');

        $query = Review::query()
            ->with(['product', 'moderatedBy'])
            ->orderByDesc('created_at');

        if (!empty($status)) {
            $query->where('status', $status);
        }

        if (!empty($productId)) {
            $query->where('product_id', $productId);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('author_name', 'like', "%{$search}%")
                    ->orWhere('author_email', 'like', "%{$search}%");
            });
        }

        $reviews = $query->paginate($perPage);

        return response()->json([
            'data' => ReviewResource::collection($reviews->items()),
            'total' => $reviews->total(),
            'page' => $reviews->currentPage(),
            'perPage' => $reviews->perPage(),
        ]);
    }

    public function approve(int $id)
    {
        $review = Review::with('moderatedBy')->findOrFail($id);
        $review->status = 'approved';
        $review->moderated_by = Auth::id();
        $review->moderated_at = now();
        $review->save();

        return new ReviewResource($review->fresh()->load(['product', 'moderatedBy']));
    }

    public function reject(int $id)
    {
        $review = Review::with('moderatedBy')->findOrFail($id);
        $review->status = 'rejected';
        $review->moderated_by = Auth::id();
        $review->moderated_at = now();
        $review->save();

        return new ReviewResource($review->fresh()->load(['product', 'moderatedBy']));
    }

    public function hide(int $id)
    {
        $review = Review::with('moderatedBy')->findOrFail($id);
        $review->status = 'hidden';
        $review->moderated_by = Auth::id();
        $review->moderated_at = now();
        $review->save();

        return new ReviewResource($review->fresh()->load(['product', 'moderatedBy']));
    }

    public function destroy(int $id)
    {
        $review = Review::findOrFail($id);
        $review->delete();

        return response()->json([
            'message' => 'Review deleted successfully',
        ]);
    }
}

