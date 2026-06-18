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
            <p style="font-size:11px;font-weight:600;text-transform:uppercase;
                      letter-spacing:1.5px;color:var(--gold);margin-bottom:16px;">
              Application Progress
            </p>

            {{-- Step 1: Registration --}}
            <div style="display:flex;gap:12px;margin-bottom:0;">
              <div style="display:flex;flex-direction:column;align-items:center;">
                <div style="width:24px;height:24px;border-radius:50%;
                            background:var(--teal);display:flex;align-items:center;
                            justify-content:center;flex-shrink:0;">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                       stroke="white" stroke-width="2.5" stroke-linecap="round">
                    <path d="M5 13l4 4L19 7"/>
                  </svg>
                </div>
                <div style="width:2px;flex:1;min-height:20px;background:var(--teal);"></div>
              </div>
              <div style="padding-bottom:16px;">
                <p style="font-size:13px;font-weight:600;color:var(--teal);margin:0;">Registration</p>
                <p style="font-size:12px;color:var(--ink-soft);margin:2px 0 0;">Account created</p>
              </div>
            </div>

            {{-- Step 2: Onboarding --}}
            <div style="display:flex;gap:12px;margin-bottom:0;">
              <div style="display:flex;flex-direction:column;align-items:center;">
                <div style="width:24px;height:24px;border-radius:50%;
                            background:var(--teal);display:flex;align-items:center;
                            justify-content:center;flex-shrink:0;">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                       stroke="white" stroke-width="2.5" stroke-linecap="round">
                    <path d="M5 13l4 4L19 7"/>
                  </svg>
                </div>
                <div style="width:2px;flex:1;min-height:20px;background:var(--teal);"></div>
              </div>
              <div style="padding-bottom:16px;">
                <p style="font-size:13px;font-weight:600;color:var(--teal);margin:0;">Onboarding</p>
                <p style="font-size:12px;color:var(--ink-soft);margin:2px 0 0;">Profile completed</p>
              </div>
            </div>

            {{-- Step 3: Under Review (current) --}}
            <div style="display:flex;gap:12px;margin-bottom:0;">
              <div style="display:flex;flex-direction:column;align-items:center;">
                <div style="width:24px;height:24px;border-radius:50%;
                            background:var(--gold);display:flex;align-items:center;
                            justify-content:center;flex-shrink:0;
                            animation:pulse-gold 2s ease-in-out infinite;">
                  <div style="width:8px;height:8px;border-radius:50%;background:white;"></div>
                </div>
                <div style="width:2px;flex:1;min-height:20px;background:var(--border);"></div>
              </div>
              <div style="padding-bottom:16px;">
                <p style="font-size:13px;font-weight:600;color:var(--gold);margin:0;">Under Review</p>
                <p style="font-size:12px;color:var(--ink-soft);margin:2px 0 0;">Our team is reviewing your application</p>
              </div>
            </div>

            {{-- Step 4: Approval --}}
            <div style="display:flex;gap:12px;">
              <div style="display:flex;flex-direction:column;align-items:center;">
                <div style="width:24px;height:24px;border-radius:50%;
                            background:var(--border);display:flex;align-items:center;
                            justify-content:center;flex-shrink:0;">
                  <div style="width:8px;height:8px;border-radius:50%;background:var(--ink-soft);opacity:0.4;"></div>
                </div>
              </div>
              <div>
                <p style="font-size:13px;font-weight:600;color:var(--ink-soft);margin:0;">Approval</p>
                <p style="font-size:12px;color:var(--ink-soft);margin:2px 0 0;">Welcome to TMC</p>
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
          <p style="font-size:11px;font-weight:600;text-transform:uppercase;
                    letter-spacing:1.5px;color:var(--gold);margin-bottom:10px;">
            What happens next?
          </p>
          <div style="display:flex;flex-direction:column;gap:8px;">
            <div style="display:flex;gap:10px;align-items:flex-start;">
              <span style="font-size:16px;flex-shrink:0;">1.</span>
              <p style="font-size:13px;color:var(--ink-md);margin:0;line-height:1.5;">
                Our team reviews your profile and application details
              </p>
            </div>
            <div style="display:flex;gap:10px;align-items:flex-start;">
              <span style="font-size:16px;flex-shrink:0;">2.</span>
              <p style="font-size:13px;color:var(--ink-md);margin:0;line-height:1.5;">
                You'll receive a notification with the decision
              </p>
            </div>
            <div style="display:flex;gap:10px;align-items:flex-start;">
              <span style="font-size:16px;flex-shrink:0;">3.</span>
              <p style="font-size:13px;color:var(--ink-md);margin:0;line-height:1.5;">
                Once approved, you'll get your membership card and can explore the full app
              </p>
            </div>
          </div>
        </div>

        {{-- Soft Engagement --}}
        <div class="mt-6 rounded-sm p-4" style="background:var(--teal-lt);
                                                border:1px solid rgba(26,107,114,0.15);">
          <p style="font-size:13px;color:var(--teal);margin:0;font-weight:500;">
            While you wait, feel free to explore our
            <a href="{{ route('landing') }}" class="tmc-link">public resources</a>.
          </p>
        </div>
    </section>
</div>
