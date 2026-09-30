<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Normalize existing service application numbers into the
     * human-readable APP-YYYY-#### format.
     */
    public function up(): void
    {
        DB::table('service_applications')
            ->select([
                'id',
                'application_number',
                'submitted_at',
                'created_at',
            ])
            ->orderBy('id')
            ->chunkById(100, function ($applications): void {
                foreach ($applications as $application) {
                    /*
                     * Leave applications alone if they are already using
                     * the new human-readable format.
                     */
                    if (
                        preg_match(
                            '/^APP-\d{4}-\d{4,}$/',
                            (string) $application->application_number
                        )
                    ) {
                        continue;
                    }

                    /*
                     * Prefer the submission year because that represents
                     * when the application officially entered the workflow.
                     *
                     * Fall back to created_at for older/incomplete records.
                     */
                    $referenceDate =
                        $application->submitted_at
                        ?? $application->created_at;

                    $year = $referenceDate
                        ? (int) date(
                            'Y',
                            strtotime((string) $referenceDate)
                        )
                        : (int) date('Y');

                    $applicationNumber = sprintf(
                        'APP-%04d-%04d',
                        $year,
                        $application->id
                    );

                    DB::table('service_applications')
                        ->where('id', $application->id)
                        ->update([
                            'application_number' =>
                            $applicationNumber,
                        ]);
                }
            });
    }

    /**
     * The original ULID-based application references cannot be
     * reconstructed safely after normalization.
     *
     * We intentionally leave the normalized business references in
     * place during rollback rather than inventing replacement values.
     */
    public function down(): void
    {
        //
    }
};
