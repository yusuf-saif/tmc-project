<div class="mx-auto flex min-h-screen w-full max-w-2xl items-center justify-center px-6 py-12"
     x-data="{ polling: null }"
     x-init="
        polling = setInterval(() => {
            $wire.checkPaymentStatus()
                .then(() => {
                    if ($wire.paymentStatus === 'active') {
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

        {{-- STATE: payment_pending — show payment form --}}
        @if ($status === 'payment_pending')
            <h1 class="font-display text-4xl leading-none text-teal-dk">Membership Payment</h1>

            <div class="mt-6 space-y-4">
                @if ($membershipId)
                    <div class="rounded-sm bg-teal-lt p-4 text-center">
                        <p class="text-[11px] font-semibold uppercase tracking-[1.5px] text-teal">Your Membership ID</p>
                        <p class="mt-1 font-display text-3xl text-teal-dk">{{ $membershipId }}</p>
                    </div>
                @endif

                <div class="rounded-sm bg-ivory p-5 text-center">
                    <p class="text-[11px] font-semibold uppercase tracking-[1.5px] text-gold">Amount Due</p>
                    <p class="mt-1 font-display text-4xl text-teal-dk">₦{{ number_format($amountDue) }}</p>
                    <p class="mt-1 text-sm text-ink-soft">{{ ucfirst($billingCycle) }} billing</p>
                </div>

                <div class="rounded-sm border border-teal bg-white p-5 text-center">
                    <h3 class="text-sm font-semibold text-teal-dk">Pay Online with Card</h3>
                    <p class="mt-1 text-xs text-ink-soft">Secure payment via Paystack</p>
                    <button type="button" wire:click="redirectToPaystack" wire:loading.attr="disabled"
                            class="mt-4 w-full rounded-sm bg-teal px-4 py-3 text-sm font-medium text-white transition hover:bg-teal-dk disabled:opacity-50">
                        <span wire:loading.remove wire:target="redirectToPaystack">Pay ₦{{ number_format($amountDue) }} with Card</span>
                        <span wire:loading wire:target="redirectToPaystack">Connecting to Paystack...</span>
                    </button>
                    @error('paystack') <p class="mt-2 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div class="relative">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-200"></div>
                    </div>
                    <div class="relative flex justify-center text-xs uppercase">
                        <span class="bg-white px-2 text-ink-soft">Or pay via bank transfer</span>
                    </div>
                </div>

                <div class="rounded-sm bg-ivory p-5 text-left text-sm text-ink-soft">
                    <h3 class="mb-2 text-sm font-semibold text-ink-md">Payment Instructions</h3>
                    <p class="leading-7">
                        Please transfer the membership fee using the account details below, then upload your payment receipt for verification.
                    </p>
                    <div class="mt-4 space-y-2">
                        {!! nl2br(e($bankDetails)) !!}
                    </div>
                </div>

                <div class="rounded-sm border border-teal-lt bg-white p-5 text-left text-sm">
                    <h3 class="mb-3 text-sm font-semibold text-ink-md">Upload Payment Receipt</h3>

                    <form wire:submit="submitPayment" class="space-y-4">
                        <div>
                            <label class="mb-1 block text-xs font-medium text-ink-md">Payment Receipt (JPG, PNG, PDF — max 5MB)</label>
                            <input type="file" wire:model="paymentProof"
                                   class="block w-full text-sm text-ink-soft file:mr-4 file:rounded-sm file:border-0 file:bg-teal file:px-4 file:py-2 file:text-sm file:text-white hover:file:bg-teal-dk">
                            <div wire:loading wire:target="paymentProof" class="mt-1 text-xs text-ink-soft">Uploading...</div>
                            @error('paymentProof') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="mb-1 block text-xs font-medium text-ink-md">Notes (optional)</label>
                            <textarea wire:model="paymentNotes" rows="2"
                                      class="w-full rounded-sm border border-gray-200 p-2 text-sm focus:border-teal focus:ring-1 focus:ring-teal"
                                      placeholder="Any additional information..."></textarea>
                            @error('paymentNotes') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>

                        <button type="submit" wire:loading.attr="disabled" wire:target="submitPayment"
                                class="w-full rounded-sm bg-teal px-4 py-3 text-sm font-medium text-white transition hover:bg-teal-dk disabled:opacity-50">
                            <span wire:loading.remove wire:target="submitPayment">Submit Payment Receipt</span>
                            <span wire:loading wire:target="submitPayment">Submitting...</span>
                        </button>
                    </form>
                </div>

                <p class="text-center text-xs text-ink-soft">
                    Your payment will be verified by an admin. You'll be notified once confirmed.
                </p>
            </div>

        {{-- STATE: payment_processing — show spinner --}}
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
                    <p class="text-sm text-ink-md">We are processing your payment. This may take a moment.</p>
                    <p class="mt-2 text-xs text-ink-soft">You will be automatically redirected once payment is confirmed.</p>
                </div>

                @if ($membershipId)
                    <div class="rounded-sm bg-teal-lt p-4 text-center">
                        <p class="text-[11px] font-semibold uppercase tracking-[1.5px] text-teal">Membership ID</p>
                        <p class="mt-1 font-display text-3xl text-teal-dk">{{ $membershipId }}</p>
                    </div>
                @endif
            </div>

        {{-- STATE: payment_failed — show retry --}}
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
                    <p class="text-sm text-ink-md">Your payment could not be processed. Please try again or use bank transfer.</p>
                </div>

                @if ($membershipId)
                    <div class="rounded-sm bg-teal-lt p-4 text-center">
                        <p class="text-[11px] font-semibold uppercase tracking-[1.5px] text-teal">Membership ID</p>
                        <p class="mt-1 font-display text-3xl text-teal-dk">{{ $membershipId }}</p>
                    </div>
                @endif

                <div class="rounded-sm bg-ivory p-5 text-center">
                    <p class="text-[11px] font-semibold uppercase tracking-[1.5px] text-gold">Amount Due</p>
                    <p class="mt-1 font-display text-4xl text-teal-dk">₦{{ number_format($amountDue) }}</p>
                    <p class="mt-1 text-sm text-ink-soft">{{ ucfirst($billingCycle) }} billing</p>
                </div>

                <div class="rounded-sm border border-teal bg-white p-5 text-center">
                    <h3 class="text-sm font-semibold text-teal-dk">Try Again</h3>
                    <p class="mt-1 text-xs text-ink-soft">Click below to retry payment via Paystack</p>
                    <button type="button" wire:click="redirectToPaystack" wire:loading.attr="disabled"
                            class="mt-4 w-full rounded-sm bg-teal px-4 py-3 text-sm font-medium text-white transition hover:bg-teal-dk disabled:opacity-50">
                        Retry Payment with Card
                    </button>
                    @error('paystack') <p class="mt-2 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div class="relative">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-200"></div>
                    </div>
                    <div class="relative flex justify-center text-xs uppercase">
                        <span class="bg-white px-2 text-ink-soft">Or pay via bank transfer</span>
                    </div>
                </div>

                <div class="rounded-sm border border-teal-lt bg-white p-5 text-left text-sm">
                    <h3 class="mb-3 text-sm font-semibold text-ink-md">Upload Payment Receipt</h3>

                    <form wire:submit="submitPayment" class="space-y-4">
                        <div>
                            <label class="mb-1 block text-xs font-medium text-ink-md">Payment Receipt (JPG, PNG, PDF — max 5MB)</label>
                            <input type="file" wire:model="paymentProof"
                                   class="block w-full text-sm text-ink-soft file:mr-4 file:rounded-sm file:border-0 file:bg-teal file:px-4 file:py-2 file:text-sm file:text-white hover:file:bg-teal-dk">
                            <div wire:loading wire:target="paymentProof" class="mt-1 text-xs text-ink-soft">Uploading...</div>
                            @error('paymentProof') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="mb-1 block text-xs font-medium text-ink-md">Notes (optional)</label>
                            <textarea wire:model="paymentNotes" rows="2"
                                      class="w-full rounded-sm border border-gray-200 p-2 text-sm focus:border-teal focus:ring-1 focus:ring-teal"
                                      placeholder="Any additional information..."></textarea>
                            @error('paymentNotes') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>

                        <button type="submit" wire:loading.attr="disabled" wire:target="submitPayment"
                                class="w-full rounded-sm bg-teal px-4 py-3 text-sm font-medium text-white transition hover:bg-teal-dk disabled:opacity-50">
                            Submit Payment Receipt
                        </button>
                    </form>
                </div>
            </div>

        {{-- STATE: active — auto-redirected by Alpine, show fallback --}}
        @elseif ($status === 'active')
            <h1 class="font-display text-4xl leading-none text-teal-dk">Welcome!</h1>

            <div class="mt-6 space-y-4">
                <div class="rounded-sm bg-teal-lt p-8 text-center">
                    <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-teal">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                            <polyline points="22 4 12 14.01 9 11.01"/>
                        </svg>
                    </div>
                    <p class="text-sm text-ink-md">Your account is active! Redirecting you to your dashboard...</p>
                </div>
            </div>
        @endif
    </section>
</div>
