<?php
/**
 * Option page of the plugin
 *
 * @package CF7_STREAK_CRM_INTEGRATION
 */

namespace CF7_STREAK_CRM_INTEGRATION;

$action_class = new Action();
$pipelines    = $action_class->get_pipelines();

if ( empty( $pipelines ) ) {
	echo 'Please Add a Pipeline first, <a target="_blank" href="https://support.streak.com/en/articles/2761787-creating-a-pipeline">Click here</a> to see more.';
	return;
}
$id = empty( $_POST['post_ID'] ) ? absint( $_REQUEST['post'] ) : absint( $_POST['post_ID'] );
?>


<style>
.streak_crm_page #tabs ul {
	border-bottom: 1px solid #aaa;
	padding-bottom: 3px;
}

.streak_crm_page #tabs ul li {
	display: inline-block;
	margin-right: 10px;
}

.streak_crm_page #tabs ul li a {
	padding: 10px;
	background: #e4e4e4;
	border: 1px solid #ccc;
	text-decoration: none;
	color: #000;
	box-shadow: none;
	outline: none;
}

.streak_crm_page #tabs ul li.ui-state-active a {
	background: #007cba;
	color: #fff;
}

#contact-form-editor .streak_crm_page .form-table th {
	width: 170px;
}

.streak_crm_page select {
	width: 100%;
}
</style>
<div class="streak_crm_page">

	<?php
		$cf_post   = \WPCF7_ContactForm::get_instance( $id );
		$form_tags = $cf_post->collect_mail_tags();
		$logging   = get_post_meta( $id, 'cfsci_logging', true );
	?>

	<table class="form-table">
		<tbody>
			<tr>
				<th scope="row">
					<label
						for="cfsci_logging"><?php echo esc_html_e( 'Enable Logging', 'cf7-streak-crm-integration' ); ?></label>
				</th>
				<td>
					<input type="checkbox" <?php checked( $logging, 'on', true ); ?> name="cfsci_logging" id="cfsci_logging">
					<small><?php echo esc_html( CF7_STREAK_CRM_INTEGRATION_LOG_FILE ); ?></small>
				</td>
			</tr>

		</tbody>
	</table>

	<h1>CRM Pipelines</h1>
	<input type="hidden" name="cfsci_pipelines_number" value="<?php echo count( $pipelines ); ?>">
	<br>
	<div id="tabs">
		<ul>
			<?php
			$i = -1;
			foreach ( $pipelines as $pipeline ) {
				$i++;
				?>
			<li><a href="#tabs-<?php echo esc_attr( $i ); ?>"><?php echo esc_attr( $pipeline->name ); ?></a></li>
			<?php } ?>
		</ul>
		<div class="content">
			<?php
			$i = -1; foreach ( $pipelines as $pipeline ) {
				$i++;
				$enabled = esc_attr( get_post_meta( $id, 'cfsci_enabled_' . $i, true ) );
				$type    = intval( get_post_meta( $id, 'cfsci_type_' . $i, true ) );
				?>
			<input type="hidden" name="cfsci_team_<?php echo esc_attr( $i ); ?>"
				value="<?php echo esc_attr( $pipeline->teamKey ); ?>">
			<input type="hidden" name="cfsci_pipeline_key_<?php echo esc_attr( $i ); ?>"
				value="<?php echo esc_attr( $pipeline->pipelineKey ); ?>">
			<div id="tabs-<?php echo esc_attr( $i ); ?>">
				<table class="form-table">
					<tbody>
						<tr>
							<th scope="row">
								<label for="cfsci_enabled_<?php echo esc_attr( $i ); ?>">Enable Sending
									<?php echo esc_html( $pipeline->name ); ?></label>
							</th>
							<td>
								<input type="checkbox" <?php checked( $enabled, 'on', true ); ?>
									name="cfsci_enabled_<?php echo esc_attr( $i ); ?>"
									id="cfsci_enabled_<?php echo esc_attr( $i ); ?>">
							</td>
						</tr>
					</tbody>
				</table>
				<hr>

				<h3><?php echo esc_html_e( 'Person/Organization Fields', 'cf7-streak-crm-integration' ); ?></h3>

				<table class="form-table">

					<tbody>

						<tr>
							<th scope="row">
								<label
									for="cfsci_type_<?php echo esc_attr( $i ); ?>"><?php echo esc_html_e( 'Add as a Person or Organization', 'cf7-streak-crm-integration' ); ?></label>
							</th>
							<td>
								<select class="cfsci_type_class" name="cfsci_type_<?php echo esc_attr( $i ); ?>"
									id="cfsci_type_<?php echo esc_attr( $i ); ?>">
									<option 
									<?php
									if ( $type === 1 ) {
										echo 'selected';
									}
									?>
								 value="1">Person</option>
									<option 
									<?php
									if ( $type === 2 ) {
										echo 'selected';
									}
									?>
								 value="2">Organization</option>
								</select>
							</td>
						</tr>
					</tbody>
				</table>

				<table class="form-table pipeline_person" 
				<?php
				if ( $type === 2 ) {
					echo 'style="display:none"';}
				?>
				>
					<tbody>
						<?php
						foreach ( $action_class->get_person_data() as $key => $val ) {
							$name  = 'cfsci_pipeline_person_' . $i . '[' . $key . ']';
							$value = esc_attr( get_post_meta( $id, 'cfsci_pipeline_person_' . $i, true ) );
							if ( ! empty( $value ) ) {
								if ( isset( $value[ $key ] ) ) {
									$value = esc_attr( $value[ $key ] );
								}
							}
							?>
						<tr>
							<th scope="row">
								<label
									for="cfsci_pipeline_person_<?php echo esc_attr( $i ); ?>"><?php echo esc_attr( $val ); ?> <?php
									if ( $key === 'emailAddresses' ) {
										echo '<span style="color:#d61212;">*</span>';}
									?>
								</label>
							</th>
							<td>
								<select name="<?php echo esc_attr( $name ); ?>"
									id="cfsci_pipeline_person_<?php echo esc_attr( $i ); ?>">
									<option value="">-None-</option>
									<?php
									foreach ( $form_tags as $tag ) {
										echo '<option ' . selected( $value, $tag, false ) . ' value="' . esc_attr( $tag ) . '">[' . esc_attr( $tag ) . ']</option>';
									}
									?>
								</select>
							</td>
						</tr>
						<?php } ?>

					</tbody>
				</table>

				<table class="form-table pipeline_org" 
				<?php
				if ( $type === 1 || $type === 0 ) {
					echo 'style="display:none"';}
				?>
				>
					<tbody>
						<?php
						foreach ( $action_class->get_organization_data() as $key => $val ) {
							$name  = 'cfsci_pipeline_org_' . $i . '[' . $key . ']';
							$value = esc_attr( get_post_meta( $id, 'cfsci_pipeline_org_' . $i, true ) );
							if ( ! empty( $value ) ) {
								if ( isset( $value[ $key ] ) ) {
									$value = esc_attr( $value[ $key ] );
								}
							}
							?>
						<tr>
							<th scope="row">
								<label
									for="cfsci_pipeline_org_<?php echo esc_attr( $i ); ?>"><?php echo esc_attr( $val ); ?> <?php
									if ( $key === 'domains' || $key === 'name' ) {
										echo '<span style="color:#d61212;">*</span>';}
									?>
								</label>
							</th>
							<td>
								<select name="<?php echo esc_attr( $name ); ?>"
									id="cfsci_pipeline_org_<?php echo esc_attr( $i ); ?>">
									<option value="">-None-</option>
									<?php
									foreach ( $form_tags as $tag ) {
										echo '<option ' . selected( $value, $tag, false ) . ' value="' . esc_attr( $tag ) . '">[' . esc_attr( $tag ) . ']</option>';
									}
									?>
								</select>
							</td>
						</tr>
						<?php } ?>

					</tbody>
				</table>


				<hr>



			</div>
			<?php } ?>
		</div>
	</div>
</div>
