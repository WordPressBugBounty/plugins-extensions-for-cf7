<?php

if( ! defined( 'ABSPATH' ) ) exit(); // Exit if accessed directly

class Extensions_Cf7_Signature{

	/**
     * [$_instance]
     * @var null
    */
    private static $_instance = null;

    /**
     * [instance] Initializes a singleton instance
     * @return [Extensions_Cf7_Signature]
    */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

	function __construct(){
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);

        add_filter('extcf7_post_metabox', [$this, 'metabox_options']);
        add_action( 'wpcf7_save_contact_form', [$this, 'styler_save_data'] );

        add_action('wpcf7_init', [$this, 'wpcf7_tags']);
        add_action('admin_init', [$this, 'wpcf7_tag_generator'], 589);

        add_filter( 'wpcf7_validate_extcf7_signature', [$this, 'validation_filter'],10, 2);
        add_filter( 'wpcf7_validate_extcf7_signature*', [$this, 'validation_filter'],10, 2);
	}
    public function enqueue_scripts() {
        wp_enqueue_script('extcf7_signature', CF7_EXTENTIONS_PL_URL.'assets/js/signature.min.js', ['jquery'], CF7_EXTENTIONS_PL_VERSION, true);
        wp_enqueue_script('extcf7_signature-active', CF7_EXTENTIONS_PL_URL.'assets/js/signature-active.js', ['jquery', 'extcf7_signature'], CF7_EXTENTIONS_PL_VERSION, true);
    }
    public function metabox_options($value) {
        $value['extcf7_signature'] = [
            'id'  => 'signature',
            'label'  => __( 'Signature', 'cf7-extensions' ),
            'fields' => [
                [
                    'id'  => 'signature_bg_color',
                    'name'  => __( 'Signature Pad Background Color', 'cf7-extensions' ),
                    'type'  => 'color',
                    'class' => 'htcf7ext-field-styler width-50 admin-width-50',
                ],
                [
                    'id'  => 'signature_pen_color',
                    'name'  => __( 'Signature Pen Color', 'cf7-extensions' ),
                    'type'  => 'color',
                    'class' => 'htcf7ext-field-styler width-50 admin-width-50',
                ],
                [
                    'id'  => 'signature_height',
                    'name'  => __( 'Signature Pad Height (px)', 'cf7-extensions' ),
                    'type'  => 'number',
                    'class' => 'htcf7ext-field-styler width-50 admin-width-50',
                ],
                [
                    'id'  => 'signature_width',
                    'name'  => __( 'Signature Pad Width (px)', 'cf7-extensions' ),
                    'type'  => 'number',
                    'class' => 'htcf7ext-field-styler width-50 admin-width-50',
                ],
            ],
        ];
        return $value;
    }

    public function styler_save_data($form) {
        if(empty($_POST['extcf7_signature'])) {
            return;
        }
        update_post_meta($form->id, 'extcf7_signature', htcf7extopt_data_clean($_POST['extcf7_signature']));
    }
	public function wpcf7_tags() {
        if (function_exists('wpcf7_add_form_tag')) {
            wpcf7_add_form_tag(
                ['extcf7_signature', 'extcf7_signature*'],
                [$this, 'signature_shortcode'],
                [
                    'name-attr' => true,
                    'file-uploading' => true,
                ]
            );
        } else {
            throw new Exception(esc_html__('functions wpcf7_add_form_tag not found.', 'cf7-extensions'));
        }
    }
    public function signature_shortcode($tag){
        if ( empty( $tag->name ) ) {
            return '';
        }
        $validation_error = wpcf7_get_validation_error( $tag->name );
        $class = wpcf7_form_controls_class( 'extcf7_signature' );
        $atts = [];
        if ( $validation_error ) {
            $class .= ' wpcf7-not-valid';
        }
        if ( $tag->is_required() ) {
            $atts['aria-required'] = 'true';
        }
        $atts['name'] = $tag->name;
        $atts['class'] = $tag->get_class_option( $class );
        $atts['aria-invalid'] = $validation_error ? 'true' : 'false';

        $atts = wpcf7_format_atts( $atts );

        $wpcf7 = WPCF7_ContactForm::get_current();
		$form_id = $wpcf7->id();
        $styleMeta = get_post_meta($form_id, 'extcf7_signature', true);
        $canvas_width = !empty($styleMeta['signature_width']) ? $styleMeta['signature_width'] : 300;
        $canvas_height = !empty($styleMeta['signature_height']) ? $styleMeta['signature_height'] : 120;
        $signature_bg_color = !empty($styleMeta['signature_bg_color']) ? $styleMeta['signature_bg_color'] : '#efefef';
        $signature_pen_color = !empty($styleMeta['signature_pen_color']) ? $styleMeta['signature_pen_color'] : '#000';
        ob_start();
        ?>
        <div class="wpcf7-form-control-wrap extcf7_signature_wrapper <?php echo sanitize_html_class( $tag->name ); ?>">
			<input hidden type="file" class="extcf7_signature_field_input" <?php echo $atts; ?>>
			<div class="extcf7_signature_pad">
				<canvas
                    id="<?php echo sanitize_html_class( $tag->name ); ?>"
                    width="<?php echo $canvas_width; ?>"
                    height="<?php echo $canvas_height; ?>"
                    data-bg-color="<?php echo $signature_bg_color; ?>"
                    data-pen-color="<?php echo $signature_pen_color; ?>"
                ></canvas>
				<div class="extcf7_signature_control">
                    <button type="button" class="extcf7_signature_clear_button"><?php _e( 'Clear Signature', 'cf7-extensions' ); ?></button>
                </div>
			</div>
		</div>
        <?php
        return ob_get_clean();
    }

	public function wpcf7_tag_generator() {
        if (! function_exists( 'wpcf7_add_tag_generator')) { 
            return;
        }
        $callback = htcf7ext_is_tg_v2() ? 'signature_layout' : 'signature_layout_old';
        wpcf7_add_tag_generator(
			'extcf7_signature',
			esc_html__('HT Signature', 'cf7-extensions'),
            'wpcf7-tg-extcf7-signature',
            [$this, $callback],
            ['version' => 2]
        );
    }
    
    public function signature_layout($contact_form, $args = '') {
        $args = wp_parse_args( $args, [] );
        $tgg = new WPCF7_TagGeneratorGenerator( $args['content'] );
        ?>
        <header class="description-box">
            <h3><?php echo esc_html__( 'HT Signature', 'cf7-extensions' ); ?></h3>
            <p><?php echo esc_html__( "Generate a form tag for Signature.", 'cf7-extensions' ); ?></p>
        </header>

        <div class="control-box">
            <fieldset>
                <legend><?php echo esc_html__( 'Field type', 'cf7-extensions' ); ?></legend>
                <input type="hidden" data-tag-part="basetype" value="extcf7_signature" />
                <label><input type="checkbox" name="required" data-tag-part="type-suffix" value="*" /> <?php echo esc_html__( 'Required field', 'cf7-extensions' ); ?></label>
            </fieldset>

            <?php
            $tgg->print( 'field_name' );
            $tgg->print( 'class_attr' );
            ?>
        </div>

        <footer class="insert-box">
            <?php $tgg->print( 'insert_box_content' ); ?>
        </footer>
        <?php
    }
    
    public function signature_layout_old($contact_form, $args = '') {
        $args = wp_parse_args( $args, [] );
        $type = 'extcf7_signature';
        ?>
            <div class="control-box">
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row"><?php echo esc_html__( 'Field type', 'cf7-extensions' ); ?></th>
                            <td>
                                <fieldset>
                                    <legend class="screen-reader-text"><?php echo esc_html__( 'Field type', 'cf7-extensions' ); ?></legend>
                                    <label><input type="checkbox" name="required" /> <?php echo esc_html__( 'Required field', 'cf7-extensions' ); ?></label>
                                </fieldset>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-name' ); ?>"><?php echo esc_html__( 'Name', 'cf7-extensions' ); ?></label></th>
                            <td><input type="text" name="name" class="tg-name" id="<?php echo esc_attr( $args['content'] . '-name' ); ?>" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-class' ); ?>"><?php echo esc_html__( 'Class attribute', 'cf7-extensions' ); ?></label></th>
                            <td><input type="text" name="class" class="classvalue oneline option" id="<?php echo esc_attr( $args['content'] . '-class' ); ?>" /></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="insert-box">
                <input type="text" name="<?php echo esc_attr( $type ); ?>" class="tag code" readonly="readonly" onfocus="this.select()" />
                <div class="submitbox">
                    <input type="button" class="button button-primary insert-tag" value="<?php esc_html_e( 'Insert Tag', 'cf7-extensions' ); ?>" />
                </div>
                <br class="clear" />
            </div>
        <?php
    }

    public function validation_filter($result, $tag){
        $name = $tag->name;
        $value = ( isset( $_POST[ $name ] ) && !empty( $_POST[ $name ] ) ) ? sanitize_text_field($_POST[ $name ]) : null ; //phpcs:ignore WordPress.Security.NonceVerification.Missing
        if( empty( $value ) && $tag->is_required() ) {
            $result->invalidate( $tag, wpcf7_get_message( 'invalid_required' ) );
            return $result;
        }
        return $result;
    }
}

Extensions_Cf7_Signature::instance();