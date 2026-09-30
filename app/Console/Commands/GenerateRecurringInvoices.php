<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Services\BillingCycleService;
use App\Services\InvoiceGenerationService;
use DomainException;
use Illuminate\Console\Command;
use Throwable;

class GenerateRecurringInvoices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'billing:generate-recurring-invoices';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description =
        'Generate all due recurring invoices for active subscriptions.';

    /**
     * Execute the console command.
     */
    public function handle(
        InvoiceGenerationService $invoiceGenerationService,
        BillingCycleService $billingCycleService
    ): int {
        $today = now()->startOfDay();

        $createdCount = 0;
        $existingCount = 0;
        $skippedCount = 0;
        $errorCount = 0;

        Subscription::query()
            ->where('status', 'active')
            ->whereNotNull('start_date')
            ->whereDate('start_date', '<=', $today->toDateString())
            ->orderBy('id')
            ->chunkById(
                100,
                function ($subscriptions) use (
                    $today,
                    $invoiceGenerationService,
                    $billingCycleService,
                    &$createdCount,
                    &$existingCount,
                    &$skippedCount,
                    &$errorCount
                ): void {
                    foreach ($subscriptions as $subscription) {
                        $cycleNumber = 1;

                        while (true) {
                            $dates = $billingCycleService->datesForCycle(
                                $subscription->start_date,
                                $cycleNumber
                            );

                            if (
                                $dates['billing_date']
                                    ->copy()
                                    ->startOfDay()
                                    ->gt($today)
                            ) {
                                break;
                            }

                            try {
                                $result =
                                    $invoiceGenerationService
                                        ->generateForCycle(
                                            $subscription,
                                            $cycleNumber
                                        );

                                if ($result['created']) {
                                    $createdCount++;

                                    $this->line(sprintf(
                                        'Created %s for subscription #%d (%s).',
                                        $result['invoice']->invoice_number,
                                        $subscription->id,
                                        $dates['billing_date']
                                            ->format('Y-m-d')
                                    ));
                                } else {
                                    $existingCount++;
                                }
                            } catch (DomainException $exception) {
                                $skippedCount++;

                                $this->warn(sprintf(
                                    'Skipped subscription #%d cycle %d: %s',
                                    $subscription->id,
                                    $cycleNumber,
                                    $exception->getMessage()
                                ));

                                /*
                                 * Eligibility failures such as a disconnected
                                 * subscriber apply to the subscription as a
                                 * whole, so there is no reason to test later
                                 * cycles during this command run.
                                 */
                                break;
                            } catch (Throwable $exception) {
                                $errorCount++;

                                report($exception);

                                $this->error(sprintf(
                                    'Failed subscription #%d cycle %d: %s',
                                    $subscription->id,
                                    $cycleNumber,
                                    $exception->getMessage()
                                ));

                                /*
                                 * Stop processing this subscription so an
                                 * unexpected failure does not produce later
                                 * cycles while an earlier cycle failed.
                                 */
                                break;
                            }

                            $cycleNumber++;
                        }
                    }
                }
            );

        $this->newLine();

        $this->info(sprintf(
            'Recurring billing complete: %d created, %d already existed, %d skipped, %d failed.',
            $createdCount,
            $existingCount,
            $skippedCount,
            $errorCount
        ));

        return $errorCount > 0
            ? self::FAILURE
            : self::SUCCESS;
    }
}
