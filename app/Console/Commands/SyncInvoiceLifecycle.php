<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Services\InvoiceLifecycleService;
use Illuminate\Console\Command;
use Throwable;

class SyncInvoiceLifecycle extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'billing:sync-invoice-lifecycle';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description =
        'Synchronize issued, partially paid, and overdue invoice statuses.';

    /**
     * Execute the console command.
     */
    public function handle(
        InvoiceLifecycleService $invoiceLifecycleService
    ): int {
        $changedCount = 0;
        $unchangedCount = 0;
        $errorCount = 0;

        Invoice::query()
            ->whereIn('status', [
                'issued',
                'partially_paid',
                'overdue',
            ])
            ->orderBy('id')
            ->chunkById(
                100,
                function ($invoices) use (
                    $invoiceLifecycleService,
                    &$changedCount,
                    &$unchangedCount,
                    &$errorCount
                ): void {
                    foreach ($invoices as $invoice) {
                        try {
                            $result =
                                $invoiceLifecycleService->sync(
                                    $invoice
                                );

                            if ($result['changed']) {
                                $changedCount++;

                                $this->line(sprintf(
                                    '%s: %s -> %s (balance %s).',
                                    $invoice->invoice_number,
                                    $result['old_status'],
                                    $result['new_status'],
                                    $result['balance_amount']
                                ));
                            } else {
                                $unchangedCount++;
                            }
                        } catch (Throwable $exception) {
                            $errorCount++;

                            report($exception);

                            $this->error(sprintf(
                                'Failed invoice #%d (%s): %s',
                                $invoice->id,
                                $invoice->invoice_number,
                                $exception->getMessage()
                            ));
                        }
                    }
                }
            );

        $this->newLine();

        $this->info(sprintf(
            'Invoice lifecycle sync complete: %d changed, %d unchanged, %d failed.',
            $changedCount,
            $unchangedCount,
            $errorCount
        ));

        return $errorCount > 0
            ? self::FAILURE
            : self::SUCCESS;
    }
}
