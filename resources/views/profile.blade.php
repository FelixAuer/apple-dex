<x-dex-layout>
    <div class="py-6 px-4 space-y-6">
        <h1 class="text-xl font-semibold">{{ __('Profile') }}</h1>

        <div class="p-4 sm:p-6 bg-white dark:bg-gray-800 shadow-sm rounded-lg">
            <livewire:profile.update-profile-information-form />
        </div>

        <div class="p-4 sm:p-6 bg-white dark:bg-gray-800 shadow-sm rounded-lg">
            <livewire:profile.update-password-form />
        </div>

        <div class="p-4 sm:p-6 bg-white dark:bg-gray-800 shadow-sm rounded-lg">
            <livewire:profile.delete-user-form />
        </div>
    </div>
</x-dex-layout>
