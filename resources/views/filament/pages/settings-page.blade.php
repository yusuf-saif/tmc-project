<x-filament-panels::page>
    <div class="space-y-6 rounded-xl bg-white p-6 shadow-sm">
        <div>
            <label class="mb-2 block text-sm font-medium text-gray-700">Bank Details</label>
            <textarea wire:model="bankDetails" rows="5" class="w-full rounded-lg border-gray-300 shadow-sm"></textarea>
        </div>
        <div>
            <label class="mb-2 block text-sm font-medium text-gray-700">Donate Message</label>
            <textarea wire:model="donateMessage" rows="4" class="w-full rounded-lg border-gray-300 shadow-sm"></textarea>
        </div>
        <div class="grid gap-4 md:grid-cols-3">
            <div>
                <label class="mb-2 block text-sm font-medium text-gray-700">Starter Coins Amount</label>
                <input type="number" min="1" wire:model="starterCoinsAmount" class="w-full rounded-lg border-gray-300 shadow-sm">
            </div>
            <div>
                <label class="mb-2 block text-sm font-medium text-gray-700">Referral Coins Amount</label>
                <input type="number" min="1" wire:model="referralCoinsAmount" class="w-full rounded-lg border-gray-300 shadow-sm">
            </div>
            <div>
                <label class="mb-2 block text-sm font-medium text-gray-700">Membership Approval Coins</label>
                <input type="number" min="0" wire:model="membershipApprovalCoins" class="w-full rounded-lg border-gray-300 shadow-sm">
            </div>
        </div>
        <div>
            <x-filament::button wire:click="save">Save</x-filament::button>
        </div>
    </div>
</x-filament-panels::page>
