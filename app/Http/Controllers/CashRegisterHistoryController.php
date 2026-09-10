<?php

namespace App\Http\Controllers;

use App\Enums\CashRegisterStatus;
use App\Models\CashRegister;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CashRegisterHistoryController extends Controller
{
    public function __invoke(Request $request): Response
    {
        Gate::authorize('viewAny', CashRegister::class);

        $from = $request->date('from');
        $to = $request->date('to');

        $registers = CashRegister::query()
            ->where('status', CashRegisterStatus::Closed)
            ->with(['opener:id,name', 'closer:id,name'])
            ->when($from, fn (Builder $query) => $query->whereDate('closed_at', '>=', $from))
            ->when($to, fn (Builder $query) => $query->whereDate('closed_at', '<=', $to))
            ->latest('closed_at')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (CashRegister $register): array => [
                'id' => $register->id,
                'number' => $register->number,
                'opened_by' => $register->opener->name,
                'closed_by' => $register->closer?->name,
                'opening_amount' => $register->opening_amount,
                'expected_cash_amount' => $register->expected_cash_amount,
                'counted_cash_amount' => $register->counted_cash_amount,
                'difference' => $register->difference,
                'opened_at' => $register->opened_at->toIso8601String(),
                'closed_at' => $register->closed_at?->toIso8601String(),
                'closing_notes' => $register->closing_notes,
            ]);

        return Inertia::render('cashier/History', [
            'registers' => $registers,
            'filters' => ['from' => $from?->toDateString(), 'to' => $to?->toDateString()],
        ]);
    }
}
