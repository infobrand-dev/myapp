@extends('layouts.admin')

@section('title', 'Brand Page')

@section('content')
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <div class="page-pretitle">Storefront</div>
                <h2 class="page-title">Brand Page</h2>
                <p class="text-muted mb-0">Atur identitas publik tenant, urutan section, CTA, testimonial, dan FAQ tanpa mencampur route public dengan app shell.</p>
            </div>
            <div class="col-auto d-flex gap-2">
                <a href="{{ route('storefront.public.index') }}" class="btn btn-outline-secondary">Lihat Halaman Publik</a>
                <a href="{{ route('storefront.offers.index') }}" class="btn btn-primary">Kelola Offers</a>
            </div>
        </div>
    </div>

    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('storefront.brand.update') }}">
        @csrf
        @method('PUT')

        <div class="row g-3">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header"><h3 class="card-title mb-0">Identitas Brand</h3></div>
                    <div class="card-body row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Brand Name</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $profile['name']) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Accent Color</label>
                            <input type="text" name="accent" class="form-control" value="{{ old('accent', $profile['accent']) }}" placeholder="#223756">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3">{{ old('description', $profile['description']) }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Hero Title</label>
                            <input type="text" name="hero_title" class="form-control" value="{{ old('hero_title', $profile['hero_title']) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Hero Subtitle</label>
                            <textarea name="hero_subtitle" class="form-control" rows="3">{{ old('hero_subtitle', $profile['hero_subtitle']) }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mt-3">
                    <div class="card-header"><h3 class="card-title mb-0">CTA & Footer Links</h3></div>
                    <div class="card-body">
                        @for($i = 0; $i < 3; $i++)
                            <div class="row g-2 mb-3">
                                <div class="col-md-4">
                                    <input type="text" name="cta_links[{{ $i }}][label]" class="form-control" placeholder="CTA label" value="{{ old("cta_links.$i.label", $profile['cta_links'][$i]['label'] ?? '') }}">
                                </div>
                                <div class="col-md-8">
                                    <input type="text" name="cta_links[{{ $i }}][url]" class="form-control" placeholder="https://..." value="{{ old("cta_links.$i.url", $profile['cta_links'][$i]['url'] ?? '') }}">
                                </div>
                            </div>
                        @endfor

                        <hr>

                        @for($i = 0; $i < 4; $i++)
                            <div class="row g-2 mb-3">
                                <div class="col-md-4">
                                    <input type="text" name="footer_links[{{ $i }}][label]" class="form-control" placeholder="Footer link label" value="{{ old("footer_links.$i.label", $profile['footer_links'][$i]['label'] ?? '') }}">
                                </div>
                                <div class="col-md-8">
                                    <input type="text" name="footer_links[{{ $i }}][url]" class="form-control" placeholder="https://..." value="{{ old("footer_links.$i.url", $profile['footer_links'][$i]['url'] ?? '') }}">
                                </div>
                            </div>
                        @endfor
                    </div>
                </div>

                <div class="card border-0 shadow-sm mt-3">
                    <div class="card-header"><h3 class="card-title mb-0">Testimonials & FAQ</h3></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <div class="fw-semibold mb-2">Testimonials</div>
                                @for($i = 0; $i < 3; $i++)
                                    <div class="row g-2 mb-3">
                                        <div class="col-md-8">
                                            <textarea name="testimonials[{{ $i }}][quote]" class="form-control" rows="2" placeholder="Quote">{{ old("testimonials.$i.quote", $profile['testimonials'][$i]['quote'] ?? '') }}</textarea>
                                        </div>
                                        <div class="col-md-4">
                                            <input type="text" name="testimonials[{{ $i }}][author]" class="form-control" placeholder="Author" value="{{ old("testimonials.$i.author", $profile['testimonials'][$i]['author'] ?? '') }}">
                                        </div>
                                    </div>
                                @endfor
                            </div>

                            <div class="col-12">
                                <div class="fw-semibold mb-2">FAQ</div>
                                @for($i = 0; $i < 4; $i++)
                                    <div class="row g-2 mb-3">
                                        <div class="col-md-5">
                                            <input type="text" name="faq[{{ $i }}][question]" class="form-control" placeholder="Question" value="{{ old("faq.$i.question", $profile['faq'][$i]['question'] ?? '') }}">
                                        </div>
                                        <div class="col-md-7">
                                            <textarea name="faq[{{ $i }}][answer]" class="form-control" rows="2" placeholder="Answer">{{ old("faq.$i.answer", $profile['faq'][$i]['answer'] ?? '') }}</textarea>
                                        </div>
                                    </div>
                                @endfor
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header"><h3 class="card-title mb-0">Section Order</h3></div>
                    <div class="card-body">
                        @foreach($profile['sections'] as $index => $section)
                            <div class="border rounded p-3 mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="fw-semibold">{{ $section['label'] }}</div>
                                    <div class="form-check mb-0">
                                        <input type="hidden" name="sections[{{ $section['key'] }}][enabled]" value="0">
                                        <input class="form-check-input" type="checkbox" name="sections[{{ $section['key'] }}][enabled]" value="1" @checked(old("sections.{$section['key']}.enabled", $section['enabled']))>
                                    </div>
                                </div>
                                <input type="number" min="1" max="999" name="sections[{{ $section['key'] }}][order]" class="form-control" value="{{ old("sections.{$section['key']}.order", $section['order']) }}">
                            </div>
                        @endforeach

                        <button type="submit" class="btn btn-primary w-100">Simpan Brand Page</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection
