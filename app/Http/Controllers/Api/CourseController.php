<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CourseResource;
use App\Models\Course;
use Illuminate\Http\JsonResponse;

class CourseController extends Controller
{
    public function index(): JsonResponse
    {
        $courses = Course::query()
            ->where('is_published', true)
            ->with(['modules', 'cohorts' => fn ($q) => $q->whereIn('status', ['open', 'full'])->with('enquiries')])
            ->latest()
            ->get();

        return response()->json([
            'data' => CourseResource::collection($courses)->resolve(),
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $course = Course::query()
            ->where('slug', $slug)
            ->where('is_published', true)
            ->with([
                'modules',
                'cohorts' => fn ($q) => $q->whereIn('status', ['open', 'full', 'closed'])->with('enquiries'),
            ])
            ->firstOrFail();

        return response()->json([
            'data' => (new CourseResource($course))->resolve(),
        ]);
    }
}
