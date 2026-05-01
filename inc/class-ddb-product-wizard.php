<?php

class Ddb_Product_Wizard extends Ddb_Product_Form {

	/**
	 * Step-by-step wizard schema for supported product-form fields.
	 *
	 * Each step contains ordered items. Standard field items use `key`; taxonomy rows use `taxonomy_key`;
	 * informational rows use `message`. Any item may optionally include a `color` hex value for the
	 * left accent border in the wizard table.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	public static $wizard_fields = array(
		'general-product-info' => array(
			'label'       => 'Step 1: General product info',
			'description' => '<p>On this step, you will enter the general product information that will be shown in the top section of the product page.</p>'
            . '<p>The only required fields are the product name and price. You can navigate away to other steps and come back to this step later.</p>'
            . '<p>Don\'t forget  to save entered info by clicking the "Create Product" button.</p>'
            . '<p>You can click the "Show step image" button to see layout of the corresponding part of the product page. Fields in the step form marked with the same colors as in the illustrating image.</p>',
            'image'       => 'APD_Wizard_Step_1.png',
			'items'       => array(
				array(
					'key'         => 'product_name',
					'description' => 'Enter the product name exactly as it should appear on the storefront. Mention the developer name - e.g. "by X".',
                    'examples' => array(
                        'OmniBEATs Collection by Audiofier',
                        'Sound Yeti Film Scoring Tools – Cinematic Drama',
                        'Pianoforte Beurmann Edition by Realsamples'
                    ),
                    'color' => '#ff7800'
				),
				array(
					'key'         => 'permalink_slug',
					'description' => 'This is the portion of the product URL that appears after <em>https://audioplugin.deals/product/</em>. If you enter XXX, your product page will have the address <em>https://audioplugin.deals/product/XXX</em>',
                    'examples' => array(
                        '<a href="https://audioplugin.deals/product/omnibeats-collection-by-audiofier/">omnibeats-collection-by-audiofier</a>',
                        '<a href="https://audioplugin.deals/product/sound-yeti-film-scoring-tools-cinematic-drama/">sound-yeti-film-scoring-tools-cinematic-drama</a>',
                        '<a href="https://audioplugin.deals/product/pianoforte-beurmann-edition-by-realsamples/">pianoforte-beurmann-edition-by-realsamples</a>'
                    ),
				),
				array(
					'key'         => 'lto_company_name',
					'description' => 'Enter the company or developer name as it should be shown to customers.',
                    'examples' => array(
                        'Audiofier',
                        'RealSamples',
                        'Image Sounds',
                        'Sonible'
                    ),
				),
                array(
					'key'         => '_thumbnail_id',
					'label'       => 'Main product image',
					'description' => 'Recommended size is 314x440 or ratio 1:1.4. This image appears in the top product section.',
                    'color' => '#2ec27e',
					'examples' => array(
						'image:3817482',
						'image:3813936',
						'image:3645758',
					),
				),
				array(
					'key'         => 'regular_price',
					'description' => 'Enter the regular product price in USD. It will be shown in the product top section under the product description, after "RETAIL PRICE" header.',
				),
				array(
					'key'         => 'short_description',
					'description' => 'Keep this short. Prefer 2 or 3 brief paragraphs, include a heading, and mention formats and compatibility.',
                    'color' => '#9141ac',
                    'examples' => array(
                        "<b>DUAL-ENGINE SONIC MORPHING</b><br>

<p>Parallax is a dual-engine multi-effect designed to sculpt sound between precision and abstraction. The Reality Engine tightens transients and harmonics for punch, while the Dream Engine dissolves audio into evolving textures and spatial depth.</p>

<p>A single Morph control lets you drift seamlessly between these two worlds, creating organic movement without complex routing. Smart parameter linking ensures every adjustment stays musical and balanced.</p>",
                    ),
				),
			),
		),
        'overview-section' => array(
			'label'       => 'Step 2: Overview section',
			'description' => 'Fill the content used in the Overview section (which is under the top section of the product page).',
            'image'       => 'APD_Wizard_Step_2.png',
			'items'       => array(
                array(
					'key'         => 'description_overview',
					'description' => 'This is a sub-heading under large "OVERVIEW" header. Use a short all-caps sentence, ideally under a dozen words.',
                    'examples' => array(
                        'BOUTIQUE HANDPAN LIBRARY RECORDED WITH HIGH-END SIGNAL CHAINS',
                        'AUTHENTIC OLD SCHOOL RECORDING PIANO CHARACTER FROM THE 1950S AND 60S',
                        'PROFESSIONAL GRADE TOOLKIT FOR FILM AND MEDIA COMPOSERS'
					),
                    'color' => '#ffa348',
				),
				array(
					'key'         => 'overview-product-description',
					'description' => 'Write a fuller overview covering what the product does, its features, and its benefits.',
                    'color' => '#33d17a',
				),
				array(
					'key'         => 'button-url',
					'description' => 'Link to the product page on the developer website. This will be shown as a button in the overview section.',
                    'examples' => array(
                        'https://www.auddict.com/angelstrings',
                        'https://www.realsamples.com/pianoforte-beurmann-edition',
                        'https://www.audiofier.com/omnibeats-collection'
                    ),
                    'color' => '#c01c28',
				),
                array(
					'key'         => 'overiew-product-title',
					'description' => 'Enter the product title as it should appear inside the overview section. Usually it is the same as the product name.',
                    'color' => '#9141ac',
				),
				array(
					'key'         => 'overiew-product-image',
					'description' => 'An image with a transparent background to be used as a product illustration for the overview section.',
                    'color' => '#1a5fb4',
					'examples' => array(
						'image:3648945', 
						'image:3725524',
						'image:1367682',
					),
				),
                array(
                    'key'         => 'value-price',
                    'description' => 'Enter the product price in USD. It will be shown in the overview section after "Visit Product Page" button. Prepend with "$" sign.',
                    'color' => '#000000',
                )
			),
		),
		'media-section' => array(
			'label'       => 'Step 3: Media section',
			'description' => 'Add the media content and supporting section header.',
			'image'       => 'APD_Wizard_Step_3.png',
			'items'       => array(
                array(
					'key'         => 'media-description',
					'description' => 'Use a short all-caps media section header, ideally under a dozen words.',
                    'examples' => array(
                        'BUILT IN 12 STRING MODE FOR RICHER HARMONIC LAYERING',
                        'FEATURES TWENTY-ONE SETS OF TIME-SYNCED DIATONIC PHRASES',
                        'HIGH-QUALITY 24-BIT AUDIO FOR PROFESSIONAL PRODUCTION STANDARDS'
                    ),
                    'color' => '#ffa348',
				),
				array(
					'key'         => 'video_url_for_mainpage_banner',
					'description' => 'Add a single YouTube video URL for the product.',
                    'examples' => array(
                        'https://www.youtube.com/watch?v=FquRvVSInZc',
                        'https://www.youtube.com/watch?v=ikCcFFbSCuQ'
                    ),
                    'color' => '#3584e4',
				),
				array(
					'key'         => 'youtube-videos',
					'description' => 'Enable YouTube playlist for the product (instead of single YouTube video).',
				),
				array(
					'key'         => 'youtube_playlist_url',
					'label'       => 'YouTube playlist ID',
					'description' => 'If the product has a YouTube playlist, enter the playlist ID only.',
					'color' => '#3584e4',
					'examples' => array(
						'PLq5zvy3wsKYaholnypxm2rNvpmPDn9s9C',
						'PLq5zvy3wsKYZ3s0G75_z-qa4mY5MQ1A2z'
					),
				),
				array(
					'key'         => 'soundcloud_url',
					'description' => 'Paste the SoundCloud player URL from the embed code when available.',
					'color' => '#c01c28',
					'examples' => array(
						'https://w.soundcloud.com/player/?url=https%3A//api.soundcloud.com/playlists/soundcloud%253Aplaylists%253A1419909442&.....'
					),
				),
				array(
					'key'         => 'add_features',
					'description' => 'This list will be shown in the media section to the right of the video player. Use a bullet list with one feature per line. Start each line with "* ", keep it to 12 features maximum, and keep each feature brief.',
                    'default_value' => "* Feature 1\n* Feature 2\n* Feature 3",
					'color' => '#9141ac',
                    'examples' => array(
                        '* Includes seven specialized sound banks for deep sonic variety',
                        '* High quality twenty-four bit and ninety-six kilohertz audio',
                        '* Provides over one hundred unique vocal effect rack patches',
                        '* Includes granular reverbs and echoes for external audio processing',
                    ),
				),
			),
		),
		'main-images' => array(
			'label'       => 'Step 4: Wide Images',
			'description' => 'Upload the images used in the product page for top, overview and media sections. Recommended size is 1920x300 or ratio 32:5',
			'image'       => 'APD_Wizard_Step_4.png',
			'items'       => array(
				array(
					'key'         => 'image_for_single_shop_page',
					'label'       => 'Main header image',
					'description' => 'This image appears above the top section.',
					'examples' => array(
						'image:3799014',
						'image:2669657',
						'image:2878304', 
					),
				),
				array(
					'key'         => 'media-background-image',
					'description' => 'This image appears in the media section header as a background.',
					'examples' => array(
						'image:3648950', 
						'image:3639854',
						'image:3640214', 
					),
				),
				array(
					'key'         => 'overview-image',
					'description' => 'This image appears in the overview section as a background.',
					'examples' => array(
						'image:3646277', 
						'image:3645757', 
						'image:3645236',
					),
				),
			),
		),
		'other-product-information' => array(
			'label'       => 'Step 5: Other info',
			'description' => 'Complete the remaining product information and classifications. It will not be shown on the product page, but will be used for emails, SEO and product categorization.',
			'items'       => array(
				array(
					'key'         => '_redeem_link',
					'description' => 'Add the redeem URL customers should use after purchase. It will be shown in email notification to the customer after purchase.',
					'examples' => array(
						'https://babyaudio.onfastspring.com/crystalline',
						'https://falloutmusicgroup.com/checkouts/cn/hWNApXuDF56pbmoJAqNHo6kd/',
						'https://gospelmusicians.com/products/neo-soul-keys-2-bundle'
					),
				),
				array(
					'key'         => '_sku',
					'description' => 'Use the shop-product SKU format: '.
					'<ul><li>S for shop product</li><li>then developer acronym (e.g. APD)</li><li>then product acronym (e.g. TST)</li></ul>',
					'examples' => array(
						'S-BA-CRY for "Crystalline by Baby Audio"',
						'S-UJM-BMV for "Beatmaker Vice by UJAM"',
						'S-FMG-DES for "Desecrator by Fallout Music Group"'
					),
				),/*
				array(
					'taxonomy_key' => 'product_categories',
					'description'  => 'Select the product categories that best match this product.',
				),*/
				array(
					'taxonomy_key' => 'instrument_categories',
					'description'  => 'Select relevant instrument categories such as Piano or Synth.',
				),
				array(
					'taxonomy_key' => 'supported_formats',
					'description'  => 'Select the supported formats such as Kontakt Retail, VST3, or Apple Loops.',
				),
			),
		),
		'emails' => array(
			'label'       => 'Step 6: Emails',
			'description' => 'Set contents for email notifications which will be sent to customers after purchase.',
			'items'       => array(
				array(
					'key'         => 'apd_product_email_heading',
					'description' => 'Set the subject line for the email notification.',
					'examples' => array(
						'Download Instructions for {product_name}.',
					),
				),
				array(
					'key'         => 'apd_product_email_template',
					'description' => 'Set the content of the email notification.<br> Avaliable shortcodes: '
									. '<br>{product_name}, {customer_name}, {coupon_code}, {company_name}, {redeem_link}, {url}',
					'default_value' => '<p style="margin-bottom: 12px;">Hi there!</p>
	<p style="margin-bottom: 12px;">Thank you for your purchase of {product_name}.</p>
	Click the following link to redeem your purchase: {coupon_code}
	<p style="margin-bottom: 12px;">Please review installation instructions here: <a href="your link here">your link here</a></p>
	<p style="margin-bottom: 12px;">In case you need help, you can reach out directly to {company_name} via this email address: <a href="mailto:your email">your email</a></p>
	<p style="margin-bottom: 12px;">Best regards,
	APD Support.</p>',
	'examples' => array(
		'<p style="margin-bottom: 12px;">Hi there!</p>
	<p style="margin-bottom: 12px;">Thank you for your purchase of {product_name}.</p>
	Click the following link to redeem your purchase: {coupon_code}
	<p style="margin-bottom: 12px;">Please review installation instructions here: <a href="your link here">your link here</a></p>
	<p style="margin-bottom: 12px;">In case you need help, you can reach out directly to {company_name} via this email address: <a href="mailto:your email">your email</a></p>
	<p style="margin-bottom: 12px;">Best regards,
	APD Support.</p>',
	),
				),
			),
		),
		'license-codes' => array(
			'label'       => 'Step 7: License codes',
			'description' => 'Add license keys for the product.',
		),
	);

	/**
	 * Render the create/edit product wizard.
	 *
	 * @param int $product_id Optional product ID to edit.
	 * @return string HTML
	 */
	public static function render_developer_product_wizard( $product_id = 0 ) {
		$out = Ddb_Frontend::notice_error_html( esc_html__( 'Not authorized', DDB_TEXT_DOMAIN ) );

		$user           = wp_get_current_user();
		$developer_term = false;

		if ( $user && is_array( $user->roles ) && in_array( self::DEV_ROLE_NAME, $user->roles, true ) ) {
			$developer_term = self::find_developer_term_by_user_id( $user->ID );
		}

		if ( ! is_object( $developer_term ) || ! is_a( $developer_term, 'WP_Term' ) ) {
			return $out;
		}

		if ( ! post_type_exists( 'product' ) || ! taxonomy_exists( 'developer' ) ) {
			return '<div id="developer-dashboard" class="ddb-developer-products ddb-developer-create-product"><p>' . esc_html__( 'The product catalog is not available on this site.', DDB_TEXT_DOMAIN ) . '</p></div>';
		}

		$notice_html       = Ddb_Frontend::get_product_form_notice_html();
		$repopulate_values = Ddb_Frontend::should_repopulate_product_form_values();
		$edit_product_id   = absint( $product_id );
		$is_edit_form      = false;
		$edit_product      = null;
		$form_action       = Ddb_Frontend::ACTION_CREATE_DEVELOPER_PRODUCT;
		$form_nonce_action = Ddb_Frontend::NONCE_ACTION_CREATE_PRODUCT;
		$form_heading      = __( 'Create new product', DDB_TEXT_DOMAIN );
		$switch_form_url   = add_query_arg( 'section', 'create-product' );

		if ( $edit_product_id > 0 ) {
			if ( self::is_deal_product( $edit_product_id ) ) {
				return '<div id="developer-dashboard" class="ddb-developer-products ddb-developer-create-product ddb-product-wizard"><p>' . esc_html__( 'Editing is not available there.', DDB_TEXT_DOMAIN ) . '</p><p class="ddb-create-product__back"><a href="' . esc_url( remove_query_arg( 'product_id', add_query_arg( 'section', 'products' ) ) ) . '">' . esc_html__( '← Back to your products', DDB_TEXT_DOMAIN ) . '</a></p></div>';
			}

			$candidate_post = get_post( $edit_product_id );
			if ( $candidate_post && 'product' === $candidate_post->post_type && has_term( (int) $developer_term->term_id, 'developer', $edit_product_id ) ) {
				$edit_product      = $candidate_post;
				$is_edit_form      = true;
				$form_action       = Ddb_Frontend::ACTION_EDIT_DEVELOPER_PRODUCT;
				$form_nonce_action = Ddb_Frontend::NONCE_ACTION_EDIT_PRODUCT;
				$form_heading      = __( 'Edit product', DDB_TEXT_DOMAIN );
				$switch_form_url   = add_query_arg(
					array(
						'section'    => 'edit-product',
						'product_id' => $edit_product_id,
					),
					''
				);
			}
		}

		$wizard_steps    = self::prepare_wizard_steps_for_render( $is_edit_form );
		$active_step_key = self::get_requested_wizard_step_key( $wizard_steps );

		ob_start();
		?>
		<div id="developer-dashboard" class="ddb-developer-products ddb-developer-create-product ddb-product-wizard">
			<h2><?php echo esc_html( $form_heading ); ?></h2>
			<?php echo $notice_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<form method="post" class="ddb-create-product-form wizard-form" action="" enctype="multipart/form-data">
				<?php wp_nonce_field( $form_nonce_action, 'ddb_create_product_nonce' ); ?>
				<input type="hidden" name="ddb_active_wizard_step" value="<?php echo esc_attr( $active_step_key ); ?>" />
				<input type="hidden" name="ddb_edit_product_origin" value="wizard" />
				<?php if ( $is_edit_form ) : ?>
					<input type="hidden" name="product_id" value="<?php echo esc_attr( (string) $edit_product_id ); ?>" />
					<?php echo self::get_hidden_non_wizard_fields_html( $edit_product, $repopulate_values ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php endif; ?>
				<div class="wizard-layout">
					<nav class="wizard-steps" aria-label="<?php echo esc_attr__( 'Product wizard steps', DDB_TEXT_DOMAIN ); ?>">
						<ol class="wizard-step-list">
							<?php foreach ( $wizard_steps as $step_key => $step ) : ?>
								<?php $is_active = ( $step_key === $active_step_key ); ?>
								<li class="wizard-step-item">
									<button
										type="button"
										class="wizard-step-button<?php echo $is_active ? ' is-active' : ''; ?>"
										data-ddb-wizard-step="<?php echo esc_attr( $step_key ); ?>"
										aria-pressed="<?php echo $is_active ? 'true' : 'false'; ?>"
									>
										<span class="wizard-step-title"><?php echo esc_html( (string) $step['label'] ); ?></span>
									</button>
								</li>
							<?php endforeach; ?>
						</ol>
					</nav>
					<div class="wizard-content">
						<?php foreach ( $wizard_steps as $step_key => $step ) : ?>
							<?php $is_active = ( $step_key === $active_step_key ); ?>
							<section class="wizard-panel<?php echo $is_active ? ' is-active' : ''; ?>" data-ddb-wizard-panel="<?php echo esc_attr( $step_key ); ?>"<?php echo $is_active ? '' : ' hidden'; ?>>
								<header class="wizard-panel-header">
									<h3 class="wizard-panel-title"><?php echo esc_html( (string) $step['label'] ); ?></h3>
									<?php if ( ! empty( $step['description'] ) ) : ?>
										<p class="wizard-panel-description"><?php echo $step['description']; ?></p>
									<?php endif; ?>
									<?php if ( ! empty( $step['image_url'] ) ) : ?>
										<div class="wizard-step-image-tools">
											<button
												type="button"
												class="button wizard-step-image-button"
												data-ddb-step-image-target="wizard-step-image-<?php echo esc_attr( $step_key ); ?>"
												aria-expanded="false"
											><?php esc_html_e( 'Show step image', DDB_TEXT_DOMAIN ); ?></button>
										</div>
										<div class="wizard-step-image" id="wizard-step-image-<?php echo esc_attr( $step_key ); ?>" hidden>
											<img src="<?php echo esc_url( $step['image_url'] ); ?>" alt="<?php echo esc_attr( (string) $step['label'] ); ?>" loading="lazy" decoding="async" />
										</div>
									<?php endif; ?>
								</header>
								<table class="ddb-report-form-table wizard-table">
									<tbody>
										<?php self::render_wizard_step_rows( $step_key, $step, $edit_product, $repopulate_values ); ?>
									</tbody>
								</table>
							</section>
						<?php endforeach; ?>
						<div class="submit wizard-actions">
							<div class="wizard-step-nav">
								<button type="button" class="button wizard-prev-step"><?php esc_html_e( 'Previous step', DDB_TEXT_DOMAIN ); ?></button>
								<button type="button" class="button wizard-next-step"><?php esc_html_e( 'Next step', DDB_TEXT_DOMAIN ); ?></button>
							</div>
							<input type="submit" name="<?php echo esc_attr( self::BUTTON_SUMBIT ); ?>" class="button button-primary" value="<?php echo esc_attr( $form_action ); ?>" />
						</div>
					</div>
				</div>
			</form>
			<script>
				(function() {
					var scriptTag = document.currentScript;
					var wizardRoot = scriptTag ? scriptTag.parentNode : null;
					if (!wizardRoot || !wizardRoot.classList || !wizardRoot.classList.contains('ddb-product-wizard')) {
						wizardRoot = document.querySelector('.ddb-product-wizard');
					}
					if (!wizardRoot) {
						return;
					}
					var root = wizardRoot.querySelector('.wizard-form');
					if (!root) {
						return;
					}
					var activeStepInput = root.querySelector('input[name="ddb_active_wizard_step"]');
					var stepButtons = Array.prototype.slice.call(root.querySelectorAll('.wizard-step-button'));
					var stepPanels = Array.prototype.slice.call(root.querySelectorAll('.wizard-panel'));
					var prevStepButton = root.querySelector('.wizard-prev-step');
					var nextStepButton = root.querySelector('.wizard-next-step');
					var stepImageButtons = Array.prototype.slice.call(root.querySelectorAll('.wizard-step-image-button'));
					var slugAutofillButtons = Array.prototype.slice.call(root.querySelectorAll('.ddb-wizard-autofill-slug'));
					var productTitleField = root.querySelector('input[name="ddb_product_title"]');
					var permalinkSlugField = root.querySelector('input[name="ddb_pf_permalink_slug"]');
					var youtubeVideosField = root.querySelector('input[name="ddb_pf_youtube_videos"]');
					var mainBannerVideoField = root.querySelector('input[name="ddb_pf_video_url_for_mainpage_banner"]');
					var youtubePlaylistField = root.querySelector('input[name="ddb_pf_youtube_playlist_url"]');
					if (!stepButtons.length || !stepPanels.length) {
						return;
					}
					var sanitizeSlug = function(value) {
						if (typeof value !== 'string') {
							return '';
						}
						return value
							.normalize('NFKD')
							.replace(/[\u0300-\u036f]/g, '')
							.toLowerCase()
							.replace(/[^a-z0-9]+/g, '-')
							.replace(/^-+|-+$/g, '');
					};
					var syncYoutubeMediaRows = function() {
						if (!youtubeVideosField || !mainBannerVideoField || !youtubePlaylistField) {
							return;
						}
						var mainBannerVideoRow = mainBannerVideoField.closest('tr');
						var youtubePlaylistRow = youtubePlaylistField.closest('tr');
						if (!mainBannerVideoRow || !youtubePlaylistRow) {
							return;
						}
						var hasPlaylistEnabled = !!youtubeVideosField.checked;
						var setYoutubeMediaRowState = function(row, isActive) {
							var rowFields;
							var fieldIndex;
							if (!row) {
								return;
							}
							row.style.opacity = isActive ? '1' : '0.1';
							row.style.pointerEvents = isActive ? '' : 'none';
							rowFields = row.querySelectorAll('input, select, textarea, button');
							for (fieldIndex = 0; fieldIndex < rowFields.length; fieldIndex++) {
								rowFields[fieldIndex].disabled = !isActive;
							}
						};
						setYoutubeMediaRowState(mainBannerVideoRow, !hasPlaylistEnabled);
						setYoutubeMediaRowState(youtubePlaylistRow, hasPlaylistEnabled);
					};
					var stepKeys = [];
					for (var s = 0; s < stepButtons.length; s++) {
						stepKeys.push(stepButtons[s].getAttribute('data-ddb-wizard-step'));
					}
					var getActiveStepIndex = function() {
						for (var index = 0; index < stepButtons.length; index++) {
							if (stepButtons[index].classList.contains('is-active')) {
								return index;
							}
						}
						return -1;
					};
					var getInitialStepKey = function() {
						if (activeStepInput && activeStepInput.value) {
							return activeStepInput.value;
						}
						var activeButton = root.querySelector('.wizard-step-button.is-active');
						if (activeButton) {
							return activeButton.getAttribute('data-ddb-wizard-step');
						}
						return stepButtons[0].getAttribute('data-ddb-wizard-step');
					};
					var activateStep = function(stepKey) {
						var matchedStep = false;
						var normalizedStepKey = stepKey;
						for (var i = 0; i < stepButtons.length; i++) {
							var button = stepButtons[i];
							var isActive = button.getAttribute('data-ddb-wizard-step') === stepKey;
							if (isActive) {
								matchedStep = true;
							}
							button.classList.toggle('is-active', isActive);
							button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
						}
						if (!matchedStep) {
							normalizedStepKey = stepButtons[0].getAttribute('data-ddb-wizard-step');
						}
						for (var j = 0; j < stepPanels.length; j++) {
							var panel = stepPanels[j];
							var isTargetPanel = panel.getAttribute('data-ddb-wizard-panel') === normalizedStepKey;
							panel.classList.toggle('is-active', isTargetPanel);
							panel.setAttribute('aria-hidden', isTargetPanel ? 'false' : 'true');
							panel.toggleAttribute('hidden', !isTargetPanel);
						}
						if (activeStepInput) {
							activeStepInput.value = normalizedStepKey;
						}
						var activeIndex = stepKeys.indexOf(normalizedStepKey);
						if (prevStepButton) {
							prevStepButton.disabled = activeIndex <= 0;
						}
						if (nextStepButton) {
							nextStepButton.disabled = activeIndex === -1 || activeIndex >= stepKeys.length - 1;
						}
					};
					var moveStep = function(direction) {
						var currentIndex = getActiveStepIndex();
						if (currentIndex === -1) {
							var currentStepKey = activeStepInput && activeStepInput.value ? activeStepInput.value : getInitialStepKey();
							currentIndex = stepKeys.indexOf(currentStepKey);
						}
						if (currentIndex === -1) {
							currentIndex = 0;
						}
						var nextIndex = currentIndex + direction;
						if (nextIndex < 0 || nextIndex >= stepKeys.length) {
							return;
						}
						activateStep(stepKeys[nextIndex]);
					};
					for (var k = 0; k < stepButtons.length; k++) {
						stepButtons[k].addEventListener('click', function() {
							var stepKey = this.getAttribute('data-ddb-wizard-step');
							if (stepKey) {
								activateStep(stepKey);
							}
						});
					}
					if (prevStepButton) {
						prevStepButton.addEventListener('click', function(event) {
							event.preventDefault();
							moveStep(-1);
						});
					}
					if (nextStepButton) {
						nextStepButton.addEventListener('click', function(event) {
							event.preventDefault();
							moveStep(1);
						});
					}
					for (var m = 0; m < stepImageButtons.length; m++) {
						stepImageButtons[m].addEventListener('click', function(event) {
							event.preventDefault();
							var targetId = this.getAttribute('data-ddb-step-image-target');
							if (!targetId) {
								return;
							}
							var imageWrap = root.querySelector('#' + targetId);
							if (!imageWrap) {
								return;
							}
							var isExpanded = !imageWrap.hasAttribute('hidden');
							imageWrap.toggleAttribute('hidden', isExpanded);
							this.setAttribute('aria-expanded', isExpanded ? 'false' : 'true');
							this.textContent = isExpanded ? 'Show step image' : 'Hide step image';
						});
					}
					for (var n = 0; n < slugAutofillButtons.length; n++) {
						slugAutofillButtons[n].addEventListener('click', function(event) {
							event.preventDefault();
							if (productTitleField && permalinkSlugField) {
								permalinkSlugField.value = sanitizeSlug(productTitleField.value);
                                permalinkSlugField.focus();
							}
						});
					}
					if (youtubeVideosField) {
						youtubeVideosField.addEventListener('change', syncYoutubeMediaRows);
					}
					syncYoutubeMediaRows();
					activateStep(getInitialStepKey());
				})();
			</script>
			<p class="ddb-create-product__back"><a href="<?php echo esc_url( remove_query_arg( 'product_id', add_query_arg( 'section', 'products' ) ) ); ?>"><?php esc_html_e( '← Back to your products', DDB_TEXT_DOMAIN ); ?></a></p>
		</div>
		<?php

		return (string) ob_get_clean();
	}

	/**
	 * Whether the given product is marked as a deal product.
	 *
	 * @param int $product_id Product ID.
	 * @return bool
	 */
	public static function is_deal_product( $product_id = 0 ) {
		$product_id = absint( $product_id );

		if ( $product_id <= 0 ) {
			return false;
		}

		return 'yes' === get_post_meta( $product_id, '_product_big_deal', true );
	}

	/**
	 * @param array<string, mixed> $step          Wizard step definition.
	 * @param \WP_Post|null        $edit_product  Product being edited.
	 * @param bool                 $repopulate    Whether to prefer submitted values.
	 * @return void
	 */
	protected static function render_wizard_step_rows( $step_key, array $step, $edit_product, $repopulate ) {
		if ( 'license-codes' === $step_key ) {
			if ( ! $edit_product instanceof WP_Post ) {
				return;
			}

			ob_start();
			?>
			<tr class="wizard-license-codes-row">
				<td colspan="2" class="wizard-license-codes-cell">
					<?php self::render_product_license_tab_content( (int) $edit_product->ID, (string) $edit_product->post_title, (string) $edit_product->post_status ); ?>
				</td>
			</tr>
			<?php
			echo (string) ob_get_clean(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			return;
		}

		if ( empty( $step['items'] ) || ! is_array( $step['items'] ) ) {
			return;
		}

		foreach ( $step['items'] as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			if ( ! empty( $item['key'] ) && is_string( $item['key'] ) ) {
				$field_set = self::build_field_set_from_refs( array( $item ), $edit_product, $repopulate );
				$field_set = self::apply_wizard_item_defaults( $field_set, $item, $edit_product, $repopulate );
				$field_set = self::apply_wizard_item_styles( $field_set, $item );
				self::display_field_set( $field_set );
				continue;
			}

			if ( ! empty( $item['taxonomy_key'] ) && is_string( $item['taxonomy_key'] ) ) {
				echo self::get_wizard_taxonomy_row_html( $item['taxonomy_key'], $item, $edit_product, $repopulate ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				continue;
			}

			if ( ! empty( $item['message'] ) && is_string( $item['message'] ) ) {
				echo self::get_wizard_message_row_html( $item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
		}
	}

	/**
	 * Normalize wizard step items for rendering.
	 *
	 * Converts item descriptions plus any examples array into HTML that can be shown in the
	 * right-hand description column without additional formatting at render time.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	protected static function prepare_wizard_steps_for_render( $is_edit_form = false ) {
		$steps = self::$wizard_fields;

		if ( ! $is_edit_form ) {
			unset( $steps['license-codes'] );

	
		}

		if ( $is_edit_form ) {
			$steps['other-product-information']['items'][] = 
			array(
				'key'         => 'extra_instrument_category',
				'description' => 'Enter the name of the instrument if it is not in the list. This instrument will be added as a new option',
				'color' => '#333333',
				'examples' => array(
					'Pianos/Keys',
					'Orchestral/Traditional',
					'Vocal',
					'Synth'
				),
			);

			$steps['other-product-information']['items'][] = 
			array(
				'key'         => 'extra_format_category',
				'description' => 'Enter the name of the format if it is not in the list. This format will be added as a new option',
				'color' => '#333333',
				'examples' => array(
					'Kontakt Retail',
					'AIFF/WAVE',
					'MIDI'
				),
			);
		}

		foreach ( $steps as $step_key => $step ) {
			if ( empty( $step['items'] ) || ! is_array( $step['items'] ) ) {
				continue;
			}

			foreach ( $step['items'] as $item_index => $item ) {
				if ( ! is_array( $item ) ) {
					continue;
				}

				$steps[ $step_key ]['items'][ $item_index ] = self::prepare_wizard_item_for_render( $item );
			}

			$steps[ $step_key ]['image_url'] = self::get_wizard_step_image_url( $step );
		}

		return $steps;
	}

	/**
	 * Return the public URL for a wizard step image when the configured file exists in `img`.
	 *
	 * @param array<string, mixed> $step Wizard step definition.
	 * @return string
	 */
	protected static function get_wizard_step_image_url( array $step ) {
		if ( empty( $step['image'] ) || ! is_string( $step['image'] ) ) {
			return '';
		}

		$image_file = basename( $step['image'] );
		if ( '' === $image_file ) {
			return '';
		}

		$image_path = dirname( __DIR__ ) . '/img/' . $image_file;
		if ( ! file_exists( $image_path ) ) {
			return '';
		}

		return plugins_url( 'img/' . rawurlencode( $image_file ), dirname( __DIR__ ) . '/developer-dashboard.php' );
	}

	/**
	 * Build render-ready item data for one wizard field or message.
	 *
	 * @param array<string, mixed> $item Wizard item definition.
	 * @return array<string, mixed>
	 */
	protected static function prepare_wizard_item_for_render( array $item ) {
		$description_html = '';

		if ( isset( $item['description'] ) && is_string( $item['description'] ) && '' !== trim( $item['description'] ) ) {
			$raw_description = trim( $item['description'] );
			if ( false !== strpos( $raw_description, '<' ) ) {
				$description_html .= wp_kses_post( $raw_description );
			} else {
				$description_html .= '<p>' . esc_html( $raw_description ) . '</p>';
			}
		}

		if ( isset( $item['examples'] ) && is_array( $item['examples'] ) && ! empty( $item['examples'] ) ) {
			$has_image_examples = self::wizard_examples_contain_image( $item['examples'] );
			$description_html  .= '<p><b>Examples:</b></p>';
			$description_html  .= $has_image_examples
				? '<span class="ddb-examples-list ddb-examples-inline">'
				: '<ul class="ddb-examples-list">';

			foreach ( $item['examples'] as $example ) {
				if ( ! is_string( $example ) || '' === trim( $example ) ) {
					continue;
				}

				$description_html .= self::render_wizard_example( $example, $has_image_examples ? 'span' : 'li' );
			}
			$description_html .= $has_image_examples ? '</span>' : '</ul>';
		}

		if ( '' !== $description_html ) {
			$item['description'] = $description_html;
		}

		if ( isset( $item['key'] ) && 'permalink_slug' === $item['key'] ) {
			$item['description'] .= '<p><button type="button" class="button button-small ddb-wizard-autofill-slug">' . esc_html__( 'Auto-fill permalink', DDB_TEXT_DOMAIN ) . '</button></p>';
		}

		return $item;
	}

	/**
	 * Render one example list item.
	 *
	 * Supports `image:<attachment_id>` values for media-library previews.
	 *
	 * @param string $example      Raw example value.
	 * @param string $wrapper_tag  HTML tag used to wrap the rendered example.
	 * @return string
	 */
	protected static function render_wizard_example( $example, $wrapper_tag = 'li' ) {
		$trimmed_example = trim( $example );
		$wrapper_tag     = 'span' === $wrapper_tag ? 'span' : 'li';

		if ( 0 === strpos( $trimmed_example, 'image:' ) ) {
			$image_example_html = self::render_wizard_image_example( substr( $trimmed_example, 6 ) );

			if ( '' !== $image_example_html ) {
				return sprintf(
					'<%1$s class="ddb-example-image-item">%2$s</%1$s>',
					$wrapper_tag,
					$image_example_html
				);
			}
		}

		return sprintf(
			'<%1$s class="ddb-example-item">%2$s</%1$s>',
			$wrapper_tag,
			wp_kses_post( $trimmed_example )
		);
	}

	/**
	 * Check whether any example token references a media-library image.
	 *
	 * @param array<int, mixed> $examples Example values from the field schema.
	 * @return bool
	 */
	protected static function wizard_examples_contain_image( array $examples ) {
		foreach ( $examples as $example ) {
			if ( is_string( $example ) && 0 === strpos( trim( $example ), 'image:' ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Render a thumbnail example from a media-library attachment id.
	 *
	 * @param string $attachment_id_raw Attachment id from the example token.
	 * @return string
	 */
	protected static function render_wizard_image_example( $attachment_id_raw ) {
		$attachment_id = absint( trim( $attachment_id_raw ) );

		if ( $attachment_id <= 0 ) {
			return '';
		}

		$full_image_url = wp_get_attachment_url( $attachment_id );
		$thumbnail_html = wp_get_attachment_image( $attachment_id, 'thumbnail' );

		if ( ! $full_image_url || ! $thumbnail_html ) {
			return '';
		}

		return sprintf(
			'<a href="%1$s" target="_blank" rel="noopener noreferrer" class="ddb-example-image-link">%2$s</a>',
			esc_url( $full_image_url ),
			$thumbnail_html
		);
	}

	/**
	 * Apply default values declared in wizard item definitions for brand-new products only.
	 *
	 * @param array<int, array<string, mixed>> $field_set Built field rows.
	 * @param array<string, mixed>             $item      Wizard field item definition.
	 * @param \WP_Post|null                    $edit_product Product being edited.
	 * @param bool                             $repopulate  Whether submitted values should win.
	 * @return array<int, array<string, mixed>>
	 */
	protected static function apply_wizard_item_defaults( array $field_set, array $item, $edit_product, $repopulate ) {
		if ( $repopulate || $edit_product instanceof WP_Post ) {
			return $field_set;
		}

		if ( array_key_exists( 'default_value', $item ) ) {
			$default_raw = $item['default_value'];
		} elseif ( array_key_exists( 'default', $item ) ) {
			$default_raw = $item['default'];
		} else {
			return $field_set;
		}

		foreach ( $field_set as $index => $row ) {
			if ( ! is_array( $row ) || empty( $row['type'] ) ) {
				continue;
			}

			$field_set[ $index ]['value'] = self::format_field_value_for_display( (string) $row['type'], $default_raw );
		}

		return $field_set;
	}

	/**
	 * Apply optional presentation styles declared in wizard item definitions.
	 *
	 * @param array<int, array<string, mixed>> $field_set Built field rows.
	 * @param array<string, mixed>             $item      Wizard field item definition.
	 * @return array<int, array<string, mixed>>
	 */
	protected static function apply_wizard_item_styles( array $field_set, array $item ) {
		$label_style = self::get_wizard_item_label_style( $item );
		if ( '' === $label_style ) {
			return $field_set;
		}

		foreach ( $field_set as $index => $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$existing_style              = isset( $row['style'] ) && is_string( $row['style'] ) ? trim( $row['style'] ) : '';
			$field_set[ $index ]['style'] = self::combine_inline_styles( $existing_style, $label_style );
		}

		return $field_set;
	}

	/**
	 * Build inline styles for the wizard row label cell.
	 *
	 * @param array<string, mixed> $item Wizard item definition.
	 * @return string
	 */
	protected static function get_wizard_item_label_style( array $item ) {
		if ( empty( $item['color'] ) || ! is_string( $item['color'] ) ) {
			return '';
		}

		$color = self::sanitize_wizard_item_color( $item['color'] );
		if ( '' === $color ) {
			return '';
		}

		return 'border-left-color:' . $color . ';border-left-width:8px;';
	}

	/**
	 * Validate an RGB hex color for wizard item styling.
	 *
	 * @param string $color Candidate color string.
	 * @return string
	 */
	protected static function sanitize_wizard_item_color( $color ) {
		$color = trim( $color );
		if ( ! preg_match( '/^#[0-9a-fA-F]{6}$/', $color ) ) {
			return '';
		}

		return strtoupper( $color );
	}

	/**
	 * Merge inline style declarations into a single style string.
	 *
	 * @param string $existing Existing inline style.
	 * @param string $append   Style declarations to append.
	 * @return string
	 */
	protected static function combine_inline_styles( $existing, $append ) {
		$existing = trim( $existing );
		$append   = trim( $append );

		if ( '' === $existing ) {
			return $append;
		}

		if ( '' === $append ) {
			return $existing;
		}

		return rtrim( $existing, ';' ) . ';' . ltrim( $append, ';' );
	}

	/**
	 * Field keys that are explicitly shown in wizard steps.
	 *
	 * @return string[]
	 */
	protected static function get_wizard_field_keys() {
		$keys = array();

		foreach ( self::$wizard_fields as $step ) {
			if ( empty( $step['items'] ) || ! is_array( $step['items'] ) ) {
				continue;
			}

			foreach ( $step['items'] as $item ) {
				if ( ! empty( $item['key'] ) && is_string( $item['key'] ) ) {
					$keys[] = $item['key'];
				}
			}
		}

		return array_values( array_unique( $keys ) );
	}

	/**
	 * Preserve non-wizard product fields during wizard edit submissions.
	 *
	 * @param \WP_Post|null $edit_product Product being edited.
	 * @param bool          $repopulate   Whether submitted values should win.
	 * @return string
	 */
	protected static function get_hidden_non_wizard_fields_html( $edit_product, $repopulate ) {
		if ( ! $edit_product instanceof WP_Post ) {
			return '';
		}

		$wizard_field_lookup = array_fill_keys( self::get_wizard_field_keys(), true );
		$html                = '';

		foreach ( self::$fields as $section_fields ) {
			if ( ! is_array( $section_fields ) ) {
				continue;
			}

			foreach ( $section_fields as $field_key => $field_def ) {
				if ( ! is_array( $field_def ) || isset( $wizard_field_lookup[ $field_key ] ) ) {
					continue;
				}

				$type = isset( $field_def['type'] ) ? (string) $field_def['type'] : 'text';
				if ( 'image' === $type ) {
					continue;
				}

				$name  = self::get_product_field_post_name( $field_key, $field_def );
				$value = self::get_product_field_raw_value( $field_key, $field_def, $type, $edit_product, $repopulate );

				if ( is_bool( $value ) ) {
					$value = $value ? '1' : '';
				} elseif ( ! is_scalar( $value ) ) {
					$value = '';
				}

				$html .= '<input type="hidden" name="' . esc_attr( $name ) . '" value="' . esc_attr( (string) $value ) . '" />';
			}
		}

		return $html;
	}

	/**
	 * Resolve the active wizard step from POST or GET.
	 *
	 * @return string
	 */
	protected static function get_requested_wizard_step_key( array $wizard_steps = array() ) {
		$step_keys = array_keys( ! empty( $wizard_steps ) ? $wizard_steps : self::$wizard_fields );
		if ( empty( $step_keys ) ) {
			return '';
		}

		$requested_step_key = '';

		$posted_step_key = filter_input( INPUT_POST, 'ddb_active_wizard_step', FILTER_DEFAULT );
		if ( is_string( $posted_step_key ) && '' !== $posted_step_key ) {
			$requested_step_key = sanitize_key( wp_unslash( $posted_step_key ) );
		}

		if ( '' === $requested_step_key ) {
			$query_step_key = filter_input( INPUT_GET, 'ddb_wizard_step', FILTER_DEFAULT );
			if ( is_string( $query_step_key ) && '' !== $query_step_key ) {
				$requested_step_key = sanitize_key( wp_unslash( $query_step_key ) );
			}
		}

		if ( in_array( $requested_step_key, $step_keys, true ) ) {
			return $requested_step_key;
		}

		return (string) reset( $step_keys );
	}

	/**
	 * Render one taxonomy row inside the wizard table.
	 *
	 * @param string        $taxonomy_key Taxonomy field key.
	 * @param array<string, mixed> $item  Wizard item definition.
	 * @param \WP_Post|null $edit_product Product being edited.
	 * @param bool          $repopulate   Whether to prefer $_POST values.
	 * @return string
	 */
	protected static function get_wizard_taxonomy_row_html( $taxonomy_key, array $item, $edit_product, $repopulate ) {
		if ( empty( self::$taxonomy_fields[ $taxonomy_key ] ) || ! is_array( self::$taxonomy_fields[ $taxonomy_key ] ) ) {
			return '';
		}

		$taxonomy_field = self::$taxonomy_fields[ $taxonomy_key ];
		$taxonomy       = isset( $taxonomy_field['taxonomy'] ) ? sanitize_key( (string) $taxonomy_field['taxonomy'] ) : '';
		if ( '' === $taxonomy || ! taxonomy_exists( $taxonomy ) ) {
			return '';
		}

		$options         = self::get_product_taxonomy_options( $taxonomy );
		$selected        = self::get_selected_taxonomy_term_ids( $taxonomy_key, $taxonomy, $edit_product, $repopulate );
		$selected_lookup = array_fill_keys( $selected, true );
		$post_name       = self::get_taxonomy_field_post_name( $taxonomy_key ) . '[]';
		$list_id         = 'ddb-wizard-tax-' . preg_replace( '/[^a-z0-9_-]/i', '', (string) $taxonomy_key );
		$label_plain     = isset( $taxonomy_field['label'] ) ? __( (string) $taxonomy_field['label'], DDB_TEXT_DOMAIN ) : '';
		$label_html      = self::field_label_with_required_suffix(
			array(
				'label'    => $label_plain,
				'required' => ! empty( $taxonomy_field['required'] ),
			)
		);
		$description     = ! empty( $item['description'] ) && is_string( $item['description'] )
			? __( $item['description'], DDB_TEXT_DOMAIN )
			: '';
		$label_style     = self::get_wizard_item_label_style( $item );

		ob_start();
		?>
		<tr>
			<td class="col-name"<?php echo '' !== $label_style ? ' style="' . esc_attr( $label_style ) . '"' : ''; ?>><span><?php echo $label_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span></td>
			<td class="col-input">
				<ul class="ddb-taxonomy-checkbox-list" role="group" aria-label="<?php echo esc_attr( $label_plain ); ?>">
					<?php foreach ( $options as $term ) : ?>
						<?php
						if ( ! $term instanceof WP_Term ) {
							continue;
						}
						$term_id = (int) $term->term_id;
						$input_id = $list_id . '-term-' . $term_id;
						?>
						<li class="ddb-taxonomy-checkbox-list__item">
							<label class="ddb-taxonomy-checkbox-list__label" for="<?php echo esc_attr( $input_id ); ?>">
								<input
									type="checkbox"
									name="<?php echo esc_attr( $post_name ); ?>"
									id="<?php echo esc_attr( $input_id ); ?>"
									value="<?php echo esc_attr( (string) $term_id ); ?>"
									<?php checked( isset( $selected_lookup[ $term_id ] ) ); ?>
								/>
								<span class="ddb-taxonomy-checkbox-list__text"><?php echo esc_html( $term->name ); ?></span>
							</label>
						</li>
					<?php endforeach; ?>
				</ul>
			</td>
			<td class="col-info"><?php echo wp_kses_post( $description ); ?></td>
		</tr>
		<?php

		return (string) ob_get_clean();
	}

	/**
	 * Render an informational row inside the wizard table.
	 *
	 * @param array<string, mixed> $item Wizard item definition.
	 * @return string
	 */
	protected static function get_wizard_message_row_html( array $item ) {
		$message = ! empty( $item['message'] ) && is_string( $item['message'] ) ? $item['message'] : '';
		$note    = ! empty( $item['note'] ) && is_string( $item['note'] ) ? $item['note'] : '';
		$label_style = self::get_wizard_item_label_style( $item );

		ob_start();
		?>
		<tr>
			<td class="col-name"<?php echo '' !== $label_style ? ' style="' . esc_attr( $label_style ) . '"' : ''; ?>><?php esc_html_e( 'What to do next', DDB_TEXT_DOMAIN ); ?></td>
			<td class="col-input"><p class="wizard-message"><?php echo esc_html( $message ); ?></p></td>
			<td class="col-info"><?php echo esc_html( $note ); ?></td>
		</tr>
		<?php

		return (string) ob_get_clean();
	}
}
