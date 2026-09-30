<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Services\DisconnectionNoticeService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Throwable;

class GenerateDisconnectionNotices extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'billing:generate-disconnection-notices';

    /**
     * The console command description.
     */
    protected $description = 'Trigger due disconnection notices for overdue invoices with outstanding balances';

    /**
     * Execute the console command.
     */
    public function handle(
        DisconnectionNoticeService $disconnectionNoticeService
    ): int {
        $evaluationDate = CarbonImmutable::now(
            config('app.timezone')
        )->startOfDay();

        $triggered = 0;
        $skipped = 0;
        $failed = 0;

        Invoice::query()
            ->where('status', 'overdue')
            ->whereNotNull('disconnection_notice_date')
            ->whereDate(
                'disconnection_notice_date',
                '<=',
                $evaluationDate->toDateString()
            )
            ->whereNull('disconnection_notice_triggered_at')
            ->orderBy('id')
            ->chunkById(
                100,
                function ($invoices) use (
                    $disconnectionNoticeService,
                    &$triggered,
                    &$skipped,
                    &$failed
                ): void {
                    foreach ($invoices as $invoice) {
                        try {
                            $result = $disconnectionNoticeService->trigger(
                                invoice: $invoice
                            );

                            if ($result['triggered']) {
                                $triggered++;

                                $this->line(
                                    sprintf(
                                        'Triggered notice for invoice %s. Outstanding balance: %s.',
                                        $result['invoice']->invoice_number,
                                        $result['balance_amount']
                                    )
                                );

                                continue;
                            }

                            $skipped++;

                            $this->line(
                                sprintf(
                                    'Skipped invoice %s: %s.',
                                    $invoice->invoice_number,
                                    $result['reason']
                                )
                            );
                        } catch (Throwable $exception) {
                            $failed++;

                            report($exception);

                            $this->error(
                                sprintf(
                                    'Failed invoice %s: %s',
                                    $invoice->invoice_number,
                                    $exception->getMessage()
                                )
                            );
                        }
                    }
                }
            );

        $this->newLine();

        $this->info(
            sprintf(
                'Disconnection notice generation complete: %d triggered, %d skipped, %d failed.',
                $triggered,
                $skipped,
                $failed
            )
        );

        return $failed > 0
            ? self::FAILURE
            : self::SUCCESS;
    }
}
