<div class="space-y-6">
    @if ($hasApproved && $approvedListing)
        <section class="space-y-4 rounded-[8px] bg-white p-6" style="border: 1px solid var(--border);">
            <h1 class="font-display text-[1.8rem] leading-none text-teal">List Your Business</h1>
            <p class="text-sm font-light leading-7 text-ink-soft">Your business is live on the Souq ✓</p>
            <div class="rounded-[8px] p-4" style="background: var(--mint-lt); border: 1px solid var(--border);">
                <p class="text-sm font-semibold text-ink">{{ $approvedListing->business_name }}</p>
                <p class="mt-2 text-[12px] font-light leading-6 text-ink-soft">{{ $approvedListing->description }}</p>
            </div>
            <a href="{{ route('souq.show', $approvedListing->slug) }}" class="tmc-button-gold max-w-[220px] no-underline">View Listing</a>
        </section>

    @elseif ($hasBankTransferPending && $approvedUnpaidListing)
        <section class="space-y-4 rounded-[8px] bg-white p-6 text-center" style="border: 1px solid var(--border);" wire:poll.5s="checkPaymentStatus">
            <h1 class="font-display text-[1.8rem] leading-none text-teal">List Your Business</h1>
            <p class="text-sm font-light leading-7 text-ink-soft">Your bank transfer is being reviewed.</p>
            <div class="rounded-[8px] p-4" style="background: var(--gold-pale); border: 1px solid rgba(200, 168, 75, 0.25);">
                <p class="text-sm font-semibold text-ink">{{ $approvedUnpaidListing->business_name }}</p>
                <p class="mt-2 text-[12px] font-light leading-6 text-ink-soft">Listing fee: ₦{{ number_format($feeAmount) }}</p>
            </div>
            <p class="text-sm font-light leading-7 text-ink-soft">We'll activate your listing once the admin confirms your payment.</p>
        </section>

    @elseif ($hasApprovedUnpaid && $approvedUnpaidListing)
        <section class="space-y-4 rounded-[8px] bg-white p-6" style="border: 1px solid var(--border);" wire:poll.5s="checkPaymentStatus">
            <h1 class="font-display text-[1.8rem] leading-none text-teal">List Your Business</h1>
            <p class="text-sm font-light leading-7 text-ink-soft">Your listing has been approved!</p>
            <div class="rounded-[8px] p-4" style="background: var(--gold-pale); border: 1px solid rgba(200, 168, 75, 0.25);">
                <p class="text-sm font-semibold text-ink">{{ $approvedUnpaidListing->business_name }}</p>
                <p class="mt-2 text-sm font-light leading-7 text-ink-soft">
                    Listing fee: ₦{{ number_format($finalFeeAmount) }}
                    @if ($paymentMethod === 'card' && $applyCoins && $redemption['eligible'])
                        <span class="text-teal">(₦{{ number_format($feeAmount - $finalFeeAmount) }} covered by {{ $redemption['coins_to_use'] }} coins)</span>
                    @endif
                </p>
            </div>

            {{-- Payment method toggle --}}
            <div class="space-y-2">
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

            {{-- Jannah Coins (only for card) --}}
            @if ($paymentMethod === 'card' && $redemption['eligible'])
                <div class="rounded-[8px] border p-4" style="border-color: var(--teal-lt); background: rgba(210, 235, 230, 0.3);">
                    <label class="flex cursor-pointer items-center gap-3">
                        <input type="checkbox" wire:model.live="applyCoins" class="h-4 w-4 rounded border-teal text-teal focus:ring-teal">
                        <div>
                            <p class="text-sm font-medium text-teal-dk">Apply your Jannah Coins</p>
                            <p class="text-[12px] font-light text-ink-soft">
                                Save ₦{{ number_format($redemption['discount_kobo'] / 100) }} using {{ $redemption['coins_to_use'] }} coins
                                (max {{ app(\App\Services\CoinsService::class)::maxRedemptionPercent() }}% of this fee)
                            </p>
                        </div>
                    </label>
                </div>
            @endif

            {{-- Card payment --}}
            @if ($paymentMethod === 'card')
                <button type="button" wire:click="payListing" wire:loading.attr="disabled" class="tmc-button-gold max-w-[220px] no-underline">
                    Pay ₦{{ number_format($finalFeeAmount) }} Now
                </button>

            {{-- Bank transfer --}}
            @else
                <div class="rounded-[8px] border p-4" style="border-color: var(--gold); background: var(--ivory);">
                    <h3 class="text-sm font-semibold text-teal-dk">Bank Transfer Details</h3>
                    <pre class="mt-3 whitespace-pre-wrap font-body text-sm font-light leading-7 text-ink-md">{{ $bankDetails }}</pre>
                </div>
                <button type="button" wire:click="submitBankTransfer" wire:loading.attr="disabled" class="tmc-button-gold max-w-[220px] no-underline">
                    <span wire:loading.remove wire:target="submitBankTransfer">I've Made the Transfer</span>
                    <span wire:loading wire:target="submitBankTransfer">Submitting...</span>
                </button>
            @endif
        </section>

    @elseif ($submitted)
        <section class="space-y-4 rounded-[8px] bg-white p-6 text-center" style="border: 1px solid var(--border);">
            <h1 class="font-display text-[1.8rem] leading-none text-teal">List Your Business</h1>
            <p class="text-sm font-light leading-7 text-ink-soft">JazakAllahu Khairan — your application has been submitted! We review within 48 hours, insha'Allah.</p>
            <div>
                @push('backButton')
                    <a href="{{ route('souq') }}" class="back-btn">&larr; The Souq</a>
                @endpush
            </div>
        </section>

    @elseif ($hasPending)
        <section class="space-y-4 rounded-[8px] p-6" style="background: var(--gold-pale); border: 1px solid rgba(200, 168, 75, 0.25);">
            <h1 class="font-display text-[1.8rem] leading-none text-teal">List Your Business</h1>
            <p class="text-sm font-light leading-7 text-ink-soft">Your application is under review — we'll be in touch insha'Allah within 48 hours.</p>
        </section>

    @else
        <section class="space-y-6">
            <div>
                <h1 class="font-display text-[1.8rem] leading-none text-teal">List Your Business</h1>
                <p class="mt-2 text-sm font-light leading-7 text-ink-soft">Share your work with the sisterhood in a calm, curated space.</p>
            </div>

            <div class="space-y-4 rounded-[8px] bg-white p-5" style="border: 1px solid var(--border);">
                <div>
                    <label class="tmc-label">Business Name</label>
                    <input type="text" wire:model="businessName" class="tmc-input">
                    @error('businessName') <p class="tmc-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="tmc-label">Category</label>
                    <select wire:model="category" class="tmc-input">
                        <option value="">Select a category</option>
                        @foreach (['fashion' => 'Fashion', 'food_catering' => 'Food & Catering', 'health_beauty' => 'Health & Beauty', 'education' => 'Education', 'services' => 'Services', 'creative' => 'Creative', 'other' => 'Other'] as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('category') <p class="tmc-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="tmc-label">Description</label>
                    <textarea wire:model="description" maxlength="300" class="min-h-[140px] w-full bg-white px-4 py-3 text-sm font-light text-ink outline-none" style="border: 1px solid var(--border); border-radius: 6px;"></textarea>
                    <p class="mt-2 text-right text-[11px] {{ strlen($description) > 280 ? 'text-red-500' : 'text-ink-soft' }}">{{ strlen($description) }} / 300</p>
                    @error('description') <p class="tmc-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="tmc-label">Contact Email</label>
                    <input type="email" wire:model="contactEmail" class="tmc-input">
                    @error('contactEmail') <p class="tmc-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="tmc-label">Phone</label>
                    <input type="text" wire:model="phone" class="tmc-input">
                    @error('phone') <p class="tmc-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="tmc-label">Website</label>
                    <input type="text" wire:model="website" placeholder="https://..." class="tmc-input">
                    @error('website') <p class="tmc-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="tmc-label">Instagram</label>
                    <input type="text" wire:model="instagram" placeholder="@yourbusiness" class="tmc-input">
                    @error('instagram') <p class="tmc-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="tmc-label">Logo</label>
                    <input type="file" wire:model="logo" accept="image/*" class="block w-full text-sm text-ink-soft">
                    @if ($logo)
                        <img src="{{ $logo->temporaryUrl() }}" alt="Logo preview" class="mt-3 h-20 w-20 rounded-full border object-cover" style="border-color: var(--border);">
                    @endif
                    @error('logo') <p class="tmc-error">{{ $message }}</p> @enderror
                </div>

                <button type="button" wire:click="submit" wire:loading.attr="disabled" class="tmc-button-gold w-full">
                    Submit Application
                </button>
            </div>
        </section>
    @endif
</div>
