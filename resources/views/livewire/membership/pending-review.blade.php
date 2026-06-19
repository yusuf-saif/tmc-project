<div class="mx-auto flex min-h-screen w-full max-w-2xl items-center justify-center px-6 py-12">
    <section class="w-full max-w-xl rounded bg-white p-10 text-center shadow-[0_4px_24px_rgba(0,0,0,0.06)]">
        <img src="{{ asset('images/img2.png') }}" alt="The Muhsinat Club" class="mx-auto mb-6 w-36 max-w-full object-contain">

        <h1 class="font-display text-4xl leading-none text-teal-dk">Thank you</h1>
        <p class="mt-3 font-display text-2xl text-gold">Your submission is under review</p>
        <p class="mt-4 text-sm font-light leading-7 text-ink-soft">
            Your membership application has been received and is being reviewed by our team.
            We'll notify you as soon as a decision has been made.
        </p>

        {{-- Progress Timeline --}}
        <div class="mt-8 text-left" style="padding:0 8px;">
            <p class="section-label" style="color:var(--gold);margin-bottom:16px;">Application Progress</p>

            {{-- Step 1: Registration --}}
            <div class="timeline-step">
                <div class="timeline-line-complete">
                    <div class="timeline-dot-complete">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round"><path d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <div class="timeline-connector timeline-connector-complete"></div>
                </div>
                <div class="timeline-text">
                    <p class="timeline-title timeline-title-complete">Registration</p>
                    <p class="timeline-sub">Account created</p>
                </div>
            </div>

            {{-- Step 2: Onboarding --}}
            <div class="timeline-step">
                <div class="timeline-line-complete">
                    <div class="timeline-dot-complete">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round"><path d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <div class="timeline-connector timeline-connector-complete"></div>
                </div>
                <div class="timeline-text">
                    <p class="timeline-title timeline-title-complete">Onboarding</p>
                    <p class="timeline-sub">Profile completed</p>
                </div>
            </div>

            {{-- Step 3: Under Review (current) --}}
            <div class="timeline-step">
                <div class="timeline-line-complete">
                    <div class="timeline-dot-active">
                        <div class="timeline-dot-pulse"></div>
                    </div>
                    <div class="timeline-connector timeline-connector-pending"></div>
                </div>
                <div class="timeline-text">
                    <p class="timeline-title" style="color:var(--gold);">Under Review</p>
                    <p class="timeline-sub">Our team is reviewing your application</p>
                </div>
            </div>

            {{-- Step 4: Approval --}}
            <div class="timeline-step">
                <div class="timeline-line-complete">
                    <div class="timeline-dot-pending">
                        <div class="timeline-dot-inner"></div>
                    </div>
                </div>
                <div class="timeline-text">
                    <p class="timeline-title" style="color:var(--ink-soft);">Approval</p>
                    <p class="timeline-sub">Welcome to TMC</p>
                </div>
            </div>
        </div>

        {{-- Estimated Time --}}
        <div class="mt-6 rounded-sm bg-ivory p-4" style="border:1px solid rgba(200,168,75,0.15);">
          <p style="font-size:13px;color:var(--ink-md);margin:0;">
            <strong style="color:var(--teal);">Estimated review time:</strong><br>
            Usually within 24–48 hours. We'll send you a notification once reviewed.
          </p>
        </div>

        {{-- What Happens Next --}}
        <div class="mt-4 text-left" style="padding:0 4px;">
          <p class="section-label" style="color:var(--gold);margin-bottom:10px;">What happens next?</p>
          <div class="space-y-2">
            <div class="flex gap-2.5 items-start">
              <span class="text-sm font-semibold text-teal">1.</span>
              <p class="text-[13px] text-ink-md leading-relaxed">Our team reviews your profile and application details</p>
            </div>
            <div class="flex gap-2.5 items-start">
              <span class="text-sm font-semibold text-teal">2.</span>
              <p class="text-[13px] text-ink-md leading-relaxed">You'll receive a notification with the decision</p>
            </div>
            <div class="flex gap-2.5 items-start">
              <span class="text-sm font-semibold text-teal">3.</span>
              <p class="text-[13px] text-ink-md leading-relaxed">Once approved, you'll get your membership card and can explore the full app</p>
            </div>
          </div>
        </div>

        {{-- Soft Engagement --}}
        <div class="mt-6 rounded-sm p-4" style="background:var(--teal-lt);border:1px solid rgba(26,107,114,0.15);">
          <p style="font-size:13px;color:var(--teal);margin:0;font-weight:500;">
            While you wait, feel free to explore our
            <a href="{{ route('landing') }}" class="tmc-link">public resources</a>.
          </p>
        </div>
    </section>
</div>
