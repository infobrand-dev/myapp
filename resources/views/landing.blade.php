@extends('layouts.landing')

@section('head_title', config('app.name') . ' — Omnichannel Inbox untuk Tim Sales & Support')
@section('head_description', 'Satukan percakapan WhatsApp, Instagram/Facebook DM, live chat, dan chatbot AI dalam satu workspace. Balas lebih cepat, lead tidak tercecer, tim lebih fokus.')

@section('content')
@include('partials.landing-main-sections')
@endsection

@push('scripts')
<script>
(function () {
    var tabBtns = document.querySelectorAll('.pricing-tab-btn');
    var tabPanes = document.querySelectorAll('.pricing-tab-pane');
    tabBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var target = btn.dataset.tab;
            tabBtns.forEach(function (b) { b.classList.remove('active'); });
            tabPanes.forEach(function (p) {
                p.classList.toggle('active', p.dataset.pane === target);
            });
            btn.classList.add('active');
        });
    });
})();
</script>
@endpush
