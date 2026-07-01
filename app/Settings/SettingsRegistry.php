<?php

namespace App\Settings;

class SettingsRegistry
{
    const GROUPS = [
        'membership' => 'Membership & Billing',
        'souq' => 'Souq / Business Listings',
        'coins' => 'Coins & Rewards',
        'notifications' => 'Notification Toggles',
        'donations' => 'Donations',
        'content' => 'Content',
        'events' => 'Events',
        'branding' => 'Brand & Appearance',
        'dashboard' => 'Dashboard',
    ];

    const KEYS = [
        'membership_fee_monthly' => [
            'group' => 'membership',
            'type' => 'int',
            'default' => 5000,
            'label' => 'Monthly Membership Fee (₦)',
            'description' => 'Amount in Naira for monthly billing cycle.',
        ],
        'membership_fee_quarterly' => [
            'group' => 'membership',
            'type' => 'int',
            'default' => 12000,
            'label' => 'Quarterly Membership Fee (₦)',
            'description' => 'Amount in Naira for quarterly billing cycle.',
        ],
        'membership_fee_yearly' => [
            'group' => 'membership',
            'type' => 'int',
            'default' => 40000,
            'label' => 'Yearly Membership Fee (₦)',
            'description' => 'Amount in Naira for yearly billing cycle.',
        ],
        'membership_approval_coins' => [
            'group' => 'membership',
            'type' => 'int',
            'default' => 100,
            'label' => 'Membership Approval Coins',
            'description' => 'Coins awarded to a member when their membership is approved by admin.',
        ],
        'membership_billing_cycle_days' => [
            'group' => 'membership',
            'type' => 'int',
            'default' => 30,
            'label' => 'Billing Cycle Days',
            'description' => 'Number of days in a single membership billing period.',
        ],
        'membership_grace_period_days' => [
            'group' => 'membership',
            'type' => 'int',
            'default' => 7,
            'label' => 'Grace Period Days',
            'description' => 'Days after period end before membership is suspended.',
        ],
        'membership_reminder_days_before' => [
            'group' => 'membership',
            'type' => 'int',
            'default' => 7,
            'label' => 'Reminder Days Before',
            'description' => 'Days before period end to send renewal reminder.',
        ],
        'souq_listing_fee_kobo' => [
            'group' => 'souq',
            'type' => 'int',
            'default' => 500000,
            'label' => 'Souq Listing Fee (kobo)',
            'description' => 'Fee in kobo for listing on the Souq (500000 kobo = ₦5,000).',
        ],
        'souq_billing_months' => [
            'group' => 'souq',
            'type' => 'int',
            'default' => 1,
            'label' => 'Souq Billing Months',
            'description' => 'Number of Hijri months per Souq billing period.',
        ],
        'referral_coins_amount' => [
            'group' => 'coins',
            'type' => 'int',
            'default' => 25,
            'label' => 'Referral Coins Amount',
            'description' => 'Coins awarded to a referrer when their referral activates.',
        ],
        'starter_coins_amount' => [
            'group' => 'coins',
            'type' => 'int',
            'default' => 50,
            'label' => 'Starter Coins Amount',
            'description' => 'Coins awarded to a new member on signup (welcome bonus).',
        ],
        'coin_value_kobo' => [
            'group' => 'coins',
            'type' => 'int',
            'default' => 500,
            'label' => 'Value Per Coin (kobo)',
            'description' => 'How much one Jannah Coin is worth when redeemed as a discount (500 kobo = ₦5).',
        ],
        'max_redemption_percent' => [
            'group' => 'coins',
            'type' => 'int',
            'default' => 20,
            'label' => 'Max Redemption (% of payment)',
            'description' => 'Maximum percentage of any single payment that can be covered using coins.',
        ],
        'notify_renewal_reminders_enabled' => [
            'group' => 'notifications',
            'type' => 'bool',
            'default' => true,
            'label' => 'Renewal Reminders Enabled',
            'description' => 'Send membership renewal reminder notifications.',
        ],
        'notify_event_reminders_enabled' => [
            'group' => 'notifications',
            'type' => 'bool',
            'default' => true,
            'label' => 'Event Reminders Enabled',
            'description' => 'Send event reminder notifications to RSVPd members.',
        ],
        'notify_souq_approval_enabled' => [
            'group' => 'notifications',
            'type' => 'bool',
            'default' => true,
            'label' => 'Souq Approval Notifications Enabled',
            'description' => 'Send email notification when a Souq listing is approved.',
        ],
        'bank_details' => [
            'group' => 'donations',
            'type' => 'text',
            'default' => '',
            'label' => 'Bank Details',
            'description' => 'Bank account details shown on the donate page.',
        ],
        'donate_message' => [
            'group' => 'donations',
            'type' => 'text',
            'default' => '',
            'label' => 'Donate Message',
            'description' => 'Message shown on the donate page.',
        ],
        'suggested_donation_1' => [
            'group' => 'donations',
            'type' => 'int',
            'default' => 5000,
            'label' => 'Suggested Donation 1 (₦)',
            'description' => 'First suggested donation amount in Naira.',
        ],
        'suggested_donation_2' => [
            'group' => 'donations',
            'type' => 'int',
            'default' => 10000,
            'label' => 'Suggested Donation 2 (₦)',
            'description' => 'Second suggested donation amount in Naira.',
        ],
        'suggested_donation_3' => [
            'group' => 'donations',
            'type' => 'int',
            'default' => 25000,
            'label' => 'Suggested Donation 3 (₦)',
            'description' => 'Third suggested donation amount in Naira.',
        ],
        'support_banner_text' => [
            'group' => 'content',
            'type' => 'string',
            'default' => 'Support our sisterhood →',
            'label' => 'Support Banner Text',
            'description' => 'Text displayed in the support banner on the member home dashboard.',
        ],
        'event_reminder_hours_before' => [
            'group' => 'events',
            'type' => 'int',
            'default' => 24,
            'label' => 'Event Reminder Hours Before',
            'description' => 'Hours before the event to send the reminder notification.',
        ],
        'brand_name' => [
            'group' => 'branding',
            'type' => 'string',
            'default' => 'The Muhsinat Club',
            'label' => 'Brand Name',
            'description' => 'Displayed in the admin panel header and login screen.',
        ],
        'brand_primary_color' => [
            'group' => 'branding',
            'type' => 'string',
            'default' => '#1A6B72',
            'label' => 'Primary Color',
            'description' => 'Hex color code used for the admin panel theme (e.g. #1A6B72).',
        ],
        'dashboard_active_window_days' => [
            'group' => 'dashboard',
            'type' => 'int',
            'default' => 30,
            'label' => 'Active Members Window (Days)',
            'description' => 'Number of days back to count a member as "active" on the dashboard.',
        ],
    ];

    public static function all(): array
    {
        return self::KEYS;
    }

    public static function groups(): array
    {
        return self::GROUPS;
    }

    public static function keysByGroup(): array
    {
        $grouped = [];
        foreach (self::KEYS as $key => $config) {
            $grouped[$config['group']][] = $key;
        }
        return $grouped;
    }

    public static function has(string $key): bool
    {
        return isset(self::KEYS[$key]);
    }

    public static function default(string $key): mixed
    {
        return self::KEYS[$key]['default'] ?? null;
    }

    public static function type(string $key): ?string
    {
        return self::KEYS[$key]['type'] ?? null;
    }

    public static function label(string $key): ?string
    {
        return self::KEYS[$key]['label'] ?? null;
    }

    public static function description(string $key): ?string
    {
        return self::KEYS[$key]['description'] ?? null;
    }

    public static function group(string $key): ?string
    {
        return self::KEYS[$key]['group'] ?? null;
    }

    public static function cast(string $key, mixed $value): mixed
    {
        $type = self::type($key);

        return match ($type) {
            'int' => (int) $value,
            'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? (bool) $value,
            'string', 'text' => (string) $value,
            default => $value,
        };
    }
}
