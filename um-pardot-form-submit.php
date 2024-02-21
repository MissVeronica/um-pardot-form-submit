<?php
/**
 * Plugin Name:         Ultimate Member - Pardot Form Submit
 * Description:         Extension to Ultimate Member for integration of Pardot Form Submit.
 * Version:             0.9.1 beta
 * Requires PHP:        7.4
 * Author:              Miss Veronica
 * License:             GPL v3 or later
 * License URI:         https://www.gnu.org/licenses/gpl-2.0.html
 * Author URI:          https://github.com/MissVeronica
 * Text Domain:         ultimate-member
 * Domain Path:         /languages
 * UM version:          2.8.3
 */

 if ( ! defined( 'ABSPATH' ) ) exit;
 if ( ! class_exists( 'UM' ) ) return;

class UM_Pardot_Form_Submit {

    function __construct() {

        add_action( 'um_registration_complete', array( $this, 'um_pardot_form_submit_post' ), 10, 3 );
        add_filter( 'um_settings_structure',    array( $this, 'um_settings_structure_pardot_form_submit' ), 10, 1 );
    }

    public function um_pardot_form_submit_post( $user_id, $args, $form_data ) {

        $url = UM()->options()->get( 'um_pardot_form_submit_URL' );
        $form_ids = array_map( 'trim', array_map( 'sanitize_text_field', explode( ',', UM()->options()->get( 'um_pardot_form_submit_forms' ))));

        if ( is_array( $form_ids ) && in_array( $form_data['form_id'], $form_ids ) && ! empty( $url )) {

            $pardot_fields = array_map( 'trim', array_map( 'sanitize_text_field', explode( ',', UM()->options()->get( 'um_pardot_form_submit_fields' ))));
            
            if ( ! empty( $pardot_fields )) {

                $fields = array();
                foreach ( $pardot_fields as $field_key ) {

                    if ( array_key_exists( $field_key, $args['submitted'] )) {
                        $fields[$field_key] = $args['submitted'][$field_key];

                        if ( is_array( $fields[$field_key] )) {
                            $fields[$field_key] = implode( ',', $fields[$field_key] );
                        }
                    }
                }

                if ( ! empty( $fields )) {

                    $response = wp_remote_post( esc_url( $url ), array( 'body' => $fields ));

                    if ( UM()->options()->get( 'um_pardot_form_submit_log' ) == 1 && ! empty( $response )) {

                        update_user_meta( $user_id, 'um_pardot_form_response', $response, 'user' );
                        UM()->user()->remove_cache( $user_id );
                        um_fetch_user( $user_id );
                    }
                }
            }
        }
	}

    public function um_settings_structure_pardot_form_submit( $settings_structure ) {

        $settings_structure['appearance']['sections']['registration_form']['form_sections']['pardot_form_submit']['title']       = __( 'Pardot Form Submit', 'ultimate-member' );
        $settings_structure['appearance']['sections']['registration_form']['form_sections']['pardot_form_submit']['description'] = __( 'Plugin version 0.9.1 beta - tested with UM 2.8.3', 'ultimate-member' );

        $settings_structure['appearance']['sections']['registration_form']['form_sections']['pardot_form_submit']['fields'][] =

                array(
                    'id'            => 'um_pardot_form_submit_URL',
                    'type'          => 'text',
                    'label'         => __( 'URL', 'ultimate-member' ),
                    'description'   => __( 'Enter the Pardot Form Submit\'s URL.', 'ultimate-member' ),
                    'size'          => 'medium',
                );

        $settings_structure['appearance']['sections']['registration_form']['form_sections']['pardot_form_submit']['fields'][] =

                array(
                    'id'            => 'um_pardot_form_submit_forms',
                    'type'          => 'text',
                    'label'         => __( 'Form IDs', 'ultimate-member' ),
                    'description'   => __( 'Enter the Registration Form IDs comma separated for Pardot Form Submit.', 'ultimate-member' ),
                    'size'          => 'medium',
                );

        $settings_structure['appearance']['sections']['registration_form']['form_sections']['pardot_form_submit']['fields'][] =

                array(
                    'id'            => 'um_pardot_form_submit_fields',
                    'type'          => 'text',
                    'label'         => __( 'Form Fields', 'ultimate-member' ),
                    'description'   => __( 'Enter the Registration Form Fields comma separated for Pardot Form Submit.', 'ultimate-member' ),
                );

        $settings_structure['appearance']['sections']['registration_form']['form_sections']['pardot_form_submit']['fields'][] =

                array(
                    'id'            => 'um_pardot_form_submit_log',
                    'type'          => 'checkbox',
                    'label'         => __( 'Log Response', 'ultimate-member' ),
                    'default'       => 0,
                    'description'   => __( 'Click to log all Pardot Form Submits Responses for the User IDs to the meta_key = um_pardot_form_response', 'ultimate-member' ),
                );

        return $settings_structure;
    }
}

new UM_Pardot_Form_Submit();

