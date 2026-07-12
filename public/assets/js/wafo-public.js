/* global jQuery, wafoPublic */
(function ($) {
	'use strict';

	var WAFOForm = {

		init: function () {
			$(document).on('submit', '.wafo-form', this.handleSubmit.bind(this));
			$(document).on('blur', '.wafo-form input, .wafo-form textarea, .wafo-form select', this.onFieldBlur.bind(this));
			$(document).on('change', '.wafo-form input, .wafo-form textarea, .wafo-form select', this.onFieldChange.bind(this));
		},

		handleSubmit: function (e) {
			e.preventDefault();
			var $form = $(e.currentTarget);
			var formId = $form.data('form-id');
			var $btn = $form.find('.wafo-submit-btn');
			var $message = $form.find('.wafo-message');

			$message.hide().removeClass('wafo-success wafo-error');

			// Honeypot check.
			if ($form.find('[name="wafo_honeypot"]').val()) {
				return false;
			}

			// Client-side validation.
			if (!this.validateForm($form)) {
				return false;
			}

			// Collect field values.
			var data = {};
			$form.find('.wafo-field').each(function () {
				var $input = $(this).find('input[type="text"], input[type="tel"], input[type="email"], textarea, select').first();
				if ($input.length) {
					var name = $input.attr('name');
					if (name && name.indexOf('wafo_') !== 0) {
						data[name] = $input.val();
					}
				}
				$(this).find('input[type="radio"]:checked, input[type="checkbox"]:checked').each(function () {
					data[$(this).attr('name')] = $(this).val();
				});
			});

			data.wafo_token = $form.find('[name="wafo_token"]').val();
			data.wafo_honeypot = '';

			// Build URL.
			var url = wafoPublic.restUrl + 'submit/' + formId;
			console.log('[WAFO] Submit URL:', url);

			// Disable button and show loading state.
			$btn.prop('disabled', true);
			$form.find('.wafo-submit-spinner').css('display', 'inline-block');
			$form.find('.wafo-btn-text').text('Mengirim...');

			$.ajax({
				url: url,
				method: 'POST',
				data: JSON.stringify(data),
				contentType: 'application/json',
				success: function (response) {
					console.log('[WAFO] Response:', response);
					$form.find('.wafo-submit-spinner').hide();
					$form.find('.wafo-btn-text').text('Kirim via WhatsApp');
					$btn.prop('disabled', false);

					if (response.success) {
						$form[0].reset();

						if (response.wa_link) {
							// Show message then redirect.
							$message.addClass('wafo-success')
								.html('Mengarahkan ke WhatsApp...')
								.show();
							// Redirect immediately.
							window.location.href = response.wa_link;
						} else {
							$message.addClass('wafo-success')
								.html(response.message || 'Submission berhasil! Data Anda telah tercatat.')
								.show();
						}
					} else {
						$message.addClass('wafo-error')
							.html(response.message || 'Terjadi kesalahan. Silakan coba lagi.')
							.show();
					}
				},
				error: function (xhr) {
					console.error('[WAFO] Error:', xhr.status, xhr.responseJSON);
					$form.find('.wafo-submit-spinner').hide();
					$form.find('.wafo-btn-text').text('Kirim via WhatsApp');
					$btn.prop('disabled', false);

					var msg = 'Terjadi kesalahan. Silakan coba lagi.';
					if (xhr.responseJSON && xhr.responseJSON.message) {
						msg = xhr.responseJSON.message;
					}
					$message.addClass('wafo-error').html(msg).show();
				}
			});
		},

		validateForm: function ($form) {
			var valid = true;

			$form.find('.wafo-field').each(function () {
				var $field = $(this);
				var $input = $field.find('input[type="text"], input[type="tel"], input[type="email"], textarea, select').first();
				var $error = $field.find('.wafo-field-error');

				$field.removeClass('has-error');
				$input.removeClass('has-error');
				if ($error.length) $error.hide();

				if (!$input.length) return;

				var value = $input.val().trim();

				if ($input.prop('required') && !value) {
					valid = false;
					$field.addClass('has-error');
					$input.addClass('has-error');
					if (!$error.length) {
						$error = $('<div class="wafo-field-error">Field ini wajib diisi.</div>');
						$field.append($error);
					}
					$error.text('Field ini wajib diisi.').show();
					return;
				}

				if (value && $input.attr('type') === 'email') {
					var emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
					if (!emailRe.test(value)) {
						valid = false;
						$field.addClass('has-error');
						$input.addClass('has-error');
						if (!$error.length) {
							$error = $('<div class="wafo-field-error">Format email tidak valid.</div>');
							$field.append($error);
						}
						$error.text('Format email tidak valid.').show();
					}
				}

				if (value && $input.attr('type') === 'tel') {
					var phoneClean = value.replace(/[^0-9]/g, '');
					if (phoneClean.length < 10 || phoneClean.length > 15) {
						valid = false;
						$field.addClass('has-error');
						$input.addClass('has-error');
						if (!$error.length) {
							$error = $('<div class="wafo-field-error">Nomor WhatsApp tidak valid.</div>');
							$field.append($error);
						}
						$error.text('Nomor WhatsApp tidak valid.').show();
					}
				}
			});

			return valid;
		},

		onFieldBlur: function (e) {
			var $input = $(e.currentTarget);
			var $field = $input.closest('.wafo-field');
			var $error = $field.find('.wafo-field-error');
			var value = $input.val().trim();

			$field.removeClass('has-error');
			$input.removeClass('has-error');
			if ($error.length) $error.hide();

			if ($input.prop('required') && !value) {
				$field.addClass('has-error');
				$input.addClass('has-error');
				if (!$error.length) {
					$error = $('<div class="wafo-field-error">Field ini wajib diisi.</div>');
					$field.append($error);
				}
				$error.text('Field ini wajib diisi.').show();
				return;
			}

			if (value && $input.attr('type') === 'email') {
				var emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
				if (!emailRe.test(value)) {
					$field.addClass('has-error');
					$input.addClass('has-error');
					if (!$error.length) {
						$error = $('<div class="wafo-field-error">Format email tidak valid.</div>');
						$field.append($error);
					}
					$error.text('Format email tidak valid.').show();
				}
			}

			if (value && $input.attr('type') === 'tel') {
				var phoneClean = value.replace(/[^0-9]/g, '');
				if (phoneClean.length < 10 || phoneClean.length > 15) {
					$field.addClass('has-error');
					$input.addClass('has-error');
					if (!$error.length) {
						$error = $('<div class="wafo-field-error">Nomor WhatsApp tidak valid.</div>');
						$field.append($error);
					}
					$error.text('Nomor WhatsApp tidak valid.').show();
				}
			}
		},

		onFieldChange: function (e) {
			var $input = $(e.currentTarget);
			var $field = $input.closest('.wafo-field');
			$field.removeClass('has-error');
			$field.find('.wafo-field-error').hide();
			$input.removeClass('has-error');
		}
	};

	$(document).ready(function () {
		WAFOForm.init();
	});

})(jQuery);
