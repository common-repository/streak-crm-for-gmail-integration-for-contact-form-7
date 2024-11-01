<?php
/**
 * Option page of the plugin
 *
 * @package CF7_STREAK_CRM_INTEGRATION
 */
$validation = $this->validate_api_key();
?>



<div class="cfsci-block">
<div class="top">
	<div class="logo">
		<h1>Streak CRM</h1>
		<small>V <?php echo CF7_STREAK_CRM_INTEGRATION_VERSION; ?></small>
	</div>
	<div class="buttons">
		<a target="_blank" href="https://www.youtube.com/watch?v=va9sjpp4o8A">Video Tutorials</a>
		<a target="_blank" href="https://wisersteps.com/docs/elementor-pro-form-widget-benchmark-email-integration/setup-the-plugin/">Documentation</a>
	</div>
</div>
<div class="card">
<div class="streak_crm_page">
  <h2 class="nav-tab-wrapper">
	<a href="admin.php?page=cf7-streak-crm-integration" class="nav-tab fs-tab nav-tab-active home">Settings</a>
  </h2>
	<h4>You can get these information from <a target="_blank" href="https://support.streak.com/en/articles/2612883-get-your-streak-api-key">From here</a></h4>
	<form method="post" action="options.php">
		<?php
			settings_fields( 'cfsci_option_page' );
			do_settings_sections( 'cfsci_option_page' );
		?>
		<style>

		.cfsci-block .top {
			padding: 25px 30px;
			background: #dc6701;
			margin-top: 20px;
			overflow: hidden;
		}
		.cfsci-block .top .logo {
			float: left;
		}
		.cfsci-block .top h1 {
			color: #fff;
			display: inline-block;
			margin: 0;
		}
		.cfsci-block .top small {
			color: #fff;
			font-size: 12px;
		}
		.cfsci-block .top a {
			padding: 7px 25px;
			margin-right: 13px;
			border: 1px solid #fff;
			font-size: 15px;
			color: #fff;
			text-decoration: none;
		}
		.cfsci-block .buttons {
			float: right;
		}

		.cfsci-block .card {
			max-width: 100%;
			border: 0px;
			margin-top: 0px;
		}
		.cfsci-block .card h1 {
			margin: 0;
		}
		.cfsci-block .button-primary {
			background: #dc6701;
			border-color: #dc6701;
			color: #fff;
			text-decoration: none;
			text-shadow: none;
		}
		.cfsci-block .button-primary:hover {
			background: #dc6701;
			border-color: #dc6701;
			color: #fff;
			text-decoration: none;
			text-shadow: none;
		}


		.cfsci-block table span{
			background: #11bf47;
			display: inline-block;
			padding: 5px 10px;
			color: #fff;
			margin-top:10px;
		}

		.cfsci-block table span.error{
			background: #d43636;
		}

		</style>
		<table class="form-table streak_crm_page">
			<tbody>

				<tr>
				<th scope="row"><label for="cfsci_api_key"><?php esc_html_e( 'API Key', 'cf7-streak-crm-integration' ); ?></label></th>
					<td>
						<input type="text" class="regular-text" name="cfsci_api_key" value="<?php echo esc_attr( get_option( 'cfsci_api_key' ) ); ?>">
						<?php
						if ( ! $validation ) {
							echo '<span class="error">Not Connected</span>';
						} else {
							echo '<span>Connected</span>';
						}
						?>
					</td>
				</tr>
			</tbody>
		</table>
		<?php submit_button(); ?>
	</form>
</div>

</div>
</div>

