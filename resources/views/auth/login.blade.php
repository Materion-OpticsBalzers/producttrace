<x-guest-layout>
    <div class="h-screen flex">
        <div class="flex-1 flex flex-col justify-center py-12 px-4 sm:px-6 lg:flex-none lg:px-20 xl:px-24">
            <div class="mx-auto w-full max-w-sm lg:w-96">
                <div>
                    <h2 class="mt-6 text-3xl font-extrabold text-[#373A36]">Product Tracking Login</h2>
                    <span class="text-sm text-gray-500">Logge dich mit deinem Materion Account ein</span>
                </div>

                <div class="mt-6">
                    <div class="mt-6">
                        <form action="{{ route('login') }}" method="POST" class="space-y-4">
                            @csrf()
                            <div>
                                <label for="personnel_number" class="block text-sm font-medium text-gray-700"> Personalnummer </label>
                                <div class="mt-1">
                                    <input id="personnel_number" name="personnel_number" value="{{ old('personnel_number') }}" type="text" class="w-full rounded-sm bg-[#D9E1E2] font-semibold text-sm shadow-sm border-0 focus:ring-[#0085CA]">
                                    @error('personnel_number') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <div class="space-y-1">
                                <label for="password" class="block text-sm font-medium text-gray-700"> Passwort </label>
                                <div class="mt-1">
                                    <input id="password" name="password" type="password" class="w-full rounded-sm bg-[#D9E1E2] font-semibold text-sm shadow-sm border-0 focus:ring-[#0085CA]">
                                    @error('password') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <input id="remember-me" name="remember-me" type="checkbox" class="h-4 w-4 text-[#0085CA] focus:ring-[#0085CA] border-0 bg-[#D9E1E2] rounded">
                                    <label for="remember-me" class="ml-2 block text-sm text-gray-900"> Angemeldet bleiben (14 Tage) </label>
                                </div>
                            </div>
                            <div>
                                <button type="submit" class="w-full rounded-sm bg-[#0085CA] hover:bg-[#0085CA]/80 shadow-md font-bold text-white py-1 px-3 uppercase text-left">Anmelden</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="hidden lg:block relative w-0 flex-1">
            <img class="absolute inset-0 h-full w-full object-cover" src="{{ asset('img/login.jpg') }}" alt="">
        </div>
    </div>
</x-guest-layout>
