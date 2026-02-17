{{-- Create/Edit UTM Code Modal --}}
<div class="modal fade" id="createUtmModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-plus-circle mr-2"></i><span id="modalTitle">Add UTM Code</span>
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="utmCodeForm">
                <input type="hidden" id="utmCodeId" name="id">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="domainName">Domain Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="domainName" name="domain_name"
                            placeholder="e.g., google.com or www.google.com" required>
                        <small class="form-text text-muted">
                            Enter the domain name (e.g., google.com). The system will automatically match URLs from this
                            domain.
                        </small>
                    </div>
                    <div class="form-group">
                        <label>UTM Codes <span class="text-danger">*</span></label>
                        @foreach (get_options('utm_keys') as $key => $label)
                            <div class="d-flex align-items-end mb-2 utm-code-row" data-utm-key="{{ $key }}">
                                <div class="col-md-4 pr-2">
                                    <label class="small">UTM Key</label>
                                    <select class="form-control utm-key-select"
                                        name="utm_codes[{{ $key }}][key]" data-key="{{ $key }}"
                                        required>
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    </select>
                                </div>
                                <div class="utm-value-col col-md-8">
                                    <label class="small">UTM Value</label>
                                    @if ($key == 'utm_source')
                                        <input type="text" class="form-control utm-value-input"
                                            name="utm_codes[{{ $key }}][value]" data-key="{{ $key }}"
                                            placeholder="engagyo" value="engagyo" readonly>
                                    @else
                                        <select class="form-control utm-value-input"
                                            name="utm_codes[{{ $key }}][value]" data-key="{{ $key }}">
                                            <option value="">-- Select Value --</option>
                                            @foreach (get_options('utm_values') as $utmValueKey => $utmValueLabel)
                                                <option value="{{ $utmValueKey }}">{{ $utmValueLabel }}</option>
                                            @endforeach
                                        </select>
                                    @endif
                                </div>
                                @if ($key != 'utm_source')
                                    <div class="utm-custom-value-col col-md-4 d-none">
                                        <label class="small">Custom Value</label>
                                        <input type="text" class="form-control utm-custom-value-input"
                                            name="utm_codes[{{ $key }}][custom_value]"
                                            data-key="{{ $key }}" placeholder="Enter custom value">
                                    </div>
                                @endif
                            </div>
                        @endforeach
                        <div class="mt-2 mb-2">
                            <button type="button" class="btn btn-sm btn-outline-primary" id="addUtmCodeBtn">
                                <i class="fas fa-plus mr-1"></i> Add UTM Code
                            </button>
                        </div>
                        <div id="customUtmRowsContainer"></div>
                        <small class="form-text text-muted d-block mt-2">
                            Configure UTM parameters that will be appended to matching URLs. Leave value empty to skip a
                            parameter.
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="saveUtmBtn">
                        <i class="fas fa-save mr-1"></i> Save UTM Code
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
