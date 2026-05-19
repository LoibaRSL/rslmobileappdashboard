<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\BusinessRegistration;
use App\Models\BusinessRegistrationLog;
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
                'pending_tasks' => $this->getPendingTasks(),
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
                'total_applications' => BusinessRegistration::count(),
                'new_applications' => BusinessRegistration::where('status', 'pending')->count(),
                'approved_applications' => BusinessRegistration::where('status', 'approved')->count(),
                'rejected_applications' => BusinessRegistration::where('status', 'rejected')->count(),
                'pending_approvals' => BusinessRegistration::where('status', 'pending')->count(),
                'today_applications' => BusinessRegistration::whereDate('created_at', today())->count(),
                'this_week_applications' => BusinessRegistration::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                'this_month_applications' => BusinessRegistration::whereMonth('created_at', now()->month)->count(),
            ];
        });
    }

    /**
     * Get pending tasks for the logged-in user
     */
    private function getPendingTasks()
    {
        $user = auth()->user();
        $tasks = collect();
        
        // If user has registration approval permissions
        if ($user->hasPermission('registration.approve') || $user->hasRole('digital_services') || $user->hasRole('admin')) {
            $pendingCount = BusinessRegistration::where('status', 'pending')->count();
            if ($pendingCount > 0) {
                $tasks->push([
                    'title' => 'Pending Business Registrations',
                    'count' => $pendingCount,
                    'url' => route('admin.registrations.index', ['status' => 'pending']),
                    'icon' => 'file-user',
                    'color' => 'warning'
                ]);
            }
        }
        
        // Add more tasks based on user's role
        if ($user->hasPermission('users.view') || $user->hasRole('admin')) {
            $inactiveUsers = User::where('is_active', false)->count();
            if ($inactiveUsers > 0) {
                $tasks->push([
                    'title' => 'Inactive User Accounts',
                    'count' => $inactiveUsers,
                    'url' => route('admin.users.index', ['status' => 'inactive']),
                    'icon' => 'users',
                    'color' => 'danger'
                ]);
            }
        }
        
        return $tasks;
    }

    /**
     * Get recent individual applications (sole traders)
     */
    private function getRecentIndividualApplications()
    {
        return BusinessRegistration::with(['contactDetails', 'personalIdentification'])
            ->where('is_sole_trader', true)
            ->orderBy('created_at', 'desc')
            ->limit(7)
            ->get()
            ->map(function ($registration) {
                $name = $registration->surname && $registration->forename 
                    ? $registration->forename . ' ' . $registration->surname 
                    : $registration->legal_name;
                    
                return [
                    'id' => $registration->id,
                    'name' => $name,
                    'reference_no' => $registration->reference_number,
                    'business_type' => $registration->business_type,
                    'status' => $registration->status,
                    'status_badge' => $this->getStatusBadge($registration->status),
                    'date' => $registration->created_at->format('Y-m-d'),
                    'date_human' => $registration->created_at->diffForHumans(),
                    'tin' => $registration->new_tin ?? $registration->old_tin ?? 'N/A',
                ];
            });
    }

    /**
     * Get recent business applications (companies, partnerships, etc.)
     */
    private function getRecentBusinessApplications()
    {
        return BusinessRegistration::with(['contactDetails', 'directorPartners'])
            ->where('is_sole_trader', false)
            ->orderBy('created_at', 'desc')
            ->limit(7)
            ->get()
            ->map(function ($registration) {
                return [
                    'id' => $registration->id,
                    'business_name' => $registration->legal_name,
                    'reference_no' => $registration->reference_number,
                    'business_type' => $registration->business_type,
                    'registration_number' => $registration->registration_number,
                    'status' => $registration->status,
                    'status_badge' => $this->getStatusBadge($registration->status),
                    'date' => $registration->created_at->format('Y-m-d'),
                    'date_human' => $registration->created_at->diffForHumans(),
                    'tin' => $registration->new_tin ?? $registration->old_tin ?? 'N/A',
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

        $applications = BusinessRegistration::select(
            DB::raw('DATE_FORMAT(created_at, "%b %Y") as month'),
            DB::raw('COUNT(*) as total'),
            DB::raw('SUM(CASE WHEN status = "approved" THEN 1 ELSE 0 END) as approved'),
            DB::raw('SUM(CASE WHEN status = "rejected" THEN 1 ELSE 0 END) as rejected'),
            DB::raw('SUM(CASE WHEN is_sole_trader = 1 THEN 1 ELSE 0 END) as individual'),
            DB::raw('SUM(CASE WHEN is_sole_trader = 0 THEN 1 ELSE 0 END) as business')
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
            'individual' => $months->map(function ($month) use ($applications) {
                return $applications->get($month, ['individual' => 0])['individual'];
            })->values(),
            'business' => $months->map(function ($month) use ($applications) {
                return $applications->get($month, ['business' => 0])['business'];
            })->values(),
        ];
    }

    /**
     * Get pie chart data for application distribution
     */
    private function getPieChartData()
    {
        $statusCounts = BusinessRegistration::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $typeCounts = BusinessRegistration::select(
            DB::raw('CASE WHEN is_sole_trader = 1 THEN "Individual" ELSE "Business" END as type'),
            DB::raw('count(*) as total')
        )
        ->groupBy('type')
        ->get()
        ->keyBy('type');

        return [
            'status' => [
                'labels' => ['Pending', 'Approved', 'Rejected'],
                'data' => [
                    $statusCounts->get('pending', ['total' => 0])['total'],
                    $statusCounts->get('approved', ['total' => 0])['total'],
                    $statusCounts->get('rejected', ['total' => 0])['total'],
                ],
                'colors' => ['#f3b73e', '#2ab57d', '#fd625e'],
            ],
            'type' => [
                'labels' => ['Individual (Sole Trader)', 'Business'],
                'data' => [
                    $typeCounts->get('Individual', ['total' => 0])['total'],
                    $typeCounts->get('Business', ['total' => 0])['total'],
                ],
                'colors' => ['#3b76e1', '#ff8c00'],
            ],
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
        $status = $request->get('status', 'all');
        
        $query = BusinessRegistration::query();
        
        if ($type == 'individual') {
            $query->where('is_sole_trader', true);
        } elseif ($type == 'business') {
            $query->where('is_sole_trader', false);
        }
        
        if ($status != 'all') {
            $query->where('status', $status);
        }
        
        $applications = $query->get();

        $filename = "business_registrations_" . date('Y-m-d_His') . ".csv";
        
        return response()->streamDownload(function () use ($applications) {
            $handle = fopen('php://output', 'w');
            
            // Add headers
            fputcsv($handle, [
                'Reference No', 'Legal Name', 'Business Type', 'Application Type',
                'Old TIN', 'New TIN', 'Registration No', 'Status', 
                'Sole Trader', 'Submitted Date', 'Approved/Rejected Date'
            ]);
            
            // Add data
            foreach ($applications as $app) {
                fputcsv($handle, [
                    $app->reference_number,
                    $app->legal_name,
                    $app->business_type,
                    $app->application_type,
                    $app->old_tin ?? 'N/A',
                    $app->new_tin ?? 'N/A',
                    $app->registration_number ?? 'N/A',
                    $app->status,
                    $app->is_sole_trader ? 'Yes' : 'No',
                    $app->created_at->format('Y-m-d H:i:s'),
                    $app->updated_at->format('Y-m-d H:i:s'),
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
    
    /**
     * Get recent activity (AJAX endpoint)
     */
    public function getRecentActivity(Request $request)
    {
        $recentActivity = BusinessRegistration::select('id', 'reference_number', 'legal_name', 'status', 'created_at', 'updated_at')
            ->orderBy('updated_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($activity) {
                return [
                    'reference_no' => $activity->reference_number,
                    'legal_name' => $activity->legal_name,
                    'status' => $activity->status,
                    'status_badge' => $this->getStatusBadge($activity->status),
                    'action' => $activity->status === 'pending' ? 'Submitted' : ucfirst($activity->status),
                    'time' => $activity->updated_at->diffForHumans(),
                ];
            });
            
        return response()->json([
            'success' => true,
            'data' => $recentActivity,
        ]);
    }
}