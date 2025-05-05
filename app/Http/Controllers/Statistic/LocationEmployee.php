<?php

namespace App\Http\Controllers\Statistic;

use App\Models\Asset;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\InspectionSheet;
use App\Models\JobCard;
use App\Models\Ticket;
use Carbon\Carbon;

class LocationEmployee extends Controller
{
      // chart statistics for support agent dashboard
      public function dashboardLocationEmployee(Request $request)
      {
          $filter    = $request->query('filter', 'weekly');
          $startDate = $this->getStartDate($filter);
          $endDate   = Carbon::now()->endOfDay();

          $ticketStats = $this->getStatusStats(
              Ticket::class, 'ticket_status',
              ['New'],
              ['Assigned', 'Inspection Sheet', 'Awaiting Purchase Order', 'Job Card Created'],
              ['Completed'],
              $startDate,
              $endDate
          );

          // For Inspection Sheets:
          $inspectionStats = $this->getStatusStats(
              InspectionSheet::class,
              'status',
              ['New'],
              ['Arrived in Location', 'Contract with user', 'View the problem', 'Solve the problem'],
              ['Completed'],
              $startDate,
              $endDate
          );

          // For Job Cards:
          $jobCardStats = $this->getStatusStats(
              JobCard::class,
              'job_status',
              ['New'],
              ['Assigned', 'In Progress', 'On hold', 'To be allocated', 'Awaiting courier', 'Collected by courier', 'Parts required', 'Picking', 'To be invoiced', 'Invoiced'],
              ['Completed'],
              $startDate,
              $endDate
          );

          return response()->json([
              'filter'            => $filter,
              'ticket_status'     => $ticketStats,
              'inspections'       => $inspectionStats,
              'job_card_progress' => $jobCardStats,
          ]);
      }

      // Returns the start date based on filter
      private function getStartDate($filter)
      {
          return match ($filter) {
              'monthly' => Carbon::now()->subMonth()->startOfMonth(), // Start of last month
              'yearly' => Carbon::now()->subYear()->startOfYear(),    // Start of last year
              'weekly' => Carbon::now()->subWeek()->startOfWeek(),    // Start of last week
              default => Carbon::now()->startOfWeek(),                // Default is current week
          };
      }

      private function getStatusStats($model, $column, $newStatuses, $inProgressStatuses, $completedStatuses, $startDate, $endDate)
      {
          // Total count
          $total = $model::whereBetween('created_at', [$startDate, $endDate])->count();

          // Count for each group
          $newCount        = $model::whereIn($column, $newStatuses)->whereBetween('created_at', [$startDate, $endDate])->count();
          $inProgressCount = $model::whereIn($column, $inProgressStatuses)->whereBetween('created_at', [$startDate, $endDate])->count();
          $completedCount  = $model::whereIn($column, $completedStatuses)->whereBetween('created_at', [$startDate, $endDate])->count();

          // Helper to calculate percentage
          $calcPercentage = fn($count) => $total ? number_format(($count / $total) * 100, 2) : 0;

          return [
              'New'         => ['count' => $newCount, 'percentage' => $calcPercentage($newCount)],
              'In-Progress' => ['count' => $inProgressCount, 'percentage' => $calcPercentage($inProgressCount)],
              'Completed'   => ['count' => $completedCount, 'percentage' => $calcPercentage($completedCount)],
          ];
      }
}
