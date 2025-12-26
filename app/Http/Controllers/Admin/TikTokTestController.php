<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TikTokTestCase;
use App\Services\TikTokTestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class TikTokTestController extends Controller
{
    protected $testService;

    public function __construct(TikTokTestService $testService)
    {
        $this->testService = $testService;
    }

    public function index()
    {
        return view('admin.tiktok-tests.index');
    }

    public function show(Request $request)
    {
        $id = $request->id;
        $testCase = TikTokTestCase::with(['testPost', 'tiktokAccount'])->findOrFail($id);
        return view('admin.tiktok-tests.show', compact('testCase'));
    }

    public function dataTable(Request $request)
    {
        $data = $request->all();
        $search = @$data['search']['value'];
        $order = end($data['order']);
        $orderby = $data['columns'][$order['column']]['data'] ?? 'id';

        $columnMapping = [
            'id' => 'id',
            'test_type' => 'test_type',
            'status_badge' => 'status',
            'failure_reason' => 'failure_reason',
            'ran_at' => 'ran_at',
        ];

        $orderColumn = $columnMapping[$orderby] ?? 'id';

        $iTotalRecords = TikTokTestCase::query();
        $testCases = TikTokTestCase::with(['testPost', 'tiktokAccount']);

        if (!empty($search)) {
            $testCases = $testCases->where(function ($query) use ($search) {
                $query->where('test_type', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%")
                    ->orWhere('failure_reason', 'like', "%{$search}%");
            });
        }

        if ($request->has('test_type') && !empty($request->test_type)) {
            $testCases = $testCases->where('test_type', $request->test_type);
        }

        if ($request->has('status') && !empty($request->status)) {
            $testCases = $testCases->where('status', $request->status);
        }

        if ($request->has('date_from') && !empty($request->date_from)) {
            $testCases = $testCases->whereDate('ran_at', '>=', $request->date_from);
        }

        if ($request->has('date_to') && !empty($request->date_to)) {
            $testCases = $testCases->whereDate('ran_at', '<=', $request->date_to);
        }

        $totalRecordswithFilter = clone $testCases;
        $testCases->orderBy($orderColumn, $order['dir']);

        $testCases = $testCases->offset(intval($data['start']));
        $testCases = $testCases->limit(intval($data['length']));

        $testCases = $testCases->get();

        $formattedData = $testCases->map(function ($testCase) {
            return [
                'id' => $testCase->id,
                'test_type' => ucfirst($testCase->test_type),
                'status_badge' => $testCase->status_badge,
                'failure_reason' => $testCase->failure_reason ? substr($testCase->failure_reason, 0, 100) . '...' : '-',
                'ran_at' => $testCase->ran_at ? $testCase->ran_at->format('Y-m-d H:i:s') : '-',
                'account_name' => $testCase->tiktokAccount ? ($testCase->tiktokAccount->display_name ?? $testCase->tiktokAccount->username) : '-',
                'action' => '<a href="' . route('admin.tiktok-tests.show', $testCase->id) . '" class="btn btn-sm btn-info">View Details</a>',
            ];
        });

        return response()->json([
            'draw' => intval($data['draw']),
            'iTotalRecords' => $iTotalRecords->count(),
            'iTotalDisplayRecords' => $totalRecordswithFilter->count(),
            'aaData' => $formattedData,
        ]);
    }

    public function runTests(Request $request)
    {
        try {
            Artisan::call('tiktok:run-tests');
            $output = Artisan::output();

            Log::info('TikTok tests manually triggered from admin panel', [
                'admin_id' => auth('admin')->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Tests started successfully. Check the results in a few moments.',
                'output' => $output
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to run TikTok tests from admin panel: ' . $e->getMessage(), [
                'admin_id' => auth('admin')->id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to run tests: ' . $e->getMessage()
            ], 500);
        }
    }
}

