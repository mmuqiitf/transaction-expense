<?php

namespace App\Http\Controllers;

use App\Http\Requests\AuditLogIndexRequest;
use App\Http\Resources\AuditLogResource;
use App\Models\Category;
use App\Models\ChartOfAccount;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;

class AuditLogController extends Controller
{
    public function index(AuditLogIndexRequest $request): Response
    {
        $search = $request->searchTerm();
        $event = $request->filter('event');
        $subjectType = $request->filter('subject_type');
        $causerId = $request->filter('causer_id');
        $from = $request->filter('from');
        $to = $request->filter('to');

        $sortColumns = [
            'created_at' => 'created_at',
            'id' => 'id',
            'event' => 'event',
            'log_name' => 'log_name',
        ];

        $activities = Activity::query()
            ->with('causer')
            ->when($search, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('description', 'like', "%{$search}%")
                        ->orWhere('log_name', 'like', "%{$search}%")
                        ->orWhere('event', 'like', "%{$search}%")
                        ->orWhere('subject_type', 'like', "%{$search}%")
                        ->orWhereHasMorph('causer', [User::class], function (Builder $userQuery) use ($search): void {
                            $userQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->when(is_string($event) && $event !== '', fn (Builder $query): Builder => $query->where('event', $event))
            ->when(is_string($subjectType) && $subjectType !== '', fn (Builder $query): Builder => $query->where('subject_type', $subjectType))
            ->when(is_numeric($causerId), fn (Builder $query): Builder => $query->where('causer_id', (int) $causerId))
            ->when(is_string($from), fn (Builder $query): Builder => $query->whereDate('created_at', '>=', $from))
            ->when(is_string($to), fn (Builder $query): Builder => $query->whereDate('created_at', '<=', $to))
            ->orderBy($sortColumns[$request->sortColumn() ?? 'created_at'] ?? 'created_at', $request->sortDirection())
            ->paginate($request->perPage())
            ->withQueryString();

        $subjectTypes = [
            ['value' => Transaction::class, 'label' => 'Transaction'],
            ['value' => Category::class, 'label' => 'Category'],
            ['value' => ChartOfAccount::class, 'label' => 'Chart of Account'],
            ['value' => User::class, 'label' => 'User'],
        ];

        $events = ['created', 'updated', 'deleted'];

        $users = User::query()
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return Inertia::render('audit-logs/Index', [
            'logs' => AuditLogResource::collection($activities),
            'subjectTypes' => $subjectTypes,
            'events' => $events,
            'users' => $users,
            'filters' => [
                'search' => $search,
                'event' => $event,
                'subject_type' => $subjectType,
                'causer_id' => $causerId,
                'from' => $from,
                'to' => $to,
                'sort' => $request->sortColumn(),
                'direction' => $request->sortDirection(),
            ],
        ]);
    }
}
