@php
    $account = $account ?? new \App\Modules\Finance\Models\ChartOfAccount();
    $isAdvancedMode = ($accountingUiMode ?? 'standard') === 'advanced';
@endphp

<div class="row g-3">
    <div class="col-md-4">
        @include('shared.accounting.field-label', [
            'label' => 'Code',
            'required' => true,
            'tooltip' => 'Kode akun yang dipakai sebagai referensi journal dan laporan. Gunakan kode stabil seperti CASH, AR, AP, atau SALES.',
        ])
        <input type="text" name="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code', $account->code) }}" required>
        @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-8">
        @include('shared.accounting.field-label', [
            'label' => 'Account Name',
            'required' => true,
            'tooltip' => 'Nama akun yang tampil di chart of accounts, journal, dan report keuangan.',
        ])
        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $account->name) }}" required>
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        @include('shared.accounting.field-label', [
            'label' => 'Account Type',
            'required' => true,
            'tooltip' => 'Kelompok utama akun untuk kebutuhan laporan dan validasi struktur COA.',
        ])
        <select name="account_type" class="form-select @error('account_type') is-invalid @enderror" required>
            @foreach($typeOptions as $value => $label)
                <option value="{{ $value }}" @selected(old('account_type', $account->account_type ?: 'asset') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('account_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-check mt-4">
            <input type="checkbox" class="form-check-input" name="is_active" value="1" @checked(old('is_active', $account->exists ? $account->is_active : true))>
            <span class="form-check-label">Active</span>
        </label>
    </div>
    <div class="col-md-4">
        <label class="form-check mt-4">
            <input type="checkbox" class="form-check-input" name="is_postable" value="1" @checked(old('is_postable', $account->exists ? $account->is_postable : true))>
            <span class="form-check-label">Postable account</span>
        </label>
        <div class="form-hint">Jika dimatikan, akun ini dipakai sebagai header/group dan tidak seharusnya dipakai langsung di line journal.</div>
    </div>

    @if($isAdvancedMode)
        <div class="col-md-6">
            @include('shared.accounting.field-label', [
                'label' => 'Parent Account',
                'tooltip' => 'Gunakan parent untuk membentuk hierarki COA yang rapi, misalnya CASH di bawah ASSET.',
            ])
            <select name="parent_id" class="form-select @error('parent_id') is-invalid @enderror">
                <option value="">No parent</option>
                @foreach($parentOptions as $parent)
                    <option value="{{ $parent->id }}" @selected((string) old('parent_id', $account->parent_id) === (string) $parent->id)>{{ $parent->code }} - {{ $parent->name }}</option>
                @endforeach
            </select>
            @error('parent_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-3">
            @include('shared.accounting.field-label', [
                'label' => 'Normal Balance',
                'tooltip' => 'Tentukan apakah saldo normal akun berada di sisi debit atau credit.',
            ])
            <select name="normal_balance" class="form-select @error('normal_balance') is-invalid @enderror">
                @foreach($normalBalanceOptions as $value => $label)
                    <option value="{{ $value }}" @selected(old('normal_balance', $account->normal_balance ?: 'debit') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('normal_balance') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-3">
            @include('shared.accounting.field-label', [
                'label' => 'Report Section',
                'tooltip' => 'Menentukan akun ini masuk ke neraca atau laba rugi.',
            ])
            <select name="report_section" class="form-select @error('report_section') is-invalid @enderror">
                @foreach($reportSectionOptions as $value => $label)
                    <option value="{{ $value }}" @selected(old('report_section', $account->report_section ?: 'balance_sheet') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('report_section') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-3">
            @include('shared.accounting.field-label', [
                'label' => 'Sort Order',
                'tooltip' => 'Dipakai untuk mengatur urutan tampilan akun dalam daftar COA.',
            ])
            <input type="number" min="0" name="sort_order" class="form-control @error('sort_order') is-invalid @enderror" value="{{ old('sort_order', $account->sort_order ?? 0) }}">
            @error('sort_order') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-12">
            @include('shared.accounting.field-label', [
                'label' => 'Description',
                'tooltip' => 'Catatan internal untuk membantu tim memahami tujuan akun ini.',
            ])
            <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="3">{{ old('description', $account->description) }}</textarea>
            @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    @else
        <input type="hidden" name="normal_balance" value="{{ old('normal_balance', $account->normal_balance ?: 'debit') }}">
        <input type="hidden" name="report_section" value="{{ old('report_section', $account->report_section ?: 'balance_sheet') }}">
        <input type="hidden" name="sort_order" value="{{ old('sort_order', $account->sort_order ?? 0) }}">
    @endif
</div>
