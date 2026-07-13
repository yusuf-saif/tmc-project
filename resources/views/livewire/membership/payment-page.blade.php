@push('backButton')
    <a href="{{ route('home') }}" class="back-btn">&larr; Home</a>
@endpush

<div class="mx-auto flex min-h-screen w-full max-w-2xl items-center justify-center px-6 py-12"
     x-data="{ polling: null }"
     x-init="
        polling = setInterval(() => {
            $wire.checkPaymentStatus()
                .then(() => {
                    if ($wire.paymentStatus === 'member') {
                        clearInterval(polling);
                        window.location.href = '/home';
                    }
                });
        }, 5000);

        $wire.on('redirect-home', () => {
            clearInterval(polling);
            window.location.href = '/home';
        });
     "
>
    <section class="w-full max-w-xl rounded bg-white p-10 shadow-[0_4px_24px_rgba(0,0,0,0.06)]">
        <img src="{{ asset('images/img2.png') }}" alt="The Muhsinat Club" class="mx-auto mb-6 w-36 max-w-full object-contain">

        @if (session('message'))
            <div class="mb-6 rounded-sm bg-teal-lt p-4 text-center text-sm text-teal-dk">
                {{ session('message') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-6 rounded-sm bg-red-50 p-4 text-sm text-red-700">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        @if (session('error'))
            <div class="mb-6 rounded-sm bg-red-50 p-4 text-sm text-red-700">
                {{ session('error') }}
            </div>
        @endif

        {{-- STATE: onboarding — show payment options --}}
        @if ($status === 'onboarding' || $status === 'active' || $status === 'suspended')
            <h1 class="font-display text-4xl leading-none text-teal-dk">Complete Your Payment</h1>

            <div class="mt-6 space-y-4">
                @if ($membershipId)
                    <div class="rounded-sm bg-teal-lt p-4 text-center">
                        <p class="text-[11px] font-semibold uppercase tracking-[1.5px] text-teal">Your Membership ID</p>
                        <p class="mt-1 font-display text-3xl text-teal-dk">{{ $membershipId }}</p>
                    </div>
                @endif

                {{-- Billing cycle selector --}}
                <div class="space-y-3">
                    <p class="text-[11px] font-semibold uppercase tracking-[1.5px] text-gold">Billing Cycle</p>
                    <div class="grid grid-cols-3 gap-2">
                        @foreach ($billingOptions as $key => $option)
                            @php($selected = $billingCycle === $key)
                            <div wire:click="selectBillingCycle('{{ $key }}')"
                                 class="cursor-pointer rounded-lg p-3 text-center transition"
                                 style="border: 2px solid {{ $selected ? '#1A6B72' : '#E2E8F0' }}; background: {{ $selected ? '#D6EDEF' : '#FFFFFF' }};">
                                <p class="font-display text-lg font-semibold text-teal-dk">{{ $option['label'] }}</p>
                                <p class="text-xs font-medium text-teal-dk">₦{{ number_format($option['price']) }}</p>
                                <p class="text-[10px] text-ink-soft">{{ $option['interval'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="rounded-sm bg-ivory p-5 text-center">
                    <p class="text-[11px] font-semibold uppercase tracking-[1.5px] text-gold">Amount Due</p>
                    <p class="mt-1 font-display text-4xl text-teal-dk">₦{{ number_format($finalAmountDue) }}</p>
                    @if ($paymentMethod === 'card' && $applyCoins && $redemption['eligible'])
                        <p class="mt-1 text-xs text-teal">₦{{ number_format($amountDue - $finalAmountDue) }} covered by {{ $redemption['coins_to_use'] }} Jannah Coins</p>
                    @endif
                    <p class="mt-1 text-sm text-ink-soft">{{ ucfirst($billingCycle) }} billing</p>
                </div>

                {{-- Payment method toggle --}}
                @if ($paymentsEnabled)
                    <div class="space-y-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[1.5px] text-gold">Payment Method</p>
                        <div class="grid grid-cols-2 gap-2">
                            <button type="button" wire:click="$set('paymentMethod', 'card')"
                                    class="rounded-lg p-3 text-center transition"
                                    style="border: 2px solid {{ $paymentMethod === 'card' ? '#1A6B72' : '#E2E8F0' }}; background: {{ $paymentMethod === 'card' ? '#D6EDEF' : '#FFFFFF' }};">
                                <p class="text-sm font-semibold text-teal-dk">Pay with Card</p>
                                <p class="text-[10px] text-ink-soft">via Paystack</p>
                            </button>
                            <button type="button" wire:click="$set('paymentMethod', 'bank_transfer')"
                                    class="rounded-lg p-3 text-center transition"
                                    style="border: 2px solid {{ $paymentMethod === 'bank_transfer' ? '#1A6B72' : '#E2E8F0' }}; background: {{ $paymentMethod === 'bank_transfer' ? '#D6EDEF' : '#FFFFFF' }};">
                                <p class="text-sm font-semibold text-teal-dk">Bank Transfer</p>
                                <p class="text-[10px] text-ink-soft">Manual payment</p>
                            </button>
                        </div>
                    </div>
                @endif

                {{-- Jannah Coins (only for card payments) --}}
                @if ($paymentsEnabled && $paymentMethod === 'card' && $redemption['eligible'])
                    <div class="rounded-sm border border-teal-lt bg-teal-lt/30 p-4">
                        <label class="flex cursor-pointer items-center gap-3">
                            <input type="checkbox" wire:model.live="applyCoins" class="h-4 w-4 rounded border-teal text-teal focus:ring-teal">
                            <div>
                                <p class="text-sm font-medium text-teal-dk">Apply your Jannah Coins</p>
                                <p class="text-xs text-ink-soft">
                                    Save ₦{{ number_format($redemption['discount_kobo'] / 100) }} using {{ $redemption['coins_to_use'] }} coins
                                    (max {{ app(\App\Services\CoinsService::class)::maxRedemptionPercent() }}% of this payment)
                                </p>
                            </div>
                        </label>
                    </div>
                @endif

                {{-- CARD PAYMENT SECTION --}}
                @if ($paymentsEnabled && $paymentMethod === 'card')
                    <div class="rounded-sm border border-teal bg-white p-5 text-center">
                        <h3 class="text-sm font-semibold text-teal-dk">Pay Online with Card</h3>
                        <p class="mt-1 text-xs text-ink-soft">Secure payment via Paystack</p>
                        <button type="button" wire:click="redirectToPaystack" wire:loading.attr="disabled"
                                class="mt-4 w-full rounded-sm bg-teal px-4 py-3 text-sm font-medium text-white transition hover:bg-teal-dk disabled:opacity-50">
                            <span wire:loading.remove wire:target="redirectToPaystack">Pay ₦{{ number_format($finalAmountDue) }} with Card</span>
                            <span wire:loading wire:target="redirectToPaystack">Connecting to Paystack...</span>
                        </button>
                        @error('paystack') <p class="mt-2 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <p class="text-center text-xs text-ink-soft">
                        After payment, you'll be redirected back here while we confirm your transaction.
                    </p>

                {{-- BANK TRANSFER SECTION --}}
                @elseif ($paymentsEnabled && $paymentMethod === 'bank_transfer')
                    <div class="rounded-sm border border-gold bg-ivory p-5">
                        <h3 class="text-sm font-semibold text-teal-dk">Bank Transfer Details</h3>
                        <pre class="mt-3 whitespace-pre-wrap font-body text-sm font-light leading-7 text-ink-md">{{ $bankDetails }}</pre>
                    </div>

                    <div class="rounded-sm border border-teal bg-white p-5 text-center">
                        <p class="text-sm font-medium text-teal-dk">After making the transfer, click the button below</p>
                        <p class="mt-1 text-xs text-ink-soft">Your membership will be activated once the admin confirms your payment.</p>
                        <button type="button" wire:click="submitBankTransfer" wire:loading.attr="disabled"
                                class="mt-4 w-full rounded-sm bg-teal px-4 py-3 text-sm font-medium text-white transition hover:bg-teal-dk disabled:opacity-50">
                            <span wire:loading.remove wire:target="submitBankTransfer">I've Made the Transfer</span>
                            <span wire:loading wire:target="submitBankTransfer">Submitting...</span>
                        </button>
                    </div>

                {{-- MANUAL PAYMENT NOTICE (payments disabled) --}}
                @elseif (!$paymentsEnabled)
                    <div class="rounded-sm border border-gold bg-ivory p-5">
                        <h3 class="text-sm font-semibold text-teal-dk">Manual Payment</h3>
                        <p class="mt-2 text-xs text-ink-soft">Online payment is temporarily unavailable. Please pay via bank transfer using the details below. Payment will be confirmed by an admin.</p>
                        @if (config('payments.manual_instructions.bank') || config('payments.manual_instructions.account_name') || config('payments.manual_instructions.account_number'))
                            <div class="mt-3 space-y-1">
                                @if (config('payments.manual_instructions.bank'))
                                    <p class="text-sm text-ink-md"><span class="font-medium">Bank:</span> {{ config('payments.manual_instructions.bank') }}</p>
                                @endif
                                @if (config('payments.manual_instructions.account_name'))
                                    <p class="text-sm text-ink-md"><span class="font-medium">Account Name:</span> {{ config('payments.manual_instructions.account_name') }}</p>
                                @endif
                                @if (config('payments.manual_instructions.account_number'))
                                    <p class="text-sm text-ink-md"><span class="font-medium">Account Number:</span> {{ config('payments.manual_instructions.account_number') }}</p>
                                @endif
                            </div>
                        @else
                            <pre class="mt-3 whitespace-pre-wrap font-body text-sm font-light leading-7 text-ink-md">{{ $bankDetails }}</pre>
                        @endif
                    </div>

                    <div class="rounded-sm border border-teal bg-white p-5 text-center">
                        <p class="text-sm font-medium text-teal-dk">After making the transfer, click the button below</p>
                        <p class="mt-1 text-xs text-ink-soft">Your membership will be activated once the admin confirms your payment.</p>
                        <button type="button" wire:click="submitBankTransfer" wire:loading.attr="disabled"
                                class="mt-4 w-full rounded-sm bg-teal px-4 py-3 text-sm font-medium text-white transition hover:bg-teal-dk disabled:opacity-50">
                            <span wire:loading.remove wire:target="submitBankTransfer">I've Made the Transfer</span>
                            <span wire:loading wire:target="submitBankTransfer">Submitting...</span>
                        </button>
                    </div>
                @endif
            </div>

        {{-- STATE: pending_verification — awaiting admin confirmation --}}
        @elseif ($status === 'pending_verification')
            <h1 class="font-display text-4xl leading-none text-teal-dk">Payment Submitted</h1>

            <div class="mt-6 space-y-4">
                <div class="rounded-sm bg-ivory p-8 text-center">
                    <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-teal-lt">
                        <svg class="h-8 w-8 text-teal" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <p class="text-sm text-ink-md">Your bank transfer is being reviewed.</p>
                    <p class="mt-2 text-xs text-ink-soft">We'll notify you once the admin confirms your payment. You'll be redirected automatically.</p>
                    <button type="button" wire:click="checkPaymentStatus"
                            class="mt-4 text-xs font-medium text-teal underline transition hover:text-teal-dk">
                        Check Status Manually
                    </button>
                </div>

                @if ($membershipId)
                    <div class="rounded-sm bg-teal-lt p-4 text-center">
                        <p class="text-[11px] font-semibold uppercase tracking-[1.5px] text-teal">Membership ID</p>
                        <p class="mt-1 font-display text-3xl text-teal-dk">{{ $membershipId }}</p>
                    </div>
                @endif

                <div class="rounded-sm bg-ivory p-5 text-center">
                    <p class="text-[11px] font-semibold uppercase tracking-[1.5px] text-gold">Amount Due</p>
                    <p class="mt-1 font-display text-4xl text-teal-dk">₦{{ number_format($finalAmountDue) }}</p>
                    <p class="mt-1 text-sm text-ink-soft">{{ ucfirst($billingCycle) }} billing</p>
                </div>
            </div>

        {{-- STATE: payment_processing — show spinner with polling --}}
        @elseif ($status === 'payment_processing')
            <h1 class="font-display text-4xl leading-none text-teal-dk">Processing Payment</h1>

            <div class="mt-6 space-y-4">
                <div class="rounded-sm bg-ivory p-8 text-center">
                    <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-teal-lt">
                        <svg class="h-8 w-8 animate-spin text-teal" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                    <p class="text-sm text-ink-md">We are confirming your payment. This may take a moment.</p>
                    <p class="mt-2 text-xs text-ink-soft">You will be automatically redirected once payment is confirmed.</p>
                    <button type="button" wire:click="checkPaymentStatus"
                            class="mt-4 text-xs font-medium text-teal underline transition hover:text-teal-dk">
                        Check Status Manually
                    </button>
                </div>

                @if ($membershipId)
                    <div class="rounded-sm bg-teal-lt p-4 text-center">
                        <p class="text-[11px] font-semibold uppercase tracking-[1.5px] text-teal">Membership ID</p>
                        <p class="mt-1 font-display text-3xl text-teal-dk">{{ $membershipId }}</p>
                    </div>
                @endif
            </div>

        {{-- STATE: payment_failed --}}
        @elseif ($status === 'payment_failed')
            <h1 class="font-display text-4xl leading-none text-red-600">Payment Failed</h1>

            <div class="mt-6 space-y-4">
                <div class="rounded-sm bg-red-50 p-5 text-center">
                    <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-red-100">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-red-500">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="15" y1="9" x2="9" y2="15"/>
                            <line x1="9" y1="9" x2="15" y2="15"/>
                        </svg>
                    </div>
                    <p class="text-sm text-ink-md">Your payment could not be processed.</p>
                    <p class="mt-1 text-xs text-ink-soft">You can try again with a different card. If the issue persists, contact support.</p>
                </div>

                @if ($membershipId)
                    <div class="rounded-sm bg-teal-lt p-4 text-center">
                        <p class="text-[11px] font-semibold uppercase tracking-[1.5px] text-teal">Membership ID</p>
                        <p class="mt-1 font-display text-3xl text-teal-dk">{{ $membershipId }}</p>
                    </div>
                @endif

                <div class="rounded-sm bg-ivory p-5 text-center">
                    <p class="text-[11px] font-semibold uppercase tracking-[1.5px] text-gold">Amount Due</p>
                    <p class="mt-1 font-display text-4xl text-teal-dk">₦{{ number_format($finalAmountDue) }}</p>
                    <p class="mt-1 text-sm text-ink-soft">{{ ucfirst($billingCycle) }} billing</p>
                </div>

                @if ($paymentsEnabled && $paymentMethod === 'card' && $redemption['eligible'])
                    <div class="rounded-sm border border-teal-lt bg-teal-lt/30 p-4">
                        <label class="flex cursor-pointer items-center gap-3">
                            <input type="checkbox" wire:model.live="applyCoins" class="h-4 w-4 rounded border-teal text-teal focus:ring-teal">
                            <div>
                                <p class="text-sm font-medium text-teal-dk">Apply your Jannah Coins</p>
                                <p class="text-xs text-ink-soft">
                                    Save ₦{{ number_format($redemption['discount_kobo'] / 100) }} using {{ $redemption['coins_to_use'] }} coins
                                    (max {{ app(\App\Services\CoinsService::class)::maxRedemptionPercent() }}% of this payment)
                                </p>
                            </div>
                        </label>
                    </div>
                @endif

                @if ($paymentsEnabled)
                    <div class="rounded-sm border border-teal bg-white p-5 text-center">
                        <h3 class="text-sm font-semibold text-teal-dk">Try Again</h3>
                        <p class="mt-1 text-xs text-ink-soft">Click below to retry payment via Paystack</p>
                        <button type="button" wire:click="redirectToPaystack" wire:loading.attr="disabled"
                                class="mt-4 w-full rounded-sm bg-teal px-4 py-3 text-sm font-medium text-white transition hover:bg-teal-dk disabled:opacity-50">
                            Retry Payment with Card
                        </button>
                        @error('paystack') <p class="mt-2 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                @else
                    <div class="rounded-sm border border-gold bg-ivory p-5">
                        <h3 class="text-sm font-semibold text-teal-dk">Pay via Bank Transfer</h3>
                        <p class="mt-2 text-xs text-ink-soft">Online payment is temporarily unavailable. Please pay via bank transfer and submit for admin verification.</p>
                    </div>
                @endif
            </div>

        {{-- STATE: member — payment confirmed, redirecting --}}
        @elseif ($status === 'member')
            <h1 class="font-display text-4xl leading-none text-teal-dk">Payment Confirmed!</h1>

            <div class="mt-6 space-y-4">
                <div class="rounded-sm bg-teal-lt p-8 text-center">
                    <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-teal">
                        <svg class="h-8 w-8 animate-spin text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                    <p class="text-sm text-ink-md">Your membership is active! Taking you to your dashboard...</p>
                    <p class="mt-2 text-xs text-ink-soft">If you are not redirected automatically, <a href="{{ route('home') }}" class="text-teal underline">click here</a>.</p>
                </div>
            </div>
        @endif
    </section>
</div>
