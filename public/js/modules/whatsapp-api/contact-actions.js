/******/ (() => { // webpackBootstrap
/*!*****************************************************************!*\
  !*** ./app/Modules/WhatsAppApi/resources/js/contact-actions.js ***!
  \*****************************************************************/
document.addEventListener('DOMContentLoaded', function () {
  var configEl = document.getElementById('wa-contact-action-config');
  var modalEl = document.getElementById('wa-contact-action-modal');
  var form = document.getElementById('wa-contact-action-form');
  if (!configEl || !modalEl || !form) {
    return;
  }
  var config = {
    instances: [],
    templates: [],
    defaults: {},
    contactFieldOptions: {},
    senderFieldOptions: {},
    senderContext: {}
  };
  try {
    config = JSON.parse(configEl.textContent || '{}');
  } catch (_) {
    return;
  }
  var contactIdInput = document.getElementById('wa-contact-id');
  var returnToInput = document.getElementById('wa-contact-return-to');
  var subtitleEl = document.getElementById('wa-contact-action-subtitle');
  var instanceSelect = document.getElementById('wa-contact-instance');
  var templateSelect = document.getElementById('wa-contact-template');
  var hintEl = document.getElementById('wa-contact-template-hint');
  var variablesWrap = document.getElementById('wa-contact-variables');
  var variablesEmpty = document.getElementById('wa-contact-variables-empty');
  var previewContactName = document.getElementById('wa-preview-contact-name');
  var previewContactPhone = document.getElementById('wa-preview-contact-phone');
  var previewHeaderContact = document.getElementById('wa-preview-header-contact');
  var previewTemplateName = document.getElementById('wa-preview-template-name');
  var previewMedia = document.getElementById('wa-preview-media');
  var previewHeader = document.getElementById('wa-preview-header');
  var previewBody = document.getElementById('wa-preview-body');
  var previewFooter = document.getElementById('wa-preview-footer');
  var previewButtons = document.getElementById('wa-preview-buttons');
  var state = {
    contact: null,
    templates: Array.isArray(config.templates) ? config.templates : [],
    contactFieldOptions: config.contactFieldOptions || {},
    senderFieldOptions: config.senderFieldOptions || {},
    senderContext: config.senderContext || {}
  };
  var esc = function esc(value) {
    return String(value || '').replace(/[&<>"']/g, function (_char) {
      return {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        '\'': '&#39;'
      }[_char] || _char;
    });
  };
  var contactContext = function contactContext() {
    var _state$contact, _state$contact2, _state$contact3, _state$contact4, _state$contact5, _state$contact6, _state$contact7, _state$contact8, _state$contact9, _state$contact0, _state$contact1;
    return {
      name: ((_state$contact = state.contact) === null || _state$contact === void 0 ? void 0 : _state$contact.name) || '',
      mobile: ((_state$contact2 = state.contact) === null || _state$contact2 === void 0 ? void 0 : _state$contact2.phone) || '',
      phone: ((_state$contact3 = state.contact) === null || _state$contact3 === void 0 ? void 0 : _state$contact3.phone) || '',
      email: ((_state$contact4 = state.contact) === null || _state$contact4 === void 0 ? void 0 : _state$contact4.email) || '',
      company_name: ((_state$contact5 = state.contact) === null || _state$contact5 === void 0 ? void 0 : _state$contact5.company) || '',
      job_title: ((_state$contact6 = state.contact) === null || _state$contact6 === void 0 ? void 0 : _state$contact6.jobTitle) || '',
      website: ((_state$contact7 = state.contact) === null || _state$contact7 === void 0 ? void 0 : _state$contact7.website) || '',
      industry: ((_state$contact8 = state.contact) === null || _state$contact8 === void 0 ? void 0 : _state$contact8.industry) || '',
      city: ((_state$contact9 = state.contact) === null || _state$contact9 === void 0 ? void 0 : _state$contact9.city) || '',
      state: ((_state$contact0 = state.contact) === null || _state$contact0 === void 0 ? void 0 : _state$contact0.state) || '',
      country: ((_state$contact1 = state.contact) === null || _state$contact1 === void 0 ? void 0 : _state$contact1.country) || ''
    };
  };
  var senderContext = function senderContext() {
    var _state$senderContext, _state$senderContext2, _state$senderContext3, _state$senderContext4, _state$senderContext5;
    return {
      name: ((_state$senderContext = state.senderContext) === null || _state$senderContext === void 0 ? void 0 : _state$senderContext.name) || '',
      email: ((_state$senderContext2 = state.senderContext) === null || _state$senderContext2 === void 0 ? void 0 : _state$senderContext2.email) || '',
      phone: ((_state$senderContext3 = state.senderContext) === null || _state$senderContext3 === void 0 ? void 0 : _state$senderContext3.phone) || '',
      mobile: ((_state$senderContext4 = state.senderContext) === null || _state$senderContext4 === void 0 ? void 0 : _state$senderContext4.mobile) || '',
      avatar: ((_state$senderContext5 = state.senderContext) === null || _state$senderContext5 === void 0 ? void 0 : _state$senderContext5.avatar) || ''
    };
  };
  var resolveMappingValue = function resolveMappingValue(mapping) {
    var sourceType = String((mapping === null || mapping === void 0 ? void 0 : mapping.source_type) || 'text');
    var fallback = String((mapping === null || mapping === void 0 ? void 0 : mapping.fallback_value) || '').trim();
    if (sourceType === 'contact_field') {
      var field = String((mapping === null || mapping === void 0 ? void 0 : mapping.contact_field) || 'name');
      var value = String(contactContext()[field] || '').trim();
      return value || fallback;
    }
    if (sourceType === 'sender_field') {
      var _field = String((mapping === null || mapping === void 0 ? void 0 : mapping.sender_field) || 'name');
      var _value = String(senderContext()[_field] || '').trim();
      return _value || fallback;
    }
    var textValue = String((mapping === null || mapping === void 0 ? void 0 : mapping.text_value) || '').trim();
    return textValue || fallback;
  };
  var defaultVariableValue = function defaultVariableValue(index, template) {
    var mappings = (template === null || template === void 0 ? void 0 : template.variable_mappings) || {};
    var mapping = mappings[index] || mappings[String(index)] || null;
    return resolveMappingValue(mapping);
  };
  var interpolate = function interpolate(text, variables) {
    return String(text || '').replace(/\{\{(\d+)\}\}/g, function (_, rawIndex) {
      var index = Number(rawIndex || 0);
      return variables[index] || '';
    });
  };
  var selectedTemplate = function selectedTemplate() {
    var id = Number(templateSelect.value || 0);
    return state.templates.find(function (template) {
      return Number(template.id) === id;
    }) || null;
  };
  var currentVariables = function currentVariables() {
    var values = {};
    variablesWrap.querySelectorAll('input[data-var-index]').forEach(function (input) {
      values[Number(input.dataset.varIndex || 0)] = input.value.trim();
    });
    return values;
  };
  var renderButtons = function renderButtons(buttons) {
    previewButtons.innerHTML = '';
    if (!Array.isArray(buttons) || buttons.length === 0) {
      previewButtons.style.display = 'none';
      return;
    }
    buttons.forEach(function (button) {
      var item = document.createElement('div');
      item.className = 'wa-btn';
      var kind = String(button.type || '').replace(/_/g, ' ');
      item.innerHTML = "<span>".concat(button.text || 'Button', "</span><small>").concat(kind, "</small>");
      previewButtons.appendChild(item);
    });
    previewButtons.style.display = 'flex';
  };
  var renderPreview = function renderPreview() {
    var template = selectedTemplate();
    var values = currentVariables();
    previewTemplateName.textContent = template ? "".concat(template.name, " (").concat(template.language, ")") : 'Template';
    if (!template) {
      previewMedia.style.display = 'none';
      previewHeader.style.display = 'none';
      previewFooter.style.display = 'none';
      previewButtons.style.display = 'none';
      previewBody.textContent = 'Pilih template untuk melihat preview.';
      return;
    }
    var components = Array.isArray(template.components) ? template.components : [];
    var header = components.find(function (component) {
      return String(component.type || '').toLowerCase() === 'header';
    }) || null;
    var footer = components.find(function (component) {
      return String(component.type || '').toLowerCase() === 'footer';
    }) || null;
    var buttons = (components.find(function (component) {
      return String(component.type || '').toLowerCase() === 'buttons';
    }) || {}).buttons || [];
    var headerText = header ? header.text || ((header.parameters || [])[0] || {}).text || '' : '';
    var headerMedia = header && Array.isArray(header.parameters) ? header.parameters[0] || null : null;
    if (headerMedia && headerMedia.link) {
      previewMedia.textContent = "".concat(String(headerMedia.type || 'media').toUpperCase(), ": ").concat(headerMedia.link);
      previewMedia.style.display = 'block';
    } else {
      previewMedia.style.display = 'none';
    }
    var computedHeader = interpolate(headerText, values).trim();
    if (computedHeader !== '') {
      previewHeader.textContent = computedHeader;
      previewHeader.style.display = 'block';
    } else {
      previewHeader.style.display = 'none';
    }
    var computedBody = interpolate(template.body || '', values).trim();
    previewBody.textContent = computedBody !== '' ? computedBody : '(isi body kosong)';
    var computedFooter = interpolate(footer && footer.text || '', values).trim();
    if (computedFooter !== '') {
      previewFooter.textContent = computedFooter;
      previewFooter.style.display = 'block';
    } else {
      previewFooter.style.display = 'none';
    }
    renderButtons(buttons);
  };
  var renderVariableInputs = function renderVariableInputs(template) {
    variablesWrap.innerHTML = '';
    var placeholders = Array.isArray(template === null || template === void 0 ? void 0 : template.placeholders) ? template.placeholders : [];
    if (placeholders.length === 0) {
      variablesEmpty.style.display = 'block';
      renderPreview();
      return;
    }
    variablesEmpty.style.display = 'none';
    placeholders.forEach(function (index) {
      var _template$variable_ma, _template$variable_ma2;
      var mapping = (template === null || template === void 0 || (_template$variable_ma = template.variable_mappings) === null || _template$variable_ma === void 0 ? void 0 : _template$variable_ma[index]) || (template === null || template === void 0 || (_template$variable_ma2 = template.variable_mappings) === null || _template$variable_ma2 === void 0 ? void 0 : _template$variable_ma2[String(index)]) || {};
      var sourceType = String(mapping.source_type || 'text');
      var sourceLabel = 'Free text';
      if (sourceType === 'contact_field') {
        sourceLabel = "Field Contact: ".concat(state.contactFieldOptions[mapping.contact_field] || mapping.contact_field || 'name');
      } else if (sourceType === 'sender_field') {
        sourceLabel = "Field User Pengirim: ".concat(state.senderFieldOptions[mapping.sender_field] || mapping.sender_field || 'name');
      }
      var fallbackLabel = String(mapping.fallback_value || '').trim();
      var row = document.createElement('label');
      row.className = 'form-label mb-0';
      row.innerHTML = "\n                <div class=\"small text-muted mb-1\">Variable {{".concat(index, "}}</div>\n                <input type=\"text\" class=\"form-control\" name=\"variables[").concat(index, "]\" data-var-index=\"").concat(index, "\" value=\"").concat(esc(defaultVariableValue(index, template)), "\">\n                <div class=\"form-hint mt-1\">").concat(esc(sourceLabel)).concat(fallbackLabel ? " | Fallback: ".concat(esc(fallbackLabel)) : '', "</div>\n            ");
      var input = row.querySelector('input');
      input === null || input === void 0 || input.addEventListener('input', renderPreview);
      variablesWrap.appendChild(row);
    });
    renderPreview();
  };
  var refreshTemplateOptions = function refreshTemplateOptions() {
    var _instanceSelect$selec;
    var namespace = (((_instanceSelect$selec = instanceSelect.selectedOptions[0]) === null || _instanceSelect$selec === void 0 ? void 0 : _instanceSelect$selec.dataset.namespace) || '').trim();
    var currentValue = templateSelect.value;
    var applicable = state.templates.filter(function (template) {
      if (!namespace || !template.namespace) return true;
      return String(template.namespace) === namespace;
    });
    templateSelect.innerHTML = '<option value="">Pilih template</option>';
    applicable.forEach(function (template) {
      var option = document.createElement('option');
      option.value = String(template.id);
      option.textContent = "".concat(template.name, " (").concat(template.language, ")");
      templateSelect.appendChild(option);
    });
    if (applicable.some(function (template) {
      return String(template.id) === currentValue;
    })) {
      templateSelect.value = currentValue;
    }
    hintEl.textContent = namespace ? 'Template disaring mengikuti namespace/WABA instance terpilih.' : 'Instance belum punya namespace khusus, semua template approved ditampilkan.';
    renderVariableInputs(selectedTemplate());
  };
  modalEl.addEventListener('show.bs.modal', function (event) {
    var trigger = event.relatedTarget;
    if (!trigger) {
      return;
    }
    state.contact = {
      id: trigger.dataset.contactId || '',
      name: trigger.dataset.contactName || '',
      phone: trigger.dataset.contactPhone || '',
      email: trigger.dataset.contactEmail || '',
      company: trigger.dataset.contactCompany || '',
      jobTitle: trigger.dataset.contactJobTitle || '',
      website: trigger.dataset.contactWebsite || '',
      industry: trigger.dataset.contactIndustry || '',
      city: trigger.dataset.contactCity || '',
      state: trigger.dataset.contactState || '',
      country: trigger.dataset.contactCountry || ''
    };
    contactIdInput.value = state.contact.id;
    returnToInput.value = trigger.dataset.returnTo || config.defaults.returnTo || window.location.href;
    subtitleEl.textContent = "".concat(state.contact.name || 'Contact', " - ").concat(state.contact.phone || '-');
    previewContactName.textContent = state.contact.name || 'Contact';
    previewContactPhone.textContent = state.contact.phone || '-';
    previewHeaderContact.textContent = state.contact.name || 'Contact';
    instanceSelect.selectedIndex = 0;
    refreshTemplateOptions();
  });
  instanceSelect.addEventListener('change', refreshTemplateOptions);
  templateSelect.addEventListener('change', function () {
    return renderVariableInputs(selectedTemplate());
  });
  form.addEventListener('reset', function () {
    variablesWrap.innerHTML = '';
    variablesEmpty.style.display = 'block';
  });
});
/******/ })()
;