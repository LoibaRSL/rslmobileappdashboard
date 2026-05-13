<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Application;
use App\Models\ApplicationLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // Cache dashboard data for 5 minutes to reduce database load
        $dashboardData = Cache::remember('dashboard_data_' . auth()->id(), 300, function () {
            return [
                'stats' => $this->getStatistics(),
                'recent_individual_applications' => $this->getRecentIndividualApplications(),
                'recent_business_applications' => $this->getRecentBusinessApplications(),
                'chart_data' => $this->getChartData(),
                'pie_chart_data' => $this->getPieChartData(),
            ];
        });

        return view('dashboard', $dashboardData);
    }

    /**
     * Get dashboard statistics
     */
    private function getStatistics()
    {
        // Using cache for counts to avoid repeated queries
        $cacheKey = 'dashboard_stats';
        
        return Cache::remember($cacheKey, 300, function () {
            return [
                'new_applications' => Application::where('status', 'pending')->count(),
                'approved_applications' => Application::where('status', 'approved')->count(),
                'rejected_applications' => Application::where('status', 'rejected')->count(),
                'upgrades' => Application::where('type', 'upgrade')->count(),
                'pending_approvals' => Application::where('status', 'pending')->count(),
            ];
        });
    }

    /**
     * Get recent individual applications
     */
    private function getRecentIndividualApplications()
    {
        return Application::with('user')
            ->where('applicant_type', 'individual')
            ->orderBy('created_at', 'desc')
            ->limit(7)
            ->get()
            ->map(function ($application) {
                return [
                    'id' => $application->id,
                    'name' => $application->applicant_name,
                    'reference_no' => $application->reference_no,
                    'amount' => number_format($application->amount, 2),
                    'status' => $application->status,
                    'status_badge' => $this->getStatusBadge($application->status),
                    'date' => $application->created_at->format('Y-m-d'),
                    'date_human' => $application->created_at->diffForHumans(),
                ];
            });
    }

    /**
     * Get recent business applications
     */
    private function getRecentBusinessApplications()
    {
        return Application::with('user')
            ->where('applicant_type', 'business')
            ->orderBy('created_at', 'desc')
            ->limit(7)
            ->get()
            ->map(function ($application) {
                return [
                    'id' => $application->id,
                    'business_name' => $application->business_name,
                    'reference_no' => $application->reference_no,
                    'amount' => number_format($application->amount, 2),
                    'status' => $application->status,
                    'status_badge' => $this->getStatusBadge($application->status),
                    'date' => $application->created_at->format('Y-m-d'),
                    'date_human' => $application->created_at->diffForHumans(),
                ];
            });
    }

    /**
     * Get chart data for analytics
     */
    private function getChartData()
    {
        // Get last 12 months data
        $months = collect(range(11, 0))->map(function ($month) {
            return now()->subMonths($month)->format('M Y');
        });

        $applications = Application::select(
            DB::raw('DATE_FORMAT(created_at, "%b %Y") as month'),
            DB::raw('COUNT(*) as total'),
            DB::raw('SUM(CASE WHEN status = "approved" THEN 1 ELSE 0 END) as approved'),
            DB::raw('SUM(CASE WHEN status = "rejected" THEN 1 ELSE 0 END) as rejected')
        )
        ->where('created_at', '>=', now()->subMonths(11))
        ->groupBy('month')
        ->orderBy('created_at')
        ->get()
        ->keyBy('month');

        return [
            'labels' => $months->values(),
            'total' => $months->map(function ($month) use ($applications) {
                return $applications->get($month, ['total' => 0])['total'];
            })->values(),
            'approved' => $months->map(function ($month) use ($applications) {
                return $applications->get($month, ['approved' => 0])['approved'];
            })->values(),
            'rejected' => $months->map(function ($month) use ($applications) {
                return $applications->get($month, ['rejected' => 0])['rejected'];
            })->values(),
        ];
    }

    /**
     * Get pie chart data for application distribution
     */
    private function getPieChartData()
    {
        $statusCounts = Application::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        return [
            'labels' => ['Pending', 'Approved', 'Rejected'],
            'data' => [
                $statusCounts->get('pending', ['total' => 0])['total'],
                $statusCounts->get('approved', ['total' => 0])['total'],
                $statusCounts->get('rejected', ['total' => 0])['total'],
            ],
            'colors' => ['#f3b73e', '#2ab57d', '#fd625e'],
        ];
    }

    /**
     * Get status badge HTML
     */
    private function getStatusBadge($status)
    {
        return match($status) {
            'pending' => '<span class="badge bg-warning-subtle text-warning">Pending</span>',
            'approved' => '<span class="badge bg-success-subtle text-success">Approved</span>',
            'rejected' => '<span class="badge bg-danger-subtle text-danger">Rejected</span>',
            default => '<span class="badge bg-secondary-subtle text-secondary">' . ucfirst($status) . '</span>',
        };
    }

    /**
     * Export applications as CSV
     */
    public function exportApplications(Request $request)
    {
        $type = $request->get('type', 'all');
        
        $applications = Application::with('user')
            ->when($type == 'individual', function ($query) {
                return $query->where('applicant_type', 'individual');
            })
            ->when($type == 'business', function ($query) {
                return $query->where('applicant_type', 'business');
            })
            ->get();

        $filename = "applications_{$type}_" . date('Y-m-d_His') . ".csv";
        
        return response()->streamDownload(function () use ($applications) {
            $handle = fopen('php://output', 'w');
            
            // Add headers
            fputcsv($handle, ['Reference No', 'Applicant Name', 'Amount', 'Status', 'Applicant Type', 'Date']);
            
            // Add data
            foreach ($applications as $app) {
                fputcsv($handle, [
                    $app->reference_no,
                    $app->applicant_name ?? $app->business_name,
                    $app->amount,
                    $app->status,
                    $app->applicant_type,
                    $app->created_at->format('Y-m-d H:i:s'),
                ]);
            }
            
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * Refresh dashboard cache (AJAX endpoint)
     */
    public function refresh(Request $request)
    {
        Cache::forget('dashboard_data_' . auth()->id());
        Cache::forget('dashboard_stats');
        
        return response()->json([
            'success' => true,
            'message' => 'Dashboard data refreshed successfully',
        ]);
    }

    /**
     * Get real-time stats (AJAX endpoint)
     */
    public function getStats(Request $request)
    {
        $stats = $this->getStatistics();
        
        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}