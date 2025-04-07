@extends('admin.layouts.secure')
@section('page_title', 'Edit Promo Code')
@section('page_content')
    @can('edit_promocode')
        <div class="page-content">
            <form method="POST" action="{{ route('promo-code.update', $promo->id) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="content-header clearfix">
                    <h1 class="float-left"> Edit Promo Code
                        <small>
                            <i class="fas fa-arrow-circle-left"></i>
                            <a href="{{ route('promo-code.index') }}">back to Promo Codes list</a>
                        </small>
                    </h1>
                    <div class="float-right">
                        <button type="submit" name="action" value="save" class="btn btn-primary">
                            <i class="far fa-save"></i>
                            Save
                        </button>
                    </div>
                </div>
                <section class="content">
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <div class="card-title">
                                            <i class="fas fa-info"></i>
                                            Info
                                        </div>
                                        <div class="card-tools">
                                            <button type="button" class="btn btn-tool" data-card-widget="collapse"
                                                title="Collapse">
                                                <i class="fas fa-minus"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group row">
                                            <div class="col-md-3">
                                                <label for="name" class="form-label">Name</label>
                                            </div>
                                            <div class="col-md-9">
                                                <input type="text" class="form-control" name="name" id="name"
                                                    value="{{ old('name', $promo->name) }}" placeholder="Enter promo code name"
                                                    required>
                                                @error('name')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <div class="col-md-3">
                                                <label for="code" class="form-label">Code</label>
                                            </div>
                                            <div class="col-md-9">
                                                <input type="text" class="form-control" name="code" id="code"
                                                    value="{{ old('code', $promo->code) }}" placeholder="Enter promo code code" required>
                                                @error('code')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <div class="col-md-3">
                                                <label for="duration" class="form-label">Duration (Days)</label>
                                            </div>
                                            <div class="col-md-9">
                                                <input type="number" class="form-control" name="duration" id="duration"
                                                    value="{{ old('duration', $promo->duration) }}"
                                                    placeholder="Enter promo code duration" required>
                                                @error('duration')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <div class="col-md-3">
                                                <label for="discount_type" class="form-label">Discount Type</label>
                                            </div>
                                            <div class="col-md-9">
                                                <select name="discount_type" id="discount_type" class="form-control">
                                                    <option value="">Select Type</option>
                                                    <option value="%"
                                                        {{ old('discount_type', $promo->discount_type) == '%' ? 'selected' : '' }}>
                                                        %
                                                    </option>
                                                    <option value="fix"
                                                        {{ old('discount_type', $promo->discount_type) == 'fix' ? 'selected' : '' }}>
                                                        Fix
                                                    </option>
                                                </select>
                                                @error('discount_type')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <div class="col-md-3">
                                                <label for="discount_amount" class="form-label">Discount Amount</label>
                                            </div>
                                            <div class="col-md-9">
                                                <input type="number" class="form-control" name="discount_amount"
                                                    id="discount_amount"
                                                    value="{{ old('discount_amount', $promo->discount_amount) }}"
                                                    placeholder="Enter code discount amount" required>
                                                @error('discount_amount')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <div class="col-md-3">
                                                <label for="active" class="form-label">Is Active</label>
                                            </div>
                                            <div class="col-md-9">
                                                <div class="form-check">
                                                    <input type="hidden" name="active" value="0">
                                                    <input type="checkbox" id="activeCheckbox" name="active"
                                                        class="form-check-input" value="1"
                                                        {{ old('active', $promo->status) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="active">Yes</label>
                                                    @error('active')
                                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </form>
        </div>
    @endcan
@endsection
@push('scripts')
    <script></script>
@endpush
