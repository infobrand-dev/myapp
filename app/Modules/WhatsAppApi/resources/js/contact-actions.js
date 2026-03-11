document.addEventListener('DOMContentLoaded', () => {
    const configEl = document.getElementById('wa-contact-action-config');
    const modalEl = document.getElementById('wa-contact-action-modal');
    const form = document.getElementById('wa-contact-action-form');

    if (!configEl || !modalEl || !form) {
        return;
    }

    let config = { instances: [], templates: [], defaults: {}, contactFieldOptions: {}, senderFieldOptions: {}, senderContext: {} };
    try {
        config = JSON.parse(configEl.textContent || '{}');
    } catch (_) {
        return;
    }

    const contactIdInput = document.getElementById('wa-contact-id');
    const returnToInput = document.getElementById('wa-contact-return-to');
    const subtitleEl = document.getElementById('wa-contact-action-subtitle');
    const instanceSelect = document.getElementById('wa-contact-instance');
    const templateSelect = document.getElementById('wa-contact-template');
    const hintEl = document.getElementById('wa-contact-template-hint');
    const variablesWrap = document.getElementById('wa-contact-variables');
    const variablesEmpty = document.getElementById('wa-contact-variables-empty');
    const previewContactName = document.getElementById('wa-preview-contact-name');
    const previewContactPhone = document.getElementById('wa-preview-contact-phone');
    const previewHeaderContact = document.getElementById('wa-preview-header-contact');
    const previewTemplateName = document.getElementById('wa-preview-template-name');
    const previewMedia = document.getElementById('wa-preview-media');
    const previewHeader = document.getElementById('wa-preview-header');
    const previewBody = document.getElementById('wa-preview-body');
    const previewFooter = document.getElementById('wa-preview-footer');
    const previewButtons = document.getElementById('wa-preview-buttons');

    const state = {
        contact: null,
        templates: Array.isArray(config.templates) ? config.templates : [],
        contactFieldOptions: config.contactFieldOptions || {},
        senderFieldOptions: config.senderFieldOptions || {},
        senderContext: config.senderContext || {},
    };
    const esc = (value) => String(value || '').replace(/[&<>"']/g, (char) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        '\'': '&#39;',
    }[char] || char));

    const contactContext = () => ({
        name: state.contact?.name || '',
        mobile: state.contact?.phone || '',
        phone: state.contact?.phone || '',
        email: state.contact?.email || '',
        company_name: state.contact?.company || '',
        job_title: state.contact?.jobTitle || '',
        website: state.contact?.website || '',
        industry: state.contact?.industry || '',
        city: state.contact?.city || '',
        state: state.contact?.state || '',
        country: state.contact?.country || '',
    });

    const senderContext = () => ({
        name: state.senderContext?.name || '',
        email: state.senderContext?.email || '',
        phone: state.senderContext?.phone || '',
        mobile: state.senderContext?.mobile || '',
        avatar: state.senderContext?.avatar || '',
    });

    const resolveMappingValue = (mapping) => {
        const sourceType = String(mapping?.source_type || 'text');
        const fallback = String(mapping?.fallback_value || '').trim();

        if (sourceType === 'contact_field') {
            const field = String(mapping?.contact_field || 'name');
            const value = String(contactContext()[field] || '').trim();
            return value || fallback;
        }

        if (sourceType === 'sender_field') {
            const field = String(mapping?.sender_field || 'name');
            const value = String(senderContext()[field] || '').trim();
            return value || fallback;
        }

        const textValue = String(mapping?.text_value || '').trim();
        return textValue || fallback;
    };

    const defaultVariableValue = (index, template) => {
        const mappings = template?.variable_mappings || {};
        const mapping = mappings[index] || mappings[String(index)] || null;
        return resolveMappingValue(mapping);
    };

    const interpolate = (text, variables) => String(text || '').replace(/\{\{(\d+)\}\}/g, (_, rawIndex) => {
        const index = Number(rawIndex || 0);
        return variables[index] || '';
    });

    const selectedTemplate = () => {
        const id = Number(templateSelect.value || 0);
        return state.templates.find((template) => Number(template.id) === id) || null;
    };

    const currentVariables = () => {
        const values = {};
        variablesWrap.querySelectorAll('input[data-var-index]').forEach((input) => {
            values[Number(input.dataset.varIndex || 0)] = input.value.trim();
        });
        return values;
    };

    const renderButtons = (buttons) => {
        previewButtons.innerHTML = '';
        if (!Array.isArray(buttons) || buttons.length === 0) {
            previewButtons.style.display = 'none';
            return;
        }

        buttons.forEach((button) => {
            const item = document.createElement('div');
            item.className = 'wa-btn';
            const kind = String(button.type || '').replace(/_/g, ' ');
            item.innerHTML = `<span>${button.text || 'Button'}</span><small>${kind}</small>`;
            previewButtons.appendChild(item);
        });

        previewButtons.style.display = 'flex';
    };

    const renderPreview = () => {
        const template = selectedTemplate();
        const values = currentVariables();

        previewTemplateName.textContent = template ? `${template.name} (${template.language})` : 'Template';

        if (!template) {
            previewMedia.style.display = 'none';
            previewHeader.style.display = 'none';
            previewFooter.style.display = 'none';
            previewButtons.style.display = 'none';
            previewBody.textContent = 'Pilih template untuk melihat preview.';
            return;
        }

        const components = Array.isArray(template.components) ? template.components : [];
        const header = components.find((component) => String(component.type || '').toLowerCase() === 'header') || null;
        const footer = components.find((component) => String(component.type || '').toLowerCase() === 'footer') || null;
        const buttons = (components.find((component) => String(component.type || '').toLowerCase() === 'buttons') || {}).buttons || [];
        const headerText = header ? (header.text || (((header.parameters || [])[0] || {}).text || '')) : '';
        const headerMedia = header && Array.isArray(header.parameters) ? header.parameters[0] || null : null;

        if (headerMedia && headerMedia.link) {
            previewMedia.textContent = `${String(headerMedia.type || 'media').toUpperCase()}: ${headerMedia.link}`;
            previewMedia.style.display = 'block';
        } else {
            previewMedia.style.display = 'none';
        }

        const computedHeader = interpolate(headerText, values).trim();
        if (computedHeader !== '') {
            previewHeader.textContent = computedHeader;
            previewHeader.style.display = 'block';
        } else {
            previewHeader.style.display = 'none';
        }

        const computedBody = interpolate(template.body || '', values).trim();
        previewBody.textContent = computedBody !== '' ? computedBody : '(isi body kosong)';

        const computedFooter = interpolate((footer && footer.text) || '', values).trim();
        if (computedFooter !== '') {
            previewFooter.textContent = computedFooter;
            previewFooter.style.display = 'block';
        } else {
            previewFooter.style.display = 'none';
        }

        renderButtons(buttons);
    };

    const renderVariableInputs = (template) => {
        variablesWrap.innerHTML = '';
        const placeholders = Array.isArray(template?.placeholders) ? template.placeholders : [];

        if (placeholders.length === 0) {
            variablesEmpty.style.display = 'block';
            renderPreview();
            return;
        }

        variablesEmpty.style.display = 'none';

        placeholders.forEach((index) => {
            const mapping = template?.variable_mappings?.[index] || template?.variable_mappings?.[String(index)] || {};
            const sourceType = String(mapping.source_type || 'text');
            let sourceLabel = 'Free text';
            if (sourceType === 'contact_field') {
                sourceLabel = `Field Contact: ${state.contactFieldOptions[mapping.contact_field] || mapping.contact_field || 'name'}`;
            } else if (sourceType === 'sender_field') {
                sourceLabel = `Field User Pengirim: ${state.senderFieldOptions[mapping.sender_field] || mapping.sender_field || 'name'}`;
            }
            const fallbackLabel = String(mapping.fallback_value || '').trim();
            const row = document.createElement('label');
            row.className = 'form-label mb-0';
            row.innerHTML = `
                <div class="small text-muted mb-1">Variable {{${index}}}</div>
                <input type="text" class="form-control" name="variables[${index}]" data-var-index="${index}" value="${esc(defaultVariableValue(index, template))}">
                <div class="form-hint mt-1">${esc(sourceLabel)}${fallbackLabel ? ` | Fallback: ${esc(fallbackLabel)}` : ''}</div>
            `;
            const input = row.querySelector('input');
            input?.addEventListener('input', renderPreview);
            variablesWrap.appendChild(row);
        });

        renderPreview();
    };

    const refreshTemplateOptions = () => {
        const namespace = (instanceSelect.selectedOptions[0]?.dataset.namespace || '').trim();
        const currentValue = templateSelect.value;
        const applicable = state.templates.filter((template) => {
            if (!namespace || !template.namespace) return true;
            return String(template.namespace) === namespace;
        });

        templateSelect.innerHTML = '<option value="">Pilih template</option>';
        applicable.forEach((template) => {
            const option = document.createElement('option');
            option.value = String(template.id);
            option.textContent = `${template.name} (${template.language})`;
            templateSelect.appendChild(option);
        });

        if (applicable.some((template) => String(template.id) === currentValue)) {
            templateSelect.value = currentValue;
        }

        hintEl.textContent = namespace
            ? 'Template disaring mengikuti namespace/WABA instance terpilih.'
            : 'Instance belum punya namespace khusus, semua template approved ditampilkan.';

        renderVariableInputs(selectedTemplate());
    };

    modalEl.addEventListener('show.bs.modal', (event) => {
        const trigger = event.relatedTarget;
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
            country: trigger.dataset.contactCountry || '',
        };

        contactIdInput.value = state.contact.id;
        returnToInput.value = trigger.dataset.returnTo || config.defaults.returnTo || window.location.href;
        subtitleEl.textContent = `${state.contact.name || 'Contact'} - ${state.contact.phone || '-'}`;
        previewContactName.textContent = state.contact.name || 'Contact';
        previewContactPhone.textContent = state.contact.phone || '-';
        previewHeaderContact.textContent = state.contact.name || 'Contact';
        instanceSelect.selectedIndex = 0;
        refreshTemplateOptions();
    });

    instanceSelect.addEventListener('change', refreshTemplateOptions);
    templateSelect.addEventListener('change', () => renderVariableInputs(selectedTemplate()));
    form.addEventListener('reset', () => {
        variablesWrap.innerHTML = '';
        variablesEmpty.style.display = 'block';
    });
});
