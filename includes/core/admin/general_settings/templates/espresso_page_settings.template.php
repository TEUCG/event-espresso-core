<div class="padding">

	<h4 class="ee-admin-settings-hdr">
		<?php _e('Critical Pages & Shortcodes', 'event_espresso'); ?>
	</h4>
	<p class="ee-attention">
		<?php
		echo sprintf(
			__('The following shortcodes and page settings are required for Event Espresso to function properly. %sThese shortcodes should not be replaced with any other shortcodes. Please view %sthis page%s for a list of optional shortcodes you can use on other pages.', 'event_espresso'),
			'<br />',
			'<a href="admin.php?page=espresso_support&action=shortcodes">',
			'</a>'
		);
		?>
	</p>

	<table class="form-table">
		<tbody>

			<tr>
				<th>
					<label for="reg_page_id">
						<strong>
							<?php _e('Event Registration Page', 'event_espresso'); ?>
						</strong>
						<?php echo EE_Template::get_help_tab_link('registration_page_info'); ?>
						<br />
						<?php echo General_Settings_Admin_Page::edit_view_links( $reg_page_id );?>
					</label>
				</th>
				<td>
					<select name="reg_page_id" data-placeholder="Choose a page...">
						<option value="0">
							<?php _e('Main Page', 'event_espresso'); ?>
						</option>
						<?php General_Settings_Admin_Page::page_settings_dropdown( $reg_page_id ); ?>
					</select>
					<span>
						<?php echo General_Settings_Admin_Page::page_and_shortcode_status( $reg_page_obj, '[ESPRESSO_REGISTRATIONS]' ); ?>
					</span>
					<br />
					<p class="description">
						<?php
						echo sprintf(
							__("This page can be hidden from navigation if desired, but should always contain the %s shortcode.", 'event_espresso'),
							'<span class="highlight" style="padding:3px;margin:0;">[ESPRESSO_REGISTRATIONS]</span>'
						);
						?>
					</p>
					<br/><br/>
				</td>
			</tr>

			<tr>
				<th>
					<label for="txn_page_id">
						<strong>
							<?php _e('Transactions Page', 'event_espresso'); ?>
						</strong>
						<?php echo EE_Template::get_help_tab_link('notify_url_info'); ?>
						<br />
						<span class="lt-grey-text"><?php _e('Notify URL (processes payments)', 'event_espresso'); ?></span><br/>
						<?php echo General_Settings_Admin_Page::edit_view_links( $txn_page_id );?>
					</label>
				</th>
				<td>
					<select name="txn_page_id" data-placeholder="Choose a page...">
						<option value="0">
							<?php _e('Main Page', 'event_espresso'); ?>
						</option>
						<?php General_Settings_Admin_Page::page_settings_dropdown( $txn_page_id ); ?>
					</select>
					<span>
						<?php echo General_Settings_Admin_Page::page_and_shortcode_status( $txn_page_obj, '[ESPRESSO_TXN_PAGE]' ); ?>
					</span>
					<br />
					<p class="description">
						<?php
						echo sprintf(
							__( 'This page should be hidden from your navigation, but still viewable to the public (not password protected), and should always contain the %s shortcode.', 'event_espresso' ),
							'<span class="highlight" style="padding:3px;margin:0;">[ESPRESSO_TXN_PAGE]</span>'
						);
						?>
					</p>
					<br/><br/>
				</td>
			</tr>

			<tr>
				<th>
					<label for="thank_you_page_id">
						<strong>
							<?php _e('Thank You Page', 'event_espresso'); ?>
						</strong>
						<?php echo EE_Template::get_help_tab_link('return_url_info'); ?>
						<br />
						<?php echo General_Settings_Admin_Page::edit_view_links( $thank_you_page_id );?>
					</label>
				</th>
				<td>
					<select name="thank_you_page_id" data-placeholder="Choose a page...">
						<option value="0">
							<?php _e('Main Page', 'event_espresso'); ?>
						</option>
						<?php General_Settings_Admin_Page::page_settings_dropdown( $thank_you_page_id ); ?>
					</select>
					<span>
						<?php echo General_Settings_Admin_Page::page_and_shortcode_status( $thank_you_page_obj, '[ESPRESSO_THANK_YOU]' ); ?>
					</span>
					<br />
					<p class="description">
						<?php
						echo sprintf(
							__( 'This page should be hidden from your navigation, but still viewable to the public (not password protected), and should always contain the %s shortcode.', 'event_espresso' ),
							'<span class="highlight" style="padding:3px;margin:0;">[ESPRESSO_THANK_YOU]</span>'
						);
						?>
					</p>
					<br/><br/>
				</td>
			</tr>

			<tr>
				<th>
					<label for="cancel_page_id">
						<strong>
							<?php _e('Cancel/Return Page', 'event_espresso'); ?>
						</strong>
						<?php echo EE_Template::get_help_tab_link('cancel_return_info'); ?>
						<br />
						<?php echo General_Settings_Admin_Page::edit_view_links( $cancel_page_id );?>
					</label>
				</th>
				<td>
					<select name="cancel_page_id" data-placeholder="Choose a page...">
						<option value="0">
							<?php _e('Main Page', 'event_espresso'); ?>
						</option>
						<?php General_Settings_Admin_Page::page_settings_dropdown( $cancel_page_id ); ?>
					</select>
					<span>
						<?php echo General_Settings_Admin_Page::page_and_shortcode_status( $cancel_page_obj, '[ESPRESSO_CANCELLED]' ); ?>
					</span>
					<br />
					<p class="description">
						<?php
						echo sprintf(
							__( 'This page should be hidden from your navigation, but still viewable to the public (not password protected), and should always contain a "cancelled transaction" message and the %s shortcode.', 'event_espresso' ),
							'<span class="highlight" style="padding:3px;margin:0;">[ESPRESSO_CANCELLED]</span>'
						);
						?>
					</p>
					<br/><br/>
				</td>
			</tr>

		</tbody>
	</table>

</div>