// Client-side validation and small UX helpers.
// This is a convenience layer only; the server re-validates everything.
(function () {
    'use strict';

    function labelFor(field) {
        var label = document.querySelector('label[for="' + field.id + '"]');
        return label ? label.textContent.trim() : 'This field';
    }

    function messageFor(field) {
        var v = field.validity;
        var label = labelFor(field);
        if (v.valueMissing) return label + ' is required.';
        if (v.typeMismatch && field.type === 'email') return 'Enter a valid email address.';
        if (v.tooShort) return label + ' must be at least ' + field.minLength + ' characters.';
        if (v.tooLong) return label + ' must be at most ' + field.maxLength + ' characters.';
        if (v.patternMismatch) return field.dataset.patternMessage || label + ' is invalid.';
        if (v.customError) return field.validationMessage;
        return '';
    }

    function showError(field) {
        var next = field.nextElementSibling;
        var box = next && next.classList.contains('field-error') ? next : null;
        var message = messageFor(field);

        if (!message) {
            if (box) box.remove();
            field.removeAttribute('aria-invalid');
            return true;
        }
        if (!box) {
            box = document.createElement('p');
            box.className = 'field-error';
            box.setAttribute('role', 'alert');
            field.insertAdjacentElement('afterend', box);
        }
        box.textContent = message;
        field.setAttribute('aria-invalid', 'true');
        return false;
    }

    function checkMatch(field) {
        var target = document.getElementById(field.dataset.match);
        field.setCustomValidity(target && field.value !== target.value ? 'Passwords do not match.' : '');
    }

    function fieldsOf(form) {
        return Array.prototype.filter.call(form.elements, function (el) {
            return el.willValidate && el.type !== 'submit' && el.type !== 'hidden';
        });
    }

    // The server trims input, so check the trimmed value. (Native minlength also
    // skips values the user hasn't edited, e.g. prefilled edit forms.)
    function enforceLength(field) {
        if (field.type === 'password') return;
        var value = field.value.trim();
        var min = field.minLength;
        if (field.required && value === '' && field.value !== '') {
            field.setCustomValidity(labelFor(field) + ' is required.');
        } else if (value !== '' && min > 0 && value.length < min) {
            field.setCustomValidity(labelFor(field) + ' must be at least ' + min + ' characters.');
        } else {
            field.setCustomValidity('');
        }
    }

    document.querySelectorAll('form.js-validate').forEach(function (form) {
        var fields = fieldsOf(form);

        fields.forEach(function (field) {
            var touched = false;
            field.addEventListener('blur', function () {
                touched = true;
                if (field.dataset.match !== undefined) checkMatch(field);
                else enforceLength(field);
                showError(field);
            });
            field.addEventListener('input', function () {
                if (field.dataset.match !== undefined) checkMatch(field);
                else enforceLength(field);
                if (touched) showError(field);
            });
        });

        form.addEventListener('submit', function (event) {
            var firstInvalid = null;
            fields.forEach(function (field) {
                if (field.dataset.match !== undefined) checkMatch(field);
                else enforceLength(field);
                if (!showError(field) && !firstInvalid) firstInvalid = field;
            });
            if (firstInvalid) {
                event.preventDefault();
                firstInvalid.focus();
                return;
            }
            var button = form.querySelector('[type="submit"]');
            if (button) button.disabled = true; // prevent double submits
        });
    });

    // Re-check password confirmation when the original password changes.
    document.querySelectorAll('[data-match]').forEach(function (confirm) {
        var original = document.getElementById(confirm.dataset.match);
        if (original) original.addEventListener('input', function () {
            if (confirm.value) { checkMatch(confirm); showError(confirm); }
        });
    });

    // Character counters for textareas.
    document.querySelectorAll('textarea[data-counter]').forEach(function (area) {
        var counter = document.createElement('p');
        counter.className = 'hint counter';
        area.insertAdjacentElement('afterend', counter);
        function update() {
            counter.textContent = area.value.length + ' / ' + area.maxLength;
        }
        area.addEventListener('input', update);
        update();
    });

    // Confirmation prompt for destructive forms.
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!window.confirm(form.dataset.confirm)) event.preventDefault();
        });
    });
})();
