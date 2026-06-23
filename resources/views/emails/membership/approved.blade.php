@component('mail::message')

# Assalamu Alaikum {{ $user->name }},

Your membership application has been approved! Welcome to The Muhsinat Club.

**Membership ID:** {{ $membershipId }}

**Membership Type:** {{ $membershipType }}

Your legacy card is ready:

@component('mail::button', ['url' => $legacyCardUrl])
View Your Legacy Card
@endcomponent

To complete your registration, please complete your membership payment:

@component('mail::button', ['url' => $paymentUrl, 'color' => 'success'])
Complete Payment
@endcomponent

You can choose between monthly, quarterly, or yearly billing.

**Important:** Your login credentials are not included in this email for security reasons.

If you have any questions, please contact our support team.

JazakAllahu Khairan,<br>
{{ config('app.name') }}
@endcomponent
