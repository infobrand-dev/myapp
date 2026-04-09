@extends('layouts.landing')

@section('head_title', config('app.name') . ' - Platform bisnis untuk operasional, transaksi, dan workflow tim')
@section('head_description', 'Meetra adalah platform bisnis untuk operasional, transaksi, customer, dan workflow tim. Jalur pendaftaran utama yang aktif saat ini adalah Accounting.')

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
