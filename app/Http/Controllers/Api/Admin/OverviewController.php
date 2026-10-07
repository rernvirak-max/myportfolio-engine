<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\CourseEnquiryResource;
use App\Models\CourseEnquiry;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class OverviewController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $now = now();
        $from7 = $now->copy()->subDays(7)->startOfDay();
        $from30 = $now->copy()->subDays(30)->startOfDay();

        $byStatus = CourseEnquiry::query()
            ->select('status', DB::raw('count(*) as aggregate'))
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->all();

        $counts = [];
        foreach (CourseEnquiry::STATUSES as $status) {
            $counts[$status] = (int) ($byStatus[$status] ?? 0);
        }

        $total = array_sum($counts);
        $enrolled = $counts['enrolled'];
        $conversion = $total > 0 ? round($enrolled / $total, 4) : 0.0;

        $dailyRaw = CourseEnquiry::query()
            ->where('created_at', '>=', $from30)
            ->select(DB::raw('date(created_at) as day'), DB::raw('count(*) as aggregate'))
            ->groupBy('day')
            ->pluck('aggregate', 'day')
            ->all();

        $daily = [];
        for ($i = 29; $i >= 0; $i--) {
            $day = $now->copy()->subDays($i)->toDateString();
            $daily[] = [
                'date' => $day,
                'count' => (int) ($dailyRaw[$day] ?? 0),
            ];
        }

        $latest = CourseEnquiry::query()->latest()->limit(5)->get();

        return response()->json([
            'by_status' => $counts,
            'total' => $total,
            'new_last_7_days' => CourseEnquiry::query()->where('created_at', '>=', $from7)->count(),
            'new_last_30_days' => CourseEnquiry::query()->where('created_at', '>=', $from30)->count(),
            'conversion_rate' => $conversion,
            'daily_counts' => $daily,
            'latest' => CourseEnquiryResource::collection($latest)->resolve(),
        ]);
    }
}
