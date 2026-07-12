<div class="wrap wafo-admin">
	<h1>
		<span class="dashicons dashicons-admin-settings wafo-dashicon"></span>
		<?php echo esc_html( get_admin_page_title() ); ?>
	</h1>

	<form id="wafo-settings-form" method="post" action="options.php">
		<?php settings_fields( 'wafo_settings_group' ); ?>

		<div class="wafo-settings-tabs">
			<div class="wafo-settings-section">
				<h2 class="wafo-section-header">
					<span class="dashicons dashicons-shield"></span>
					<?php esc_html_e( 'Pengaturan Keamanan', 'wa-form-optin' ); ?>
				</h2>
				<table class="form-table">
					<tr>
						<th scope="row"><label for="wafo_settings[rate_limit_max]"><?php esc_html_e( 'Batas Submission per IP', 'wa-form-optin' ); ?></label></th>
						<td>
							<input type="number" name="wafo_settings[rate_limit_max]" id="wafo_settings[rate_limit_max]" value="5" class="small-text" min="1" max="100">
							<p class="description"><?php esc_html_e( 'Jumlah maksimum submission per IP dalam periode waktu tertentu untuk mencegah spam.', 'wa-form-optin' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="wafo_settings[rate_limit_window]"><?php esc_html_e( 'Jendela Waktu Rate Limit (detik)', 'wa-form-optin' ); ?></label></th>
						<td>
							<input type="number" name="wafo_settings[rate_limit_window]" id="wafo_settings[rate_limit_window]" value="600" class="small-text" min="60" max="86400">
							<p class="description"><?php esc_html_e( 'Waktu dalam detik sebelum hitungan rate limit direset. Default: 600 detik (10 menit).', 'wa-form-optin' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label><?php esc_html_e( 'Fitur Keamanan', 'wa-form-optin' ); ?></label></th>
						<td>
							<fieldset>
								<legend class="screen-reader-text"><?php esc_html_e( 'Fitur Keamanan', 'wa-form-optin' ); ?></legend>
								<label>
									<input type="checkbox" checked disabled>
									<?php esc_html_e( 'Honeypot Protection (Always On)', 'wa-form-optin' ); ?>
								</label>
								<br>
								<label>
									<input type="checkbox" checked disabled>
									<?php esc_html_e( 'Hidden Token Validation (Always On)', 'wa-form-optin' ); ?>
								</label>
								<p class="description"><?php esc_html_e( 'Form dilindungi oleh honeypot field dan hidden token untuk mencegah submission otomatis dari bot.', 'wa-form-optin' ); ?></p>
							</fieldset>
						</td>
					</tr>
				</table>
			</div>

			<div class="wafo-settings-section">
				<h2 class="wafo-section-header">
					<span class="dashicons dashicons-email-alt"></span>
					<?php esc_html_e( 'Pengaturan Notifikasi Email', 'wa-form-optin' ); ?>
				</h2>
				<table class="form-table">
					<tr>
						<th scope="row"><label for="wafo_settings[email_notification]"><?php esc_html_e( 'Kirim Notifikasi Email', 'wa-form-optin' ); ?></label></th>
						<td>
							<label>
								<input type="checkbox" name="wafo_settings[email_notification]" id="wafo_settings[email_notification]" value="1">
								<?php esc_html_e( 'Kirim email notifikasi ke admin saat ada submission baru', 'wa-form-optin' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="wafo_settings[email_to]"><?php esc_html_e( 'Email Tujuan', 'wa-form-optin' ); ?></label></th>
						<td>
							<input type="email" name="wafo_settings[email_to]" id="wafo_settings[email_to]" class="regular-text" placeholder="admin@example.com">
							<p class="description"><?php esc_html_e( 'Email yang akan menerima notifikasi submission. Default: email admin WordPress.', 'wa-form-optin' ); ?></p>
						</td>
					</tr>
				</table>
			</div>

			<div class="wafo-settings-section">
				<h2 class="wafo-section-header">
					<span class="dashicons dashicons-info"></span>
					<?php esc_html_e( 'Info Plugin', 'wa-form-optin' ); ?>
				</h2>
				<table class="form-table">
					<tr>
						<th scope="row"><?php esc_html_e( 'Versi', 'wa-form-optin' ); ?></th>
						<td><?php echo esc_html( WAFO_VERSION ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Plugin Name', 'wa-form-optin' ); ?></th>
						<td>WA Form Optin (WAFO)</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Database Tables', 'wa-form-optin' ); ?></th>
						<td>
							<ul class="wafo-db-tables">
								<li><code>wp_wafo_forms</code></li>
								<li><code>wp_wafo_fields</code></li>
								<li><code>wp_wafo_wa_targets</code></li>
								<li><code>wp_wafo_submissions</code></li>
								<li><code>wp_wafo_submission_values</code></li>
								<li><code>wp_wafo_submission_logs</code></li>
							</ul>
						</td>
					</tr>
				</table>
			</div>
		</div>

		<?php submit_button( __( 'Simpan Pengaturan', 'wa-form-optin' ), 'primary wafo-button' ); ?>
	</form>
</div>
