<div class="wrap wafo-admin">
	<h1>
		<span class="dashicons dashicons-list-view wafo-dashicon"></span>
		<?php echo esc_html( get_admin_page_title() ); ?>
	</h1>

	<div class="wafo-submissions-toolbar">
		<div class="wafo-filters">
			<select id="wafo-filter-form" class="wafo-filter-select">
				<option value=""><?php esc_html_e( 'Semua Form', 'wa-form-optin' ); ?></option>
			</select>
			<select id="wafo-filter-status" class="wafo-filter-select">
				<option value=""><?php esc_html_e( 'Semua Status', 'wa-form-optin' ); ?></option>
				<option value="baru"><?php esc_html_e( 'Baru', 'wa-form-optin' ); ?></option>
				<option value="dihubungi"><?php esc_html_e( 'Dihubungi', 'wa-form-optin' ); ?></option>
				<option value="selesai"><?php esc_html_e( 'Selesai', 'wa-form-optin' ); ?></option>
			</select>
			<input type="date" id="wafo-filter-date-from" class="wafo-date-input" placeholder="<?php esc_attr_e( 'Dari Tanggal', 'wa-form-optin' ); ?>">
			<input type="date" id="wafo-filter-date-to" class="wafo-date-input" placeholder="<?php esc_attr_e( 'Sampai Tanggal', 'wa-form-optin' ); ?>">
			<button type="button" class="button button-primary wafo-button wafo-filter-btn" id="wafo-filter-apply">
				<span class="dashicons dashicons-filter"></span>
				<?php esc_html_e( 'Filter', 'wa-form-optin' ); ?>
			</button>
			<button type="button" class="button wafo-reset-btn" id="wafo-filter-reset">
				<span class="dashicons dashicons-undo"></span>
				<?php esc_html_e( 'Reset', 'wa-form-optin' ); ?>
			</button>
		</div>
		<div class="wafo-actions">
			<button type="button" class="button wafo-export-btn" id="wafo-export-csv">
				<span class="dashicons dashicons-download"></span>
				<?php esc_html_e( 'Export CSV', 'wa-form-optin' ); ?>
			</button>
		</div>
	</div>

	<table class="wp-list-table widefat fixed striped wafo-submissions-table">
		<thead>
			<tr>
				<th style="width:50px;"><?php esc_html_e( 'ID', 'wa-form-optin' ); ?></th>
				<th><?php esc_html_e( 'Form', 'wa-form-optin' ); ?></th>
				<th><?php esc_html_e( 'Data', 'wa-form-optin' ); ?></th>
				<th style="width:100px;"><?php esc_html_e( 'Status', 'wa-form-optin' ); ?></th>
				<th style="width:100px;"><?php esc_html_e( 'WA', 'wa-form-optin' ); ?></th>
				<th style="width:140px;"><?php esc_html_e( 'Tanggal', 'wa-form-optin' ); ?></th>
				<th style="width:100px;"><?php esc_html_e( 'Aksi', 'wa-form-optin' ); ?></th>
			</tr>
		</thead>
		<tbody id="wafo-submissions-body">
			<tr>
				<td colspan="7" class="wafo-loading"><?php esc_html_e( 'Memuat data...', 'wa-form-optin' ); ?></td>
			</tr>
		</tbody>
	</table>

	<div class="wafo-pagination-wrap">
		<div class="wafo-pagination-info" id="wafo-pagination-info"></div>
		<div class="tablenav-pages" id="wafo-pagination"></div>
	</div>
</div>

<div id="wafo-submission-modal" class="wafo-modal" style="display:none;">
	<div class="wafo-modal-content wafo-modal-large">
		<div class="wafo-modal-header">
			<h2><?php esc_html_e( 'Detail Submission', 'wa-form-optin' ); ?></h2>
			<span class="wafo-modal-close">&times;</span>
		</div>
		<div class="wafo-modal-body" id="wafo-submission-detail">
		</div>
		<div class="wafo-modal-footer">
			<button type="button" class="button button-primary wafo-button" id="wafo-close-modal"><?php esc_html_e( 'Tutup', 'wa-form-optin' ); ?></button>
		</div>
	</div>
</div>
