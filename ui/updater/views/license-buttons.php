<?php

/**
 * License buttons partial view.
 *
 * @param array $options Options array containing 'license' and 'license_status'.
 *
 * @package capsule
 */

?>
<div class="capsule-sl-action-buttons-wrapper">
	<div class="capsule-sl-field capsule-sl-field--buttons">
		<button id="capsule-sl-license-save" class="capsule-sl-button capsule-sl-button-save">
			<?php esc_html_e('Save', 'capsule'); ?>
		</button>
		<?php
		if ('valid' === $options['license_status']) :
			?>
			<button id="capsule-sl-license-check"
				class="capsule-sl-button capsule-sl-button-secondary capsule-sl-button-info">
				<?php esc_html_e('Check License', 'capsule'); ?>
			</button>
			<button
				id="capsule-sl-license-deactivate"
				class="capsule-sl-button capsule-sl-button-danger capsule-sl-flex-float-right">
				<?php esc_html_e('Revoke License', 'capsule'); ?>
			</button>
		<?php endif; ?>
	</div>
</div>
