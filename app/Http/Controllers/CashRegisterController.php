<?php

namespace App\Http\Controllers;

use App\Enums\CashRegisterStatus;
use App\Enums\CashTransactionCategory;
use App\Enums\CashTransactionType;
use App\Enums\PaymentMethod;
use App\Models\CashRegister;
use App\Models\CashTransaction;
use App\Models\Sale;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CashRegisterController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', CashRegister::class);

        $register = CashRegister::query()->open()->with('opener:id,name')->first();
        $lastClosed = CashRegister::query()
            ->where('status', CashRegisterStatus::Closed)
            ->with(['opener:id,name', 'closer:id,name'])
            ->latest('closed_at')
            ->first();

        return Inertia::render('cashier/Index', [
            'register' => $register === null ? null : [
                ...$this->registerData($register),
                'summary' => $register->summary(),
                'transactions' => $register->transactions()
                    ->with('user:id,name')
                    ->latest('occurred_at')->latest('id')
                    ->limit(60)
                    ->get()
                    ->map(fn (CashTransaction $transaction): array => $this->transactionData($transaction))
                    ->all(),
            ],
            'lastClosed' => $lastClosed === null ? null : $this->registerData($lastClosed),
            'options' => [
                'payment_methods' => $this->paymentMethodOptions(),
                'expense_categories' => $this->manualCategoryOptions(),
            ],
            'can' => [
                'open' => Gate::allows('open', CashRegister::class),
                'close' => $register !== null && Gate::allows('close', $register),
                'register_movement' => $register !== null && Gate::allows('registerTransaction', $register),
                'sell' => Gate::allows('create', Sale::class),
            ],
        ]);
    }

    /** @return array<string, mixed> */
    private function registerData(CashRegister $register): array
    {
        return [
            'id' => $register->id,
            'number' => $register->number,
            'status' => $register->status->value,
            'status_label' => $register->status->label(),
            'opened_by' => $register->opener->name,
            'closed_by' => $register->closer?->name,
            'opening_amount' => $register->opening_amount,
            'expected_cash_amount' => $register->expected_cash_amount,
            'counted_cash_amount' => $register->counted_cash_amount,
            'difference' => $register->difference,
            'opened_at' => $register->opened_at->toIso8601String(),
            'closed_at' => $register->closed_at?->toIso8601String(),
            'opening_notes' => $register->opening_notes,
            'closing_notes' => $register->closing_notes,
        ];
    }

    /** @return array<string, mixed> */
    private function transactionData(CashTransaction $transaction): array
    {
        return [
            'id' => $transaction->id,
            'type' => $transaction->type->value,
            'type_label' => $transaction->type->label(),
            'category' => $transaction->category->value,
            'category_label' => $transaction->category->label(),
            'payment_method' => $transaction->payment_method->value,
            'payment_method_label' => $transaction->payment_method->label(),
            'amount' => $transaction->amount,
            'description' => $transaction->description,
            'reference' => $transaction->reference,
            'user_name' => $transaction->user->name,
            'occurred_at' => $transaction->occurred_at->toIso8601String(),
        ];
    }

    /** @return array<int, array{value: string, label: string}> */
    private function paymentMethodOptions(): array
    {
        return array_map(
            fn (PaymentMethod $method): array => ['value' => $method->value, 'label' => $method->label()],
            PaymentMethod::cases(),
        );
    }

    /** @return array<int, array{value: string, label: string, types: array<int, string>}> */
    private function manualCategoryOptions(): array
    {
        $options = [];

        foreach (CashTransactionCategory::cases() as $category) {
            if (! $category->isManuallySelectable()) {
                continue;
            }

            $options[] = [
                'value' => $category->value,
                'label' => $category->label(),
                'types' => array_map(fn (CashTransactionType $type): string => $type->value, $category->allowedTypes()),
            ];
        }

        return $options;
    }
}
