<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateCourseEnquiryRequest;
use App\Http\Resources\CourseEnquiryResource;
use App\Models\CourseEnquiry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CourseEnquiryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $enquiries = CourseEnquiry::query()
            ->filter(
                $request->query('status'),
                $request->query('search'),
                $request->integer('course_id') ?: null,
                $request->integer('cohort_id') ?: null,
            )
            ->latest()
            ->paginate(20)
            ->withQueryString();

        // Flat pagination keys match the Vue admin client (not Resource meta wrapping).
        return response()->json([
            'data' => CourseEnquiryResource::collection($enquiries->getCollection())->resolve(),
            'current_page' => $enquiries->currentPage(),
            'last_page' => $enquiries->lastPage(),
            'per_page' => $enquiries->perPage(),
            'total' => $enquiries->total(),
            'from' => $enquiries->firstItem(),
            'to' => $enquiries->lastItem(),
        ]);
    }

    public function show(CourseEnquiry $courseEnquiry): JsonResponse
    {
        return response()->json([
            'data' => (new CourseEnquiryResource($courseEnquiry))->resolve(),
        ]);
    }

    public function update(UpdateCourseEnquiryRequest $request, CourseEnquiry $courseEnquiry): JsonResponse
    {
        $courseEnquiry->fill($request->validated())->save();

        return response()->json([
            'data' => new CourseEnquiryResource($courseEnquiry->fresh()),
        ]);
    }

    public function destroy(CourseEnquiry $courseEnquiry): JsonResponse
    {
        $courseEnquiry->delete();

        return response()->json(['message' => 'Deleted.']);
    }

    public function export(Request $request): StreamedResponse
    {
        $filename = 'course-requests-'.now()->toDateString().'.csv';

        return response()->streamDownload(function () use ($request) {
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                'id', 'name', 'email', 'contact', 'language', 'format', 'level',
                'status', 'admin_note', 'message', 'created_at',
            ]);

            CourseEnquiry::query()
                ->filter(
                    $request->query('status'),
                    $request->query('search'),
                    $request->integer('course_id') ?: null,
                    $request->integer('cohort_id') ?: null,
                )
                ->latest()
                ->chunk(200, function ($rows) use ($out) {
                    foreach ($rows as $row) {
                        fputcsv($out, [
                            $row->id,
                            $row->name,
                            $row->email,
                            $row->contact,
                            $row->language,
                            $row->format,
                            $row->level,
                            $row->status,
                            $row->admin_note,
                            $row->message,
                            $row->created_at?->toIso8601String(),
                        ]);
                    }
                });

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
