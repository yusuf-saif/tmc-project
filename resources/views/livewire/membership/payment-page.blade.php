<div class="mx-auto flex min-h-screen w-full max-w-2xl items-center justify-center px-6 py-12">
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

        @if ($status === 'approved_pending_payment')
            <h1 class="font-display text-4xl leading-none text-teal-dk">Membership Payment</h1>

            <div class="mt-6 space-y-4">
                @if ($membershipId)
                    <div class="rounded-sm bg-teal-lt p-4 text-center">
                        <p class="text-[11px] font-semibold uppercase tracking-[1.5px] text-teal">Your Membership ID</p>
                        <p class="mt-1 font-display text-3xl text-teal-dk">{{ $membershipId }}</p>
                    </div>
                @endif

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

        @elseif ($status === 'payment_submitted')
            <h1 class="font-display text-4xl leading-none text-teal-dk">Payment Submitted</h1>

            <div class="mt-6 space-y-4">
                <div class="rounded-sm bg-ivory p-5 text-center">
                    <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-teal-lt">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-teal">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                            <polyline points="22 4 12 14.01 9 11.01"/>
                        </svg>
                    </div>
                    <p class="text-sm text-ink-md">Your payment receipt has been received and is awaiting admin verification.</p>
                    <p class="mt-2 text-xs text-ink-soft">You will be notified once your payment is confirmed and your account is activated.</p>
                </div>

                @if ($membershipId)
                    <div class="rounded-sm bg-teal-lt p-4 text-center">
                        <p class="text-[11px] font-semibold uppercase tracking-[1.5px] text-teal">Membership ID</p>
                        <p class="mt-1 font-display text-3xl text-teal-dk">{{ $membershipId }}</p>
                    </div>
                @endif
            </div>
        @endif
    </section>
</div>
