/* global jQuery, wafoAdmin */
(function ($) {
	'use strict';

	var WAFO = {

		currentFormId: null,
		fieldsData: [],
		targetsData: [],
		currentPage: 1,
		perPage: 20,

		init: function () {
			this.bindEvents();
			this.detectPage();
		},

		detectPage: function () {
			var page = this.getCurrentPage();
			if (page === 'wafo-dashboard') {
				this.loadDashboard();
			} else if (page === 'wafo-forms') {
				this.loadFormsList();
			} else if (page === 'wafo-submissions') {
				this.loadSubmissionsList();
				this.loadFormFilter();
			}
		},

		getCurrentPage: function () {
			var match = window.location.search.match(/page=([\w-]+)/);
			return match ? match[1] : '';
		},

		bindEvents: function () {
			$(document).on('click', '.wafo-palette-item', this.onPaletteAdd.bind(this));
			$(document).on('click', '.wafo-field-edit', this.onFieldEdit.bind(this));
			$(document).on('click', '.wafo-field-delete', this.onFieldDelete.bind(this));
			$('#wafo-save-form').on('click', this.onSaveForm.bind(this));
			$('#wafo-delete-form').on('click', this.onDeleteForm.bind(this));
			$(document).on('click', '.wafo-copy-btn', this.onCopy.bind(this));
			$(document).on('click', '.wafo-new-form-btn, .wafo-back-to-list', this.onToggleView.bind(this));
			$(document).on('click', '.wafo-edit-form', this.onEditForm.bind(this));
			$(document).on('click', '.wafo-delete-form-btn', this.onDeleteFormFromList.bind(this));

			$('#wafo-add-target').on('click', this.onAddTarget.bind(this));
			$(document).on('click', '.wafo-remove-target', this.onRemoveTarget.bind(this));

			$('#wafo-filter-apply').on('click', this.onFilterSubmissions.bind(this));
			$('#wafo-filter-reset').on('click', this.onResetFilters.bind(this));
			$('#wafo-export-csv').on('click', this.onExportCSV.bind(this));
			$(document).on('click', '.wafo-view-submission', this.onViewSubmission.bind(this));
			$(document).on('click', '.wafo-modal-close, #wafo-close-modal', this.onCloseModal.bind(this));
			$(document).on('change', '.wafo-inline-status', this.onInlineStatusChange.bind(this));

			$('#wafo-edit-field-type').on('change', this.onFieldTypeChange.bind(this));
			$(document).on('click', '.wafo-save-field', this.onSaveField.bind(this));
			$(document).on('click', '.wafo-modal-close-btn', this.onCloseModal.bind(this));

			if ($('#wafo-builder-canvas').length) {
				$('#wafo-builder-canvas').sortable({
					handle: '.wafo-field-handle',
					placeholder: 'wafo-field-item wafo-field-placeholder',
					tolerance: 'pointer',
					update: this.onFieldReorder.bind(this)
				});
			}
		},

		api: function (endpoint, method, data) {
			return $.ajax({
				url: wafoAdmin.restUrl + endpoint,
				method: method || 'GET',
				beforeSend: function (xhr) {
					xhr.setRequestHeader('X-WP-Nonce', wafoAdmin.restNonce);
				},
				data: (method === 'GET' || method === 'DELETE') ? data : JSON.stringify(data),
				contentType: (method !== 'GET' && method !== 'DELETE') ? 'application/json' : undefined
			});
		},

		/* ======================== Dashboard ======================== */
		loadDashboard: function () {
			var self = this;
			self.api('forms').done(function (forms) {
				$('#wafo-total-forms').text(forms.length);
			});
			self.api('submissions?per_page=1').done(function (data, s, xhr) {
				$('#wafo-total-submissions').text(xhr.getResponseHeader('X-WP-Total') || 0);
			});
			self.api('submissions?per_page=1&status=baru').done(function (data, s, xhr) {
				$('#wafo-total-baru').text(xhr.getResponseHeader('X-WP-Total') || 0);
			});
			self.api('submissions?per_page=1&wa_send_status=sent').done(function (data, s, xhr) {
				$('#wafo-total-sent').text(xhr.getResponseHeader('X-WP-Total') || 0);
			});

			self.api('submissions?per_page=10&orderby=created_at&order=desc').done(function (data) {
				var $tbody = $('#wafo-recent-submissions');
				$tbody.empty();
				if (!data || data.length === 0) {
					$tbody.html('<tr><td colspan="5" style="text-align:center;">Belum ada submission.</td></tr>');
					return;
				}
				$.each(data, function (i, sub) {
					$tbody.append(self.renderSubmissionRow(sub));
				});
			});
		},

		/* ======================== Forms List ======================== */
		loadFormsList: function () {
			var self = this;
			self.api('forms').done(function (forms) {
				var $tbody = $('#wafo-forms-list-body');
				$tbody.empty();
				if (!forms || forms.length === 0) {
					$tbody.html('<tr><td colspan="6" style="text-align:center;">Belum ada form. Klik "Form Baru" untuk membuat.</td></tr>');
					return;
				}
				$.each(forms, function (i, form) {
					var statusClass = 'wafo-status-' + form.status;
					var statusLabel = form.status === 'active' ? 'Aktif' : 'Nonaktif';
					var shortcode = '[wafo_form id="' + form.id + '"]';
					var row = '<tr data-id="' + form.id + '">' +
						'<td><strong>' + form.id + '</strong></td>' +
						'<td><strong>' + self.escHtml(form.name) + '</strong></td>' +
						'<td><span class="wafo-status-badge ' + statusClass + '">' + statusLabel + '</span></td>' +
						'<td class="shortcode-cell"><code>' + shortcode + '</code></td>' +
						'<td>-</td>' +
						'<td class="wafo-action-btns">' +
						'<button class="button button-small wafo-edit-form" data-id="' + form.id + '" title="Edit"><span class="dashicons dashicons-edit"></span></button>' +
						'<button class="button button-small wafo-delete-form-btn" data-id="' + form.id + '" data-name="' + self.escHtml(form.name) + '" title="Hapus"><span class="dashicons dashicons-trash"></span></button>' +
						'<button class="button button-small wafo-copy-shortcode" data-shortcode="' + shortcode + '" title="Copy Shortcode"><span class="dashicons dashicons-admin-page"></span></button>' +
						'</td>' +
						'</tr>';
					$tbody.append(row);
				});

				self.api('submissions?per_page=1').done(function (data, s, xhr) {
					var total = parseInt(xhr.getResponseHeader('X-WP-Total') || 0);
					$tbody.find('tr').each(function () {
						$(this).children('td').eq(4).text(total);
					});
				});
			});
		},

		onToggleView: function (e) {
			e.preventDefault();
			var isBuilderVisible = $('#wafo-form-builder-view').is(':visible');
			if (isBuilderVisible) {
				$('#wafo-form-builder-view').hide();
				$('#wafo-forms-list-view').show();
				this.currentFormId = null;
				this.fieldsData = [];
				this.targetsData = [];
				this.resetBuilder();
			} else {
				$('#wafo-forms-list-view').hide();
				$('#wafo-form-builder-view').show();
				this.resetBuilder();
			}
		},

		onEditForm: function (e) {
			e.preventDefault();
			var id = $(e.currentTarget).data('id');
			var self = this;
			self.api('forms/' + id).done(function (form) {
				$('#wafo-forms-list-view').hide();
				$('#wafo-form-builder-view').show();
				self.currentFormId = form.id;
				$('#wafo-form-name').val(form.name);
				$('#wafo-form-status').val(form.status);
				$('#wafo-form-id-display').val(form.id);
				$('#wafo-shortcode-display').val('[wafo_form id="' + form.id + '"]');
				$('#wafo-wa-template').val(form.wa_message_template || '');
				$('#wafo-delete-form').show();

				self.fieldsData = [];
				if (form.fields && form.fields.length) {
					$.each(form.fields, function (i, f) {
						self.fieldsData.push({
							label: f.label,
							field_type: f.field_type,
							is_required: parseInt(f.is_required) || 0,
							options: f.options
						});
					});
				}
				self.renderCanvas();

				self.targetsData = [];
				if (form.wa_targets && form.wa_targets.length) {
					$.each(form.wa_targets, function (i, t) {
						self.targetsData.push({ phone_number: t.phone_number, label: t.label || '' });
					});
				}
				self.renderTargets();
			});
		},

		onDeleteFormFromList: function (e) {
			e.preventDefault();
			var id = $(e.currentTarget).data('id');
			var name = $(e.currentTarget).data('name');
			if (!confirm('Yakin ingin menghapus form "' + name + '"?')) return;
			this.api('forms/' + id, 'DELETE').done(function () {
				$('tr[data-id="' + id + '"]').fadeOut(300, function () { $(this).remove(); });
			});
		},

		/* ======================== Form Builder ======================== */
		resetBuilder: function () {
			$('#wafo-form-name').val('');
			$('#wafo-form-status').val('active');
			$('#wafo-form-id-display').val('');
			$('#wafo-shortcode-display').val('');
			$('#wafo-wa-template').val('');
			$('#wafo-delete-form').hide();
			$('#wafo-targets-list').empty();
			$('#wafo-save-status').text('').removeClass('success error');
			this.fieldsData = [];
			this.targetsData = [];
			this.currentFormId = null;
			this.renderCanvas();
		},

		onPaletteAdd: function (e) {
			e.preventDefault();
			var type = $(e.currentTarget).data('type');
			var defaultLabels = {
				'text': 'Nama', 'phone': 'No. WhatsApp', 'email': 'Email',
				'textarea': 'Pesan', 'select': 'Pilihan', 'radio': 'Pilihan Ganda'
			};
			var field = {
				label: defaultLabels[type] || 'Field Baru',
				field_type: type,
				is_required: 0,
				options: null
			};
			this.fieldsData.push(field);
			this.renderCanvas();

			if (type === 'select' || type === 'radio') {
				var idx = this.fieldsData.length - 1;
				this.openFieldEditModal(idx);
			}
		},

		onFieldEdit: function (e) {
			e.preventDefault();
			var idx = $(e.currentTarget).closest('.wafo-field-item').data('index');
			this.openFieldEditModal(idx);
		},

		openFieldEditModal: function (idx) {
			var field = this.fieldsData[idx];
			if (!field) return;
			$('#wafo-edit-field-index').val(idx);
			$('#wafo-edit-field-label').val(field.label);
			$('#wafo-edit-field-type').val(field.field_type);
			$('#wafo-edit-field-required').prop('checked', field.is_required === 1);

			var hasOptions = (field.field_type === 'select' || field.field_type === 'radio');
			$('#wafo-field-options-wrap').toggle(hasOptions);
			if (hasOptions) {
				var opts = '';
				if (field.options) {
					var arr = typeof field.options === 'string' ? JSON.parse(field.options) : field.options;
					opts = arr.join('\n');
				}
				$('#wafo-edit-field-options').val(opts);
			}
			$('#wafo-wa-template').trigger('change');
			$('#wafo-field-modal').show();
		},

		onFieldTypeChange: function () {
			var type = $('#wafo-edit-field-type').val();
			var hasOptions = (type === 'select' || type === 'radio');
			$('#wafo-field-options-wrap').toggle(hasOptions);
		},

		onSaveField: function () {
			var idx = parseInt($('#wafo-edit-field-index').val());
			var field = this.fieldsData[idx];
			if (!field) return;
			field.label = $('#wafo-edit-field-label').val();
			field.field_type = $('#wafo-edit-field-type').val();
			field.is_required = $('#wafo-edit-field-required').is(':checked') ? 1 : 0;

			if (field.field_type === 'select' || field.field_type === 'radio') {
				var raw = $('#wafo-edit-field-options').val();
				field.options = raw.split('\n').map(function (s) { return s.trim(); }).filter(function (s) { return s.length > 0; });
			} else {
				field.options = null;
			}
			this.renderCanvas();
			$('#wafo-field-modal').hide();
		},

		onFieldDelete: function (e) {
			e.preventDefault();
			var idx = $(e.currentTarget).closest('.wafo-field-item').data('index');
			this.fieldsData.splice(idx, 1);
			this.renderCanvas();
		},

		onFieldReorder: function () {
			var self = this;
			var reordered = [];
			$('#wafo-builder-canvas .wafo-field-item').each(function () {
				var idx = $(this).data('index');
				reordered.push(self.fieldsData[idx]);
			});
			self.fieldsData = reordered;
			self.renderCanvas();
		},

		renderCanvas: function () {
			var self = this;
			var $canvas = $('#wafo-builder-canvas');
			$canvas.find('.wafo-field-item').remove();

			if (self.fieldsData.length === 0) {
				$('#wafo-empty-canvas').show();
				return;
			}
			$('#wafo-empty-canvas').hide();

			var typeIcons = {
				'text': 'text', 'phone': 'phone', 'email': 'email-alt',
				'textarea': 'editor-paragraph', 'select': 'arrow-down-alt2', 'radio': 'radio-button-checked'
			};

			$.each(self.fieldsData, function (i, field) {
				var icon = typeIcons[field.field_type] || 'forms-alt';
				var reqTag = field.is_required ? '<span class="wafo-required-tag">*</span>' : '';
				var html = '<div class="wafo-field-item" data-index="' + i + '">' +
					'<span class="wafo-field-handle"><span class="dashicons dashicons-move"></span></span>' +
					'<div class="wafo-field-icon"><span class="dashicons dashicons-' + icon + '"></span></div>' +
					'<div class="wafo-field-info">' +
					'<div class="wafo-field-label">' + self.escHtml(field.label) + reqTag + '</div>' +
					'<div class="wafo-field-meta"><span class="wafo-field-type-badge">' + field.field_type + '</span>' +
					(field.is_required ? ' &middot; Wajib' : '') + '</div></div>' +
					'<div class="wafo-field-actions">' +
					'<button class="button wafo-field-edit" title="Edit"><span class="dashicons dashicons-edit"></span></button>' +
					'<button class="button wafo-field-delete" title="Hapus"><span class="dashicons dashicons-trash"></span></button>' +
					'</div></div>';
				$canvas.append(html);
			});
		},

		/* ======================== WA Targets ======================== */
		onAddTarget: function (e) {
			e.preventDefault();
			this.targetsData.push({ phone_number: '', label: '' });
			this.renderTargets();
		},

		onRemoveTarget: function (e) {
			e.preventDefault();
			var idx = $(e.currentTarget).data('index');
			this.targetsData.splice(idx, 1);
			this.renderTargets();
		},

		syncTargetsFromDOM: function () {
			var self = this;
			$('#wafo-targets-list .wafo-target-item').each(function (i) {
				var phone = $(this).find('.wafo-target-phone').val();
				var label = $(this).find('.wafo-target-label').val();
				if (self.targetsData[i]) {
					self.targetsData[i].phone_number = phone;
					self.targetsData[i].label = label;
				}
			});
		},

		renderTargets: function () {
			var self = this;
			self.syncTargetsFromDOM();
			var $list = $('#wafo-targets-list');
			$list.empty();
			$.each(self.targetsData, function (i, t) {
				var html = '<div class="wafo-target-item">' +
					'<input type="text" class="wafo-target-phone" value="' + self.escHtml(t.phone_number) + '" placeholder="08xxxxxxxxxx">' +
					'<input type="text" class="wafo-target-label" value="' + self.escHtml(t.label) + '" placeholder="Label (opsional)">' +
					'<button type="button" class="button wafo-remove-target" data-index="' + i + '"><span class="dashicons dashicons-trash"></span></button>' +
					'</div>';
				$list.append(html);
			});
		},

		/* ======================== Save / Delete Form ======================== */
		onSaveForm: function (e) {
			e.preventDefault();
			var self = this;
			self.syncTargetsFromDOM();

			var name = $('#wafo-form-name').val().trim();
			if (!name) {
				$('#wafo-save-status').text('Nama form harus diisi.').removeClass('success').addClass('error');
				$('#wafo-form-name').focus();
				return;
			}

			var payload = {
				name: name,
				status: $('#wafo-form-status').val(),
				wa_message_template: $('#wafo-wa-template').val(),
				fields: $.map(self.fieldsData, function (f) {
					return { label: f.label, field_type: f.field_type, is_required: f.is_required, options: f.options };
				}),
				wa_targets: $.map(self.targetsData, function (t) {
					return { phone_number: t.phone_number, label: t.label };
				})
			};

			$('.wafo-save-spinner').addClass('is-active');
			$('#wafo-save-status').text('');

			var method = self.currentFormId ? 'POST' : 'POST';
			var endpoint = self.currentFormId ? 'forms/' + self.currentFormId : 'forms';

			self.api(endpoint, method, payload).done(function (data) {
				$('.wafo-save-spinner').removeClass('is-active');
				self.currentFormId = data.id;
				$('#wafo-form-id-display').val(data.id);
				$('#wafo-shortcode-display').val('[wafo_form id="' + data.id + '"]');
				$('#wafo-delete-form').show();
				$('#wafo-save-status').text('Form berhasil disimpan!').removeClass('error').addClass('success');
				setTimeout(function () { $('#wafo-save-status').text(''); }, 3000);
			}).fail(function () {
				$('.wafo-save-spinner').removeClass('is-active');
				$('#wafo-save-status').text('Gagal menyimpan form.').removeClass('success').addClass('error');
			});
		},

		onDeleteForm: function (e) {
			e.preventDefault();
			var self = this;
			if (!self.currentFormId) return;
			if (!confirm('Yakin ingin menghapus form ini?')) return;

			self.api('forms/' + self.currentFormId, 'DELETE').done(function () {
				$('#wafo-form-builder-view').hide();
				$('#wafo-forms-list-view').show();
				self.resetBuilder();
				self.loadFormsList();
			});
		},

		onCopy: function (e) {
			e.preventDefault();
			var target = $(e.currentTarget).data('copy-target');
			var $input = $(target);
			$input.select();
			document.execCommand('copy');
			$(e.currentTarget).find('.dashicons').css('color', '#25D366');
			var $this = $(e.currentTarget);
			setTimeout(function () { $this.find('.dashicons').css('color', ''); }, 1500);
		},

		/* ======================== Submissions ======================== */
		loadFormFilter: function () {
			var self = this;
			self.api('forms').done(function (forms) {
				var $sel = $('#wafo-filter-form');
				$sel.find('option:gt(0)').remove();
				$.each(forms, function (i, f) {
					$sel.append('<option value="' + f.id + '">' + self.escHtml(f.name) + '</option>');
				});
			});
		},

		loadSubmissionsList: function (page) {
			var self = this;
			self.currentPage = page || 1;

			var params = 'per_page=' + self.perPage + '&page=' + self.currentPage + '&orderby=created_at&order=desc';
			var formId = $('#wafo-filter-form').val();
			var status = $('#wafo-filter-status').val();
			var dateFrom = $('#wafo-filter-date-from').val();
			var dateTo = $('#wafo-filter-date-to').val();

			if (formId) params += '&form_id=' + formId;
			if (status) params += '&status=' + status;
			if (dateFrom) params += '&date_from=' + dateFrom;
			if (dateTo) params += '&date_to=' + dateTo;

			self.api('submissions?' + params).done(function (data, s, xhr) {
				var total = parseInt(xhr.getResponseHeader('X-WP-Total') || 0);
				var totalPages = parseInt(xhr.getResponseHeader('X-WP-TotalPages') || 1);
				var $tbody = $('#wafo-submissions-body');
				$tbody.empty();

				if (!data || data.length === 0) {
					$tbody.html('<tr><td colspan="7" style="text-align:center;">Tidak ada data submission.</td></tr>');
					self.renderPagination(total, totalPages);
					return;
				}

				$.each(data, function (i, sub) {
					$tbody.append(self.renderSubmissionRow(sub));
				});

				self.renderPagination(total, totalPages);
			});
		},

		renderSubmissionRow: function (sub) {
			var statusClass = 'wafo-status-badge wafo-status-' + sub.status;
			var waClass = 'wafo-status-badge wafo-status-' + sub.wa_send_status;
			var date = sub.created_at ? new Date(sub.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '-';

			return '<tr data-id="' + sub.id + '">' +
				'<td><strong>#' + sub.id + '</strong></td>' +
				'<td>' + (sub.form_id || '-') + '</td>' +
				'<td>' +
				'<select class="wafo-inline-status" data-id="' + sub.id + '" data-current="' + sub.status + '">' +
				'<option value="baru"' + (sub.status === 'baru' ? ' selected' : '') + '>Baru</option>' +
				'<option value="dihubungi"' + (sub.status === 'dihubungi' ? ' selected' : '') + '>Dihubungi</option>' +
				'<option value="selesai"' + (sub.status === 'selesai' ? ' selected' : '') + '>Selesai</option>' +
				'</select></td>' +
				'<td><span class="' + waClass + '">' + sub.wa_send_status + '</span></td>' +
				'<td>' + date + '</td>' +
				'<td class="wafo-action-btns">' +
				'<button class="button button-small wafo-view-submission" data-id="' + sub.id + '" title="Detail"><span class="dashicons dashicons-visibility"></span></button>' +
				'<button class="button button-small wafo-delete-submission" data-id="' + sub.id + '" title="Hapus"><span class="dashicons dashicons-trash"></span></button>' +
				'</td></tr>';
		},

		onFilterSubmissions: function (e) {
			e.preventDefault();
			this.loadSubmissionsList(1);
		},

		onResetFilters: function (e) {
			e.preventDefault();
			$('#wafo-filter-form').val('');
			$('#wafo-filter-status').val('');
			$('#wafo-filter-date-from').val('');
			$('#wafo-filter-date-to').val('');
			this.loadSubmissionsList(1);
		},

		onInlineStatusChange: function (e) {
			var self = this;
			var $select = $(e.currentTarget);
			var id = $select.data('id');
			var status = $select.val();
			self.api('submissions/' + id + '/status', 'PATCH', { status: status }).done(function () {
				$select.css('background', '#d1fae5');
				setTimeout(function () { $select.css('background', ''); }, 1500);
			});
		},

		onViewSubmission: function (e) {
			e.preventDefault();
			var id = $(e.currentTarget).data('id');
			var self = this;

			self.api('submissions/' + id).done(function (data) {
				var $detail = $('#wafo-submission-detail');
				var date = data.created_at ? new Date(data.created_at).toLocaleString('id-ID') : '-';

				var html = '<div class="wafo-detail-grid">' +
					'<div class="wafo-detail-field"><strong>ID:</strong> <span>#' + data.id + '</span></div>' +
					'<div class="wafo-detail-field"><strong>Form ID:</strong> <span>' + data.form_id + '</span></div>' +
					'<div class="wafo-detail-field"><strong>Status:</strong> <span class="wafo-status-badge wafo-status-' + data.status + '">' + data.status + '</span></div>' +
					'<div class="wafo-detail-field"><strong>WA Status:</strong> <span class="wafo-status-badge wafo-status-' + data.wa_send_status + '">' + data.wa_send_status + '</span></div>' +
					'<div class="wafo-detail-field"><strong>Tanggal:</strong> <span>' + date + '</span></div>' +
					'<div class="wafo-detail-field"><strong>IP:</strong> <span>' + (data.ip_address || '-') + '</span></div>' +
					'</div>';

				if (data.values && data.values.length > 0) {
					html += '<h3 style="margin-top:20px;font-size:14px;font-weight:600;">Data Submission:</h3>';
					html += '<table class="wafo-table wafo-table-compact">';
					$.each(data.values, function (i, v) {
						html += '<tr><td style="width:150px;"><strong>Field #' + v.field_id + '</strong></td><td>' + self.escHtml(v.value || '-') + '</td></tr>';
					});
					html += '</table>';
				}

				if (data.logs && data.logs.length > 0) {
					html += '<h3 style="margin-top:20px;font-size:14px;font-weight:600;">Riwayat:</h3>';
					html += '<ul class="wafo-log-list">';
					$.each(data.logs, function (i, log) {
						var logDate = log.created_at ? new Date(log.created_at).toLocaleString('id-ID') : '';
						html += '<li><span class="wafo-log-action">' + log.action + '</span>';
						if (log.old_value) html += ' (' + log.old_value + ' → ' + log.new_value + ')';
						html += ' <span class="wafo-log-date">' + logDate + '</span></li>';
					});
					html += '</ul>';
				}

				$detail.html(html);
				$('#wafo-submission-modal').show();
			});
		},

		onExportCSV: function () {
			var self = this;
			var params = '?format=csv';
			var status = $('#wafo-filter-status').val();
			var formId = $('#wafo-filter-form').val();
			var dateFrom = $('#wafo-filter-date-from').val();
			var dateTo = $('#wafo-filter-date-to').val();
			if (status) params += '&status=' + status;
			if (formId) params += '&form_id=' + formId;
			if (dateFrom) params += '&date_from=' + dateFrom;
			if (dateTo) params += '&date_to=' + dateTo;

			self.api('submissions/export' + params).done(function (data) {
				if (data.file_url) {
					window.open(data.file_url, '_blank');
				} else {
					alert('Tidak ada data untuk di-export.');
				}
			});
		},

		renderPagination: function (total, totalPages) {
			var self = this;
			$('#wafo-pagination-info').text('Menampilkan ' + total + ' data');
			var $pag = $('#wafo-pagination');
			$pag.empty();
			if (totalPages <= 1) return;

			var html = '<span class="displaying-num">' + total + ' items</span>';
			html += '<span class="pagination-links">';
			if (self.currentPage > 1) {
				html += '<a class="prev-page button" data-page="' + (self.currentPage - 1) + '">&lsaquo;</a>';
			} else {
				html += '<span class="tablenav-pages-navspan button disabled">&lsaquo;</span>';
			}
			for (var i = 1; i <= totalPages && i <= 10; i++) {
				if (i === self.currentPage) {
					html += '<span class="tablenav-paging-text"><strong>' + i + '</strong></span>';
				} else {
					html += '<a class="page-numbers button" data-page="' + i + '">' + i + '</a>';
				}
			}
			if (self.currentPage < totalPages) {
				html += '<a class="next-page button" data-page="' + (self.currentPage + 1) + '">&rsaquo;</a>';
			} else {
				html += '<span class="tablenav-pages-navspan button disabled">&rsaquo;</span>';
			}
			html += '</span>';
			$pag.html(html);

			$(document).off('click', '.page-numbers, .prev-page, .next-page').on('click', '.page-numbers, .prev-page, .next-page', function (e) {
				e.preventDefault();
				var page = $(this).data('page');
				if (page) self.loadSubmissionsList(page);
			});
		},

		/* ======================== Modal ======================== */
		onCloseModal: function (e) {
			e.preventDefault();
			$('.wafo-modal').hide();
		},

		/* ======================== Utility ======================== */
		escHtml: function (str) {
			if (!str) return '';
			var div = document.createElement('div');
			div.appendChild(document.createTextNode(str));
			return div.innerHTML;
		}
	};

	$(document).ready(function () {
		WAFO.init();
	});

})(jQuery);
