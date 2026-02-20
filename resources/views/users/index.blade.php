<x-app-layout>
    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <h1 class="text-2xl font-bold">Foydalanuvchilar</h1>

            <form method="POST" action="{{ route('users.store') }}" class="grid grid-cols-1 gap-3 rounded-xl border bg-white p-4 sm:grid-cols-2 xl:grid-cols-5">
                @csrf
                <input name="name" class="rounded border p-2" placeholder="Ism" required>
                <input name="email" type="email" class="rounded border p-2" placeholder="Email" required>
                <select name="role" class="rounded border p-2" required>
                    @foreach ($roles as $role)
                        <option value="{{ $role }}">{{ $role }}</option>
                    @endforeach
                </select>
                <input name="password" type="password" class="rounded border p-2" placeholder="Parol" required>
                <input name="password_confirmation" type="password" class="rounded border p-2" placeholder="Parol tasdiqi" required>
                <button class="rounded bg-slate-900 px-4 py-2 text-white sm:col-span-2 xl:col-span-5 xl:w-48">Qo'shish</button>
            </form>

            <div class="overflow-x-auto rounded-xl border bg-white">
                <table class="min-w-[900px] w-full text-sm">
                    <thead class="bg-slate-50">
                    <tr>
                        <th class="p-3 text-left">Ism</th>
                        <th class="p-3 text-left">Email</th>
                        <th class="p-3 text-left">Rol</th>
                        <th class="p-3 text-left">Yangilash</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($users as $user)
                        <tr class="border-t align-top">
                            <td class="p-3">{{ $user->name }}</td>
                            <td class="p-3">{{ $user->email }}</td>
                            <td class="p-3">{{ $user->role }}</td>
                            <td class="p-3">
                                <form method="POST" action="{{ route('users.update', $user) }}" class="grid grid-cols-1 gap-2 md:grid-cols-4">
                                    @csrf
                                    @method('PATCH')
                                    <input name="name" value="{{ $user->name }}" class="rounded border p-1" required>
                                    <input name="email" type="email" value="{{ $user->email }}" class="rounded border p-1" required>
                                    <select name="role" class="rounded border p-1" required>
                                        @foreach ($roles as $role)
                                            <option value="{{ $role }}" @selected($user->role === $role)>{{ $role }}</option>
                                        @endforeach
                                    </select>
                                    <button class="rounded bg-blue-700 px-3 py-1 text-white">Saqlash</button>
                                    <input name="password" type="password" class="rounded border p-1 md:col-span-2" placeholder="Yangi parol (ixtiyoriy)">
                                    <input name="password_confirmation" type="password" class="rounded border p-1 md:col-span-2" placeholder="Parol tasdiqi">
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            {{ $users->links() }}
        </div>
    </div>
</x-app-layout>
