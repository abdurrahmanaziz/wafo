<div class="wrap wafo-admin">
	<h1>
		<span class="dashicons dashicons-feedback wafo-dashicon"></span>
		<?php echo esc_html( get_admin_page_title() ); ?>
	</h1>

	<div id="wafo-forms-list-view">
		<div class="wafo-tablenav top">
			<div class="alignleft actions">
				<button type="button" class="button button-primary wafo-button wafo-new-form-btn">
					<span class="dashicons dashicons-plus-alt2"></span>
					<?php esc_html_e( 'Form Baru', 'wa-form-optin' ); ?>
				</button>
			</div>
		</div>

		<table class="wp-list-table widefat fixed striped wafo-forms-table">
			<thead>
				<tr>
					<th style="width:60px;"><?php esc_html_e( 'ID', 'wa-form-optin' ); ?></th>
					<th><?php esc_html_e( 'Nama Form', 'wa-form-optin' ); ?></th>
					<th style="width:100px;"><?php esc_html_e( 'Status', 'wa-form-optin' ); ?></th>
					<th style="width:120px;"><?php esc_html_e( 'Shortcode', 'wa-form-optin' ); ?></th>
					<th style="width:100px;"><?php esc_html_e( 'Submissions', 'wa-form-optin' ); ?></th>
					<th style="width:140px;"><?php esc_html_e( 'Aksi', 'wa-form-optin' ); ?></th>
				</tr>
			</thead>
			<tbody id="wafo-forms-list-body">
				<tr><td colspan="6" class="wafo-loading"><?php esc_html_e( 'Memuat data...', 'wa-form-optin' ); ?></td></tr>
			</tbody>
		</table>
	</div>

	<div id="wafo-form-builder-view" style="display:none;">
		<div class="wafo-tablenav top">
			<div class="alignleft actions">
				<button type="button" class="button wafo-back-to-list">
					<span class="dashicons dashicons-arrow-left-alt2"></span>
					<?php esc_html_e( 'Kembali ke Daftar', 'wa-form-optin' ); ?>
				</button>
			</div>
		</div>

		<div class="wafo-builder-wrap">
			<div class="wafo-builder-sidebar">
				<h3 class="wafo-sidebar-title"><?php esc_html_e( 'Field Types', 'wa-form-optin' ); ?></h3>
				<div class="wafo-field-palette">
					<button class="wafo-palette-item" data-type="text" data-icon="text">
						<span class="dashicons dashicons-text"></span>
						<?php esc_html_e( 'Text', 'wa-form-optin' ); ?>
					</button>
					<button class="wafo-palette-item" data-type="phone" data-icon="phone">
						<span class="dashicons dashicons-phone"></span>
						<?php esc_html_e( 'No. WhatsApp', 'wa-form-optin' ); ?>
					</button>
					<button class="wafo-palette-item" data-type="email" data-icon="email">
						<span class="dashicons dashicons-email-alt"></span>
						<?php esc_html_e( 'Email', 'wa-form-optin' ); ?>
					</button>
					<button class="wafo-palette-item" data-type="textarea" data-icon="textarea">
						<span class="dashicons dashicons-editor-paragraph"></span>
						<?php esc_html_e( 'Textarea', 'wa-form-optin' ); ?>
					</button>
					<button class="wafo-palette-item" data-type="select" data-icon="select">
						<span class="dashicons dashicons-arrow-down-alt2"></span>
						<?php esc_html_e( 'Dropdown', 'wa-form-optin' ); ?>
					</button>
					<button class="wafo-palette-item" data-type="radio" data-icon="radio">
						<span class="dashicons dashicons-radio-button-checked"></span>
						<?php esc_html_e( 'Radio Button', 'wa-form-optin' ); ?>
					</button>
				</div>

				<div class="wafo-sidebar-section">
					<h4><?php esc_html_e( 'Pengaturan Form', 'wa-form-optin' ); ?></h4>
					<label for="wafo-form-name"><?php esc_html_e( 'Nama Form', 'wa-form-optin' ); ?></label>
					<input type="text" id="wafo-form-name" class="widefat" placeholder="<?php esc_attr_e( 'Contoh: Form Kontak', 'wa-form-optin' ); ?>">

					<label for="wafo-form-status"><?php esc_html_e( 'Status', 'wa-form-optin' ); ?></label>
					<select id="wafo-form-status" class="widefat">
						<option value="active"><?php esc_html_e( 'Aktif', 'wa-form-optin' ); ?></option>
						<option value="inactive"><?php esc_html_e( 'Nonaktif', 'wa-form-optin' ); ?></option>
					</select>

					<label for="wafo-form-id-display"><?php esc_html_e( 'Form ID', 'wa-form-optin' ); ?></label>
					<div class="wafo-shortcode-copy">
						<input type="text" id="wafo-form-id-display" class="widefat" value="" readonly>
						<button type="button" class="button wafo-copy-btn" data-copy-target="#wafo-form-id-display" title="<?php esc_attr_e( 'Copy', 'wa-form-optin' ); ?>">
							<span class="dashicons dashicons-admin-page"></span>
						</button>
					</div>
					<label><?php esc_html_e( 'Shortcode', 'wa-form-optin' ); ?></label>
					<div class="wafo-shortcode-copy">
						<input type="text" id="wafo-shortcode-display" class="widefat" value="" readonly>
						<button type="button" class="button wafo-copy-btn" data-copy-target="#wafo-shortcode-display" title="<?php esc_attr_e( 'Copy', 'wa-form-optin' ); ?>">
							<span class="dashicons dashicons-admin-page"></span>
						</button>
					</div>
				</div>
			</div>

			<div class="wafo-builder-main">
				<div class="wafo-builder-canvas-wrap">
					<h3 class="wafo-section-title"><?php esc_html_e( 'Form Fields', 'wa-form-optin' ); ?></h3>
					<div class="wafo-builder-canvas" id="wafo-builder-canvas">
						<div class="wafo-empty-canvas" id="wafo-empty-canvas">
							<span class="dashicons dashicons-move"></span>
							<p><?php esc_html_e( 'Klik field type di sebelah kiri untuk menambahkan field', 'wa-form-optin' ); ?></p>
						</div>
					</div>
				</div>

				<div class="wafo-builder-section">
					<h3 class="wafo-section-title">
						<span class="dashicons dashicons-email-alt"></span>
						<?php esc_html_e( 'Template Pesan WhatsApp', 'wa-form-optin' ); ?>
					</h3>
					<p class="description"><?php esc_html_e( 'Gunakan {nama-field} sebagai placeholder. Contoh: {nama}, {email}, {pesan}', 'wa-form-optin' ); ?></p>
					<textarea id="wafo-wa-template" class="large-text code" rows="5" placeholder="Halo Admin, saya {nama}.&#10;Email: {email}&#10;Pesan: {pesan}"></textarea>
				</div>

				<div class="wafo-builder-section">
					<h3 class="wafo-section-title">
						<span class="dashicons dashicons-phone"></span>
						<?php esc_html_e( 'Nomor WhatsApp Tujuan', 'wa-form-optin' ); ?>
					</h3>
					<div id="wafo-targets-list"></div>
					<button type="button" class="button wafo-add-target-btn" id="wafo-add-target">
						<span class="dashicons dashicons-plus-alt2"></span>
						<?php esc_html_e( 'Tambah Nomor', 'wa-form-optin' ); ?>
					</button>
				</div>

				<div class="wafo-builder-footer">
					<button type="button" class="button button-primary wafo-button wafo-save-form" id="wafo-save-form">
						<span class="dashicons dashicons-saved"></span>
						<?php esc_html_e( 'Simpan Form', 'wa-form-optin' ); ?>
					</button>
					<button type="button" class="button wafo-delete-form" id="wafo-delete-form" style="display:none;">
						<span class="dashicons dashicons-trash"></span>
						<?php esc_html_e( 'Hapus Form', 'wa-form-optin' ); ?>
					</button>
					<span class="spinner wafo-save-spinner"></span>
					<span id="wafo-save-status" class="wafo-save-status"></span>
				</div>
			</div>
		</div>
	</div>
</div>

<div id="wafo-field-modal" class="wafo-modal" style="display:none;">
	<div class="wafo-modal-content wafo-modal-field">
		<div class="wafo-modal-header">
			<h2><?php esc_html_e( 'Edit Field', 'wa-form-optin' ); ?></h2>
			<span class="wafo-modal-close">&times;</span>
		</div>
		<div class="wafo-modal-body">
			<input type="hidden" id="wafo-edit-field-index" value="">
			<div class="wafo-field-row">
				<label for="wafo-edit-field-label"><?php esc_html_e( 'Label', 'wa-form-optin' ); ?></label>
				<input type="text" id="wafo-edit-field-label" class="widefat">
			</div>
			<div class="wafo-field-row">
				<label for="wafo-edit-field-type"><?php esc_html_e( 'Tipe Field', 'wa-form-optin' ); ?></label>
				<select id="wafo-edit-field-type" class="widefat">
					<option value="text"><?php esc_html_e( 'Text', 'wa-form-optin' ); ?></option>
					<option value="phone"><?php esc_html_e( 'No. WhatsApp', 'wa-form-optin' ); ?></option>
					<option value="email"><?php esc_html_e( 'Email', 'wa-form-optin' ); ?></option>
					<option value="textarea"><?php esc_html_e( 'Textarea', 'wa-form-optin' ); ?></option>
					<option value="select"><?php esc_html_e( 'Dropdown', 'wa-form-optin' ); ?></option>
					<option value="radio"><?php esc_html_e( 'Radio Button', 'wa-form-optin' ); ?></option>
				</select>
			</div>
			<div class="wafo-field-row">
				<label>
					<input type="checkbox" id="wafo-edit-field-required">
					<?php esc_html_e( 'Wajib diisi', 'wa-form-optin' ); ?>
				</label>
			</div>
			<div class="wafo-field-row wafo-field-options-row" id="wafo-field-options-wrap" style="display:none;">
				<label><?php esc_html_e( 'Opsi (satu per baris)', 'wa-form-optin' ); ?></label>
				<textarea id="wafo-edit-field-options" class="widefat" rows="4" placeholder="Opsi 1&#10;Opsi 2&#10;Opsi 3"></textarea>
			</div>
		</div>
		<div class="wafo-modal-footer">
			<button type="button" class="button button-primary wafo-button wafo-save-field"><?php esc_html_e( 'Simpan', 'wa-form-optin' ); ?></button>
			<button type="button" class="button wafo-modal-close-btn"><?php esc_html_e( 'Batal', 'wa-form-optin' ); ?></button>
		</div>
	</div>
</div>
