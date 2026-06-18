<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Microsoft Teams</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#0f1014] text-white flex items-center justify-center h-screen font-sans">

    <div class="w-full max-w-md p-8 bg-[#111318] border border-[#1c1d22] rounded-lg shadow-2xl">
        <div class="flex flex-col items-center mb-8">
            <!-- Simple Teams Logo Icon -->
            <div class="w-12 h-12 bg-[#5b5fc7] rounded-lg flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z"></path></svg>
            </div>
            <h1 class="text-2xl font-semibold">Sign in</h1>
            <p class="text-[#8b8d97] text-sm mt-2">Use your enterprise account</p>
        </div>

        <form action="{{ route('login.post') }}" method="POST" class="space-y-6">
            @csrf
            <div>
                <input type="email" name="email" placeholder="Email" required 
                    class="w-full bg-[#1a1c22] border-b-2 border-transparent focus:border-[#5b5fc7] outline-none px-3 py-2 text-sm transition-all">
                @error('email') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>

            <div>
                <input type="password" name="password" placeholder="Password" required 
                    class="w-full bg-[#1a1c22] border-b-2 border-transparent focus:border-[#5b5fc7] outline-none px-3 py-2 text-sm transition-all">
            </div>

            <div class="flex items-center justify-between text-xs text-[#8b8d97]">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" class="accent-[#5b5fc7]"> Remember me
                </label>
                <a href="#" class="hover:underline text-[#5b5fc7]">Forgot password?</a>
            </div>

            <button type="submit" class="w-full bg-[#5b5fc7] hover:bg-[#4d51ab] text-white font-semibold py-2 rounded transition-colors text-sm">
                Sign in
            </button>
        </form>
    </div>

</body>
</html>