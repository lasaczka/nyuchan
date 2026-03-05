<x-app-layout>
    <x-slot name="header">
        <h2>Dashboard</h2>
    </x-slot>

    <div class="stack">
        <div class="card">
            <p>You are logged in as <strong>{{ auth()->user()->username }}</strong>.</p>
            <form method="POST" action="{{ route('invites.create') }}">
                @csrf
                <button type="submit">Generate invite</button>
            </form>
            @error('invite')<div class="error">{{ $message }}</div>@enderror
        </div>

        <div class="card">
            <h3>Boards</h3>
            <ul class="list">
                @foreach(\App\Models\Board::orderBy('slug')->get() as $board)
                    <li><a href="{{ route('boards.show', ['board' => $board->slug]) }}">/{{ $board->slug }}/ {{ $board->title }}</a></li>
                @endforeach
            </ul>
        </div>
    </div>
</x-app-layout>
