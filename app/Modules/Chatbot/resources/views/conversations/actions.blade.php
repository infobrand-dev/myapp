@if(($showPause ?? false) && Route::has('chatbot.conversations.pause-bot'))
    <form method="POST" action="{{ route('chatbot.conversations.pause-bot', $conversation) }}" class="d-inline">
        @csrf
        <button class="btn btn-outline-warning" type="submit">Pause AI</button>
    </form>
@endif

@if(($showResume ?? false) && Route::has('chatbot.conversations.resume-bot'))
    <form method="POST" action="{{ route('chatbot.conversations.resume-bot', $conversation) }}" class="d-inline">
        @csrf
        <button class="btn btn-outline-success" type="submit">Resume AI</button>
    </form>
@endif
