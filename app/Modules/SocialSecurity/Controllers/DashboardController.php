<?php

namespace App\Modules\SocialSecurity\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Patients\Enums\AffiliateStatus;
use App\Modules\Patients\Models\Affiliate;
use App\Modules\SocialSecurity\Models\Payroll;
use App\Modules\SocialSecurity\Enums\PayrollStatus;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $today = Carbon::today();
        $next7 = Carbon::today()->addDays(7);
        $currentYear = (int) $today->format('Y');
        $currentMonth = (int) $today->format('n');

        $affiliatesWithProfile = Affiliate::where('status', AffiliateStatus::ACTIVO)
            ->whereHas('socialSecurityProfile')
            ->count();

        $pendingCount = Payroll::whereIn('status', [
            PayrollStatus::PENDING->value,
            PayrollStatus::SETTLED->value,
        ])->count();

        /** Planillas solo PENDING: sin liquidar (para Katherine) */
        $pendingToSettleCount = Payroll::where('status', PayrollStatus::PENDING->value)->count();
        $pendingToSettleThisMonth = Payroll::where('status', PayrollStatus::PENDING->value)
            ->where('year', $currentYear)
            ->where('month', $currentMonth)
            ->count();

        $overdueCount = Payroll::where('status', PayrollStatus::OVERDUE->value)->count();

        $paidThisMonth = Payroll::where('status', PayrollStatus::PAID->value)
            ->whereMonth('paid_at', $today->month)
            ->whereYear('paid_at', $today->year)
            ->count();

        $totalOverdueAmount = (float) Payroll::where('status', PayrollStatus::OVERDUE->value)->sum('total_amount');

        $dueTodayQuery = Payroll::with(['affiliate:id,first_name,second_name,last_name,second_last_name,document_number', 'affiliate.socialSecurityProfile.payer:id,name'])
            ->whereDate('due_date', $today)
            ->whereNotIn('status', [PayrollStatus::PAID->value])
            ->orderBy('due_date');
        $dueTodayCount = (clone $dueTodayQuery)->count();
        $dueToday = (clone $dueTodayQuery)->limit(20)->get();

        $dueNext7Query = Payroll::with(['affiliate:id,first_name,second_name,last_name,second_last_name,document_number', 'affiliate.socialSecurityProfile.payer:id,name'])
            ->whereBetween('due_date', [$today->copy()->addDay(), $next7])
            ->whereNotIn('status', [PayrollStatus::PAID->value])
            ->orderBy('due_date');
        $dueNext7Count = (clone $dueNext7Query)->count();
        $dueNext7 = (clone $dueNext7Query)->limit(30)->get();

        $overdueQuery = Payroll::with(['affiliate:id,first_name,second_name,last_name,second_last_name,document_number', 'affiliate.socialSecurityProfile.payer:id,name'])
            ->where('status', PayrollStatus::OVERDUE->value)
            ->orderBy('due_date');
        $overdueList = (clone $overdueQuery)->limit(20)->get();

        $pendingListQuery = Payroll::with(['affiliate:id,first_name,second_name,last_name,second_last_name,document_number', 'affiliate.socialSecurityProfile.payer:id,name'])
            ->where('status', PayrollStatus::PENDING->value)
            ->where('year', $currentYear)
            ->where('month', $currentMonth)
            ->orderBy('due_date')
            ->orderBy('affiliate_id');
        $pendingListTotal = (clone $pendingListQuery)->count();
        $pendingList = (clone $pendingListQuery)->limit(30)->get();

        $mapPayroll = fn ($p) => [
            'id' => $p->id,
            'year' => $p->year,
            'month' => $p->month,
            'due_date' => $p->due_date?->format('Y-m-d'),
            'status' => $p->status,
            'total_amount' => $p->total_amount !== null ? (float) $p->total_amount : null,
            'affiliate' => $p->affiliate ? [
                'id' => $p->affiliate->id,
                'full_name' => $p->affiliate->full_name ?? trim(collect([$p->affiliate->first_name, $p->affiliate->second_name, $p->affiliate->last_name, $p->affiliate->second_last_name])->filter()->join(' ')),
                'document_number' => $p->affiliate->document_number,
            ] : null,
            'payer_name' => $p->affiliate?->socialSecurityProfile?->payer?->name,
        ];

        $statusLabels = array_column(PayrollStatus::toSelectArray(), 'label', 'value');

        return Inertia::render('SocialSecurity/Dashboard', [
            'metrics' => [
                'affiliates_with_profile' => $affiliatesWithProfile,
                'pending_count' => $pendingCount,
                'pending_to_settle_count' => $pendingToSettleCount,
                'pending_to_settle_this_month' => $pendingToSettleThisMonth,
                'overdue_count' => $overdueCount,
                'paid_this_month' => $paidThisMonth,
                'total_overdue_amount' => round($totalOverdueAmount, 2),
            ],
            'due_today' => $dueToday->map($mapPayroll),
            'due_today_count' => $dueTodayCount,
            'due_next_7' => $dueNext7->map($mapPayroll),
            'due_next_7_count' => $dueNext7Count,
            'overdue_list' => $overdueList->map($mapPayroll),
            'pending_list' => $pendingList->map($mapPayroll),
            'pending_list_total' => $pendingListTotal,
            'status_labels' => $statusLabels,
            'current_year' => $currentYear,
            'current_month' => $currentMonth,
        ]);
    }
}
