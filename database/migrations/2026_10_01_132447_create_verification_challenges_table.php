<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('verification_challenges', function (Blueprint $table) {
            $table->id();

            /*
             * Every challenge belongs to an already-created user account.
             *
             * Registration will create an inactive customer account first,
             * then issue its verification challenge.
             */
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            /*
             * Non-secret public identifier used by the verification flow.
             * Authorization never relies on this identifier alone.
             */
            $table->string('public_id', 40)
                ->unique();

            /*
             * Supported purposes will initially be:
             *
             * registration
             * employee_activation
             * password_reset
             */
            $table->string('purpose', 40);

            /*
             * Delivery channel:
             *
             * email
             * sms
             */
            $table->string('channel', 20);

            /*
             * Snapshot of the destination used when this code was issued.
             *
             * Keeping the destination on the challenge prevents later
             * account-profile changes from silently changing where an
             * already-issued OTP is expected to have been delivered.
             */
            $table->string('destination', 255);

            /*
             * Raw OTP values are never stored.
             *
             * Only a one-way hash of the generated code is persisted.
             */
            $table->string('code_hash', 255);

            /*
             * Number of failed verification attempts against the current
             * issued code.
             */
            $table->unsignedTinyInteger('attempts')
                ->default(0);

            /*
             * Number of replacement codes issued for this challenge.
             * This gives us an auditable limit in addition to HTTP
             * rate limiting and resend cooldown enforcement.
             */
            $table->unsignedTinyInteger('resend_count')
                ->default(0);

            /*
             * Used to enforce the resend cooldown without trusting
             * client-side timers.
             */
            $table->timestamp('last_sent_at');

            /*
             * OTP expiration is enforced server-side.
             */
            $table->timestamp('expires_at');

            /*
             * OTP verification and workflow completion are separate states.
             *
             * Example:
             * - password-reset OTP becomes verified;
             * - user enters a new password;
             * - challenge becomes consumed.
             */
            $table->timestamp('verified_at')
                ->nullable();

            /*
             * Once consumed, the challenge cannot be reused.
             */
            $table->timestamp('consumed_at')
                ->nullable();

            $table->timestamps();

            /*
             * Common lookup paths for active challenges.
             */
            $table->index(
                ['user_id', 'purpose'],
                'verification_challenges_user_purpose_index'
            );

            $table->index(
                ['purpose', 'channel'],
                'verification_challenges_purpose_channel_index'
            );

            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('verification_challenges');
    }
};
