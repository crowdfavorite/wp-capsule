/**
 * Admin settings.
 *
 * @package capsule
 */
import axios from 'axios';
import Swal from 'sweetalert2';

let __ = wp.i18n.__;
let _n = wp.i18n._n;

jQuery(($) => {
	var Capsule = {
		/**
		 * Initialize.
		 */
		init() {
			this.$body = $('body');
			this.registerLicenseSave();
			this.registerLicenseReveal();
			this.registerLicenseRevoke();
			this.registerLicenseCheck();
		},
		/**
		 * Send command via Ajax.
		 */
		sendCommand(action, data, callback, options = {}, blockUI = true) {
			if (blockUI) {
				$.blockUI({
					message: `
						<div id="capsule-sl-logo-loading" aria-label="${__('Loading', 'edd-software-licensing-example')}">
							<img src="${capsule_sl_admin.loading}" style="max-width: 200px;" /><br />
						</div>`,
					css: {
						border: 'none',
						padding: '15px',
						backgroundColor: 'transparent',
					},
					overlayCSS: { backgroundColor: '#FFF' },
				});
			}

			let default_options = {
				json: true,
				alert_on_error: false,
				prefix: 'capsule_sl_',
				nonce: $('#_capsule_sl').val(),
				timeout: null,
				async: true,
				type: 'POST',
			};

			for (let opt in default_options) {
				if (!options.hasOwnProperty(opt)) {
					options[opt] = default_options[opt];
				}
			}

			// Axios and WordPress require data as form data.
			var formData = new FormData();
			for (let key in data) {
				if (data.hasOwnProperty(key)) {
					formData.append(key, data[key]);
				}
			}

			formData.append('action', options.prefix + action);

			axios({
				method: options.type,
				url: ajaxurl,
				data: formData,
			})
				.then((response) => {
						$.unblockUI();
						if (!response.data.success && options.alert_on_error) {
							alert(response.data.data.message);
							return;
						}
						if ('function' === typeof callback) callback(response.data);
					},
					(response) => {
						$.unblockUI();
						alert(__('Could not complete request', 'edd-software-licensing-example'));
					});
		},
		/**
		 * Register the reset button on the white label savings button.
		 */
		registerLicenseSave() {
			this.$body.on('click', '#capsule-sl-license-save', (e) => {
				e.preventDefault();
				Capsule.sendCommand(
					'license_save',
					{
						nonce: $('#_capsule_sl').val(),
						license: $('#edd-license').val(),
					},
					(response) => this.updateContent(response)
				);
			});
		},
		/**
		 * Revokes a license.
		 */
		revokeLicense() {
			Capsule.sendCommand(
				'license_deactivate',
				{
					nonce: $('#_capsule_sl').val(),
					license: $('#edd-license').val(),
				},
				(response) => this.updateContent(response)
			);
		},
		/**
		 * Register the reset button on the white label savings button.
		 */
		registerLicenseRevoke() {
			this.$body.on('click', '#capsule-sl-license-deactivate', (e) => {
				e.preventDefault();

				Swal.fire({
					title: __( 'Revoke License?', 'edd-software-licensing-example' ),
					text: __( 'You can always enter your license again later.', 'edd-software-licensing-example' ),
					icon: 'warning',
					showCancelButton: true,
					confirmButtonColor: '#c3640f',
					cancelButtonColor: '#721c24',
					confirmButtonText: __( 'Revoke License', 'edd-software-licensing-example' ),
				}).then((result) => {
					if (result.value) {
						Capsule.revokeLicense();
					}
				})
			});
		},
		/**
		 * Register the check button on the license check button.
		 */
		registerLicenseCheck() {
			this.$body.on('click', '#capsule-sl-license-check', (e) => {
				e.preventDefault();
				Capsule.sendCommand(
					'license_check',
					{
						nonce: $('#_capsule_sl').val(),
						license: $('#edd-license').val(),
					},
					(response) => this.updateContent(response)
				);
			});
		},
		/**
		 * Reveal the license.
		 */
		registerLicenseReveal() {
			this.$body.on('click', '#capsule-sl-field-license-reveal', (e) => {
				let $license = $('#edd-license');
				if ('password' === $license.prop('type')) {
					$license.prop('type', 'text');
				} else {
					$license.prop('type', 'password');
				}
			});
		},
		/**
		 * Update content.
		 */
		updateContent(response) {
			if (response.success) {
				$('.license-status')
					.removeClass('capsule-sl-success capsule-sl-error')
					.addClass('capsule-sl-success')
					.html(response.data.message)
					.css('display', 'block');
				$('.capsule-sl-action-buttons-wrapper').html( response.data.html );
			} else {
				$('.license-status')
					.removeClass('capsule-sl-success capsule-sl-error')
					.addClass('capsule-sl-error')
					.html(response.data[0].message)
					.css('display', 'block');
			}
		}
	};
	Capsule.init();
});
