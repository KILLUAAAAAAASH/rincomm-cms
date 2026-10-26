{{-- This shows RouterOS PPPoE session status, not subscription or internet quality. --}}
<div class="border-t border-neutral-200 px-5 py-4 dark:border-neutral-800 sm:px-6"
     data-live-pppoe-status
     data-status-url="{{ route('customer.connection-status.show') }}">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-neutral-600 dark:text-neutral-400">
                Live PPPoE Connection
            </p>
            <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-400" data-live-pppoe-description>
                Router status has not been verified.
            </p>
        </div>
        <span role="status" aria-live="polite" data-live-pppoe-label data-state="unavailable"
              class="inline-flex items-center rounded-md border border-neutral-300 bg-white px-3 py-1.5 text-sm font-semibold text-neutral-600 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-300
                     data-[state=online]:border-[#008080]/30 data-[state=online]:text-[#008080]
                     data-[state=offline]:border-red-300 data-[state=offline]:text-red-700
                     dark:data-[state=online]:text-[#5EEAD4] dark:data-[state=offline]:text-red-300">
            Unavailable
        </span>
    </div>
</div>
<script src="{{ asset('js/customer-live-connection.js') }}" defer></script>
