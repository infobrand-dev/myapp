<form method="POST" action="{{ route('locale.switch') }}" class="d-inline">
    @csrf
    <div class="d-flex gap-1 align-items-center">
        <button type="submit" name="locale" value="en"
            class="btn btn-sm {{ app()->getLocale() === 'en' ? 'btn-primary' : 'btn-outline-secondary' }}">
            EN
        </button>
        <button type="submit" name="locale" value="id"
            class="btn btn-sm {{ app()->getLocale() === 'id' ? 'btn-primary' : 'btn-outline-secondary' }}">
            ID
        </button>
    </div>
</form>
