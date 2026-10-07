<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCohortRequest;
use App\Http\Requests\Admin\StoreCourseModuleRequest;
use App\Http\Requests\Admin\StoreCourseRequest;
use App\Http\Requests\Admin\UpdateCohortRequest;
use App\Http\Requests\Admin\UpdateCourseRequest;
use App\Http\Resources\CohortResource;
use App\Http\Resources\CourseModuleResource;
use App\Http\Resources\CourseResource;
use App\Models\Cohort;
use App\Models\Course;
use App\Models\CourseModule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function index(): JsonResponse
    {
        $courses = Course::query()
            ->withCount(['enquiries', 'modules'])
            ->withSum('modules as modules_hours_total', 'hours')
            ->with([
                'modules:id,course_id,title,hours,order',
                'cohorts' => fn ($q) => $q->with('enquiries'),
            ])
            ->latest()
            ->get();

        return response()->json([
            'data' => CourseResource::collection($courses)->resolve(),
        ]);
    }

    public function store(StoreCourseRequest $request): JsonResponse
    {
        $course = Course::query()->create($request->validated());

        return response()->json([
            'data' => (new CourseResource($course->loadCount('enquiries')))->resolve(),
        ], 201);
    }

    public function show(Course $course): JsonResponse
    {
        $course->load(['modules', 'cohorts.enquiries'])->loadCount('enquiries');

        return response()->json([
            'data' => (new CourseResource($course))->resolve(),
        ]);
    }

    public function update(UpdateCourseRequest $request, Course $course): JsonResponse
    {
        $course->fill($request->validated())->save();
        $course->load(['modules', 'cohorts.enquiries'])->loadCount('enquiries');

        return response()->json([
            'data' => (new CourseResource($course))->resolve(),
        ]);
    }

    public function destroy(Course $course): JsonResponse
    {
        $course->delete();

        return response()->json(['message' => 'Deleted.']);
    }

    public function storeModule(StoreCourseModuleRequest $request, Course $course): JsonResponse
    {
        $order = $request->integer('order', (int) $course->modules()->max('order') + 1);
        $module = $course->modules()->create([
            ...$request->safe()->except('order'),
            'order' => $order,
        ]);

        return response()->json([
            'data' => (new CourseModuleResource($module))->resolve(),
        ], 201);
    }

    public function updateModule(StoreCourseModuleRequest $request, Course $course, CourseModule $module): JsonResponse
    {
        abort_unless($module->course_id === $course->id, 404);
        $module->fill($request->validated())->save();

        return response()->json([
            'data' => (new CourseModuleResource($module))->resolve(),
        ]);
    }

    public function destroyModule(Course $course, CourseModule $module): JsonResponse
    {
        abort_unless($module->course_id === $course->id, 404);
        $module->delete();

        return response()->json(['message' => 'Deleted.']);
    }

    public function reorderModules(Request $request, Course $course): JsonResponse
    {
        $data = $request->validate([
            'order' => ['required', 'array', 'min:1'],
            'order.*' => ['integer', 'distinct'],
        ]);

        foreach (array_values($data['order']) as $index => $id) {
            CourseModule::query()
                ->where('course_id', $course->id)
                ->where('id', $id)
                ->update(['order' => $index + 1]);
        }

        return response()->json([
            'data' => CourseModuleResource::collection($course->modules()->get())->resolve(),
        ]);
    }

    public function storeCohort(StoreCohortRequest $request, Course $course): JsonResponse
    {
        $cohort = $course->cohorts()->create($request->validated());
        $cohort->load('enquiries');

        return response()->json([
            'data' => (new CohortResource($cohort))->resolve(),
        ], 201);
    }

    public function updateCohort(UpdateCohortRequest $request, Course $course, Cohort $cohort): JsonResponse
    {
        abort_unless($cohort->course_id === $course->id, 404);
        $cohort->fill($request->validated())->save();
        $cohort->load(['enquiries', 'course']);

        return response()->json([
            'data' => (new CohortResource($cohort))->resolve(),
        ]);
    }

    public function destroyCohort(Course $course, Cohort $cohort): JsonResponse
    {
        abort_unless($cohort->course_id === $course->id, 404);
        $cohort->delete();

        return response()->json(['message' => 'Deleted.']);
    }
}
