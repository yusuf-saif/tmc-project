<div class="mx-auto flex min-h-screen w-full max-w-2xl items-center justify-center px-6 py-12">
    <section class="w-full max-w-xl rounded bg-white p-10 shadow-[0_4px_24px_rgba(0,0,0,0.06)]">
        <img src="{{ asset('images/img2.png') }}" alt="The Muhsinat Club" class="mx-auto mb-6 w-36 max-w-full object-contain">

        <h1 class="font-display text-4xl leading-none text-teal-dk">Membership Payment</h1>

        @if ($profile->membership_status === 'approved_pending_payment')
            <div class="mt-6 space-y-4">
                <div class="rounded-sm bg-teal-lt p-4 text-center">
                    <p class="text-[11px] font-semibold uppercase tracking-[1.5px] text-teal">Your Membership ID</p>
                    <p class="mt-1 font-display text-3xl text-teal-dk">{{ $profile->membership_id }}</p>
                </div>

                <div class="rounded-sm bg-ivory p-5 text-left text-sm text-ink-soft">
                    <h3 class="mb-2 text-sm font-semibold text-ink-md">Payment Instructions</h3>
                    <p class="leading-7">
                        Please transfer the membership fee to the account details below and your account will be activated within 24 hours.
                    </p>
                    <div class="mt-4 space-y-2">
                        <p><strong>Bank:</strong> [Bank Name]</p>
                        <p><strong>Account Name:</strong> The Muhsinat Club</p>
                        <p><strong>Account Number:</strong> [Account Number]</p>
                        <p><strong>Amount:</strong> [Amount]</p>
                    </div>
                </div>

                <p class="text-center text-xs text-ink-soft">
                    After payment, please contact the admin to confirm your payment.
                </p>
            </div>
        @elseif ($profile->membership_status === 'payment_submitted')
            <div class="mt-6 space-y-4">
                <div class="rounded-sm bg-ivory p-5 text-center">
                    <p class="text-sm text-ink-md">Your payment has been submitted and is awaiting confirmation.</p>
                    <p class="mt-2 text-xs text-ink-soft">You will be notified once your payment is confirmed.</p>
                </div>

                @if ($profile->membership_id)
                    <div class="rounded-sm bg-teal-lt p-4 text-center">
                        <p class="text-[11px] font-semibold uppercase tracking-[1.5px] text-teal">Membership ID</p>
                        <p class="mt-1 font-display text-3xl text-teal-dk">{{ $profile->membership_id }}</p>
                    </div>
                @endif
            </div>
        @endif
    </section>
</div>
