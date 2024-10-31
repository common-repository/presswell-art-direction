<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

class Presswell_Art_Direction_Settings {

  protected static $instance;

  public $file = __FILE__;
  public $plugin;

  public static function get_instance() {
    if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Presswell_Art_Direction_Settings ) ) {
      self::$instance = new Presswell_Art_Direction_Settings();
    }
    return self::$instance;
  }

  public function __construct() {
    $this->plugin = Presswell_Art_Direction::get_instance();

    if ( ! session_id() ) {
      session_start();
    }

    add_action( $this->plugin->key . '_resources', array( $this, 'admin_enqueue_resources' ) );

    add_filter( 'plugin_action_links', array( $this, 'plugin_action_links' ), 10, 2 );

    add_action( 'admin_menu', array( $this, 'admin_menu' ) );

    add_action( 'admin_post_' . $this->plugin->key . '_update_settings', array( $this, 'admin_post_update' ) );

    add_action( 'admin_post_' . $this->plugin->key . '_clear_cache', array( $this, 'admin_clear_cache' ) );

    add_action( 'admin_post_' . $this->plugin->key . '_import_sizes', array( $this, 'admin_import_sizes' ) );

    add_action( 'admin_post_' . $this->plugin->key . '_export_sizes', array( $this, 'admin_export_sizes' ) );

    add_action( 'admin_post_' . $this->plugin->key . '_migrate_data', array( $this, 'admin_migrate_data' ) );

    add_action( 'admin_notices', array( $this, 'admin_notices' ) );

    add_action( 'shutdown', array( $this, 'shutdown' ) );
  }

  public function shutdown() {
    if ( session_status() == PHP_SESSION_ACTIVE ) {
      session_write_close();
    }
  }

  //

  public function admin_enqueue_resources() {
    $screen = get_current_screen();

    if ( $screen->id == 'settings_page_presswell-art-direction-settings' ) {
      wp_enqueue_script( $this->plugin->slug . '-admin-js-settings', $this->plugin->get_url() . 'assets/js/admin-settings.js', array( 'jquery', 'underscore' ), $this->plugin->version, true );

      wp_localize_script( $this->plugin->slug . '-admin-js-settings', 'PWAD_SETTINGS_DATA', $this->localize_settings() );
    }
  }

  public function localize_settings() {
    $settings = $this->plugin->settings;

    $internal_sizes = array();
    $external_sizes = array();

    // All sizes registered via code
    foreach ( $this->plugin->plugin_sizes as $size ) {
      if ( empty( $internal_sizes[ $size['key'] ] ) ) {
        $size['thumbnails'] = array_values( $size['thumbnails'] );
        $size['editable'] = false;

        foreach ( $size['thumbnails'] as &$thumb ) {
          $thumb['key'] = $thumb['suffix'];
        }

        $internal_sizes[ $size['key'] ] = $size;
      }
    }

    // All sizes registered via settings
    foreach ( $this->plugin->settings_sizes as $size ) {
      if ( empty( $internal_sizes[ $size['key'] ] ) ) {
        $size['thumbnails'] = array_values( $size['thumbnails'] );
        $size['editable'] = true;

        foreach ( $size['thumbnails'] as &$thumb ) {
          $thumb['key'] = $thumb['suffix'];
        }

        $internal_sizes[ $size['key'] ] = $size;
      }
    }

    // All sizes
    $external = $this->plugin->get_all_sizes();
    foreach ( $external as $size_key => $size_data ) {
      if ( empty( $internal_sizes[ $size_key ] ) && empty( $size_data['parent'] ) ) {
        $size_data['name'] = $size_key;
        $size_data['key'] = $size_key;
        $size_data['thumbnails'] = array();
        $size_data['editable'] = false;

        $external_sizes[ $size_key ] = $size_data;
      }
    }

    $settings['sizes'] = array_merge( $external_sizes, $internal_sizes );

    return $settings;
  }

  //

  public function plugin_action_links( $links, $file ) {
    if ( strpos( $file, $this->plugin->slug ) !== false ) {
      $link = '<a href="' . admin_url( 'options-general.php?page=' . $this->plugin->slug . '-settings' ) . '">' . __( 'Settings', $this->plugin->slug ) . '</a>';
      array_unshift( $links, $link );
    }

    return $links;
  }

  public function admin_menu() {
    add_submenu_page(
      'options-general.php',
      $this->plugin->name,
      'Art Direction',
      'manage_options',
      $this->plugin->slug . '-settings',
      array( $this, 'draw_menu_page' )
    );

    do_action( $this->plugin->key . '_settings_menu' );
  }

  public function draw_menu_page() {
    $settings = $this->plugin->settings;

    include $this->plugin->get_path() . 'includes/admin/templates/settings.php';
  }

  public function draw_fields() {
    do_action( $this->plugin->key . '_settings_before_fields' );

    $this->draw_field( 'sizes' );

    do_action( $this->plugin->key . '_settings_after_fields' );
  }

  public function draw_field( $type ) {
    include $this->plugin->get_path() . 'includes/admin/templates/fields/' . $type . '.php';
  }

  public function admin_post_update() {
    if ( ! wp_verify_nonce( $_POST['_wpnonce'], $this->plugin->key . '_update_settings' ) ) {
      wp_die( 'Nonce verification failed' );
    }

    $post = $_POST;

    if ( ! empty( $post['_pwad_sizes'] ) ) {
      $settings = array(
        'sizes' => json_decode( stripslashes( $post['_pwad_sizes'] ), true ),
      );

      $settings = $this->sanitize_settings( $settings );

      $settings['smart_cache'] = ( ! empty( $post['_pwad_smart_cache'] ) ) ? 'on' : 'off';

      $this->plugin->update_settings( $settings );

      $_SESSION[ $this->plugin->key . '-settings-updated' ] = true;
    }

    wp_redirect( admin_url( 'options-general.php?page=' . $this->plugin->slug . '-settings' ), 303 );
  }

  public function sanitize_settings( $settings ) {
    $settings['sizes'] = $this->sanitize_sizes( $settings['sizes'] );

    $settings = apply_filters( $this->plugin->key . '_filter_settings', $settings );

    return $settings;
  }

  public function sanitize_sizes( $sizes = array(), $import = false ) {
    $size_defaults = array(
      'name' => '',
      'key' => '',
      'width' => 0,
      'height' => 0,
      'crop' => false,
    );

    $clean_sizes = array();

    if ( is_array( $sizes ) && ! empty( $sizes ) ) {
      foreach ( $sizes as $size ) {
        if ( ! $import && ! $size['editable'] ) {
          continue;
        }

        unset( $size['editable'] );
        unset( $size['editing'] );
        unset( $size['type'] );

        $size = wp_parse_args( array_filter( $size ), $size_defaults );

        $thumbnails = array();

        if ( is_array( $size['thumbnails'] ) && ! empty( $size['thumbnails'] ) ) {
          foreach ( $size['thumbnails'] as $thumb ) {
            $thumb = wp_parse_args( array_filter( $thumb ), $size_defaults );

            unset( $thumb['editing'] );
            unset( $thumb['type'] );
            unset( $thumb['crop'] );

            // Determine missing width / height
            if ( ! empty( $size['crop'] ) ) {
              if ( empty( $thumb['width'] ) ) {
                $ratio = $size['width'] / $size['height'];
                $thumb['width'] = round( $thumb['height'] * $ratio );
              }
              if ( empty( $thumb['height'] ) ) {
                $ratio = $size['height'] / $size['width'];
                $thumb['height'] = round( $thumb['width'] * $ratio );
              }
            }

            $thumbnails[] = $thumb;
          }
        }

        $size['thumbnails'] = $thumbnails;

        $clean_sizes[] = $size;
      }
    }

    return $clean_sizes;
  }

  //

  public function admin_clear_cache() {
    if ( ! wp_verify_nonce( $_POST['_wpnonce'], $this->plugin->key . '_clear_cache' ) ) {
      wp_die( 'Nonce verification failed' );
    }

    $post = $_POST;
    $include_default = false;

    if ( ! empty( $post['pwad_clear_default_sizes'] ) && $post['pwad_clear_default_sizes'] == 'on' ) {
      $include_default = true;
    }

    $this->plugin->delete_full_cache( $include_default );

    $_SESSION[ $this->plugin->key . '-clear-cache' ] = true;

    wp_redirect( admin_url( 'options-general.php?page=' . $this->plugin->slug . '-settings' ), 303 );
  }

  //

  public function admin_import_sizes() {
    if ( ! wp_verify_nonce( $_POST['_wpnonce'], $this->plugin->key . '_import_sizes' ) ) {
      wp_die( 'Nonce verification failed' );
    }

    $error = true;

    $post = $_POST;
    $files = $_FILES;

    if ( ! empty( $files['pwad_import_sizes_file'] ) ) {
      $file = $files['pwad_import_sizes_file'];

      if ( $file['type'] == 'application/json' ) {
        $json = json_decode( file_get_contents( $file['tmp_name'] ), true );

        $sizes = $this->sanitize_sizes( $json, true );

        $settings = $this->plugin->settings;
        // $settings['sizes'] = array_merge( $settings['sizes'], $sizes );

        foreach ( $sizes as $size ) {
          $size_index = array_search( $size['key'], array_column( $settings['sizes'], 'key' ) );

          if ( $size_index !== false ) {
            $settings['sizes'][ $size_index ] = $size;
          } else {
            $settings['sizes'][] = $size;
          }
        }

        $this->plugin->update_settings( $settings );

        $_SESSION[ $this->plugin->key . '-import-complete' ] = true;

        $error = false;
      }
    }

    if ( $error ) {
      $_SESSION[ $this->plugin->key . '-import-error' ] = true;
    }

    wp_redirect( admin_url( 'options-general.php?page=' . $this->plugin->slug . '-settings' ), 303 );
    // die();
  }

  //

  public function admin_export_sizes() {
    if ( ! wp_verify_nonce( $_POST['_wpnonce'], $this->plugin->key . '_export_sizes' ) ) {
      wp_die( 'Nonce verification failed' );
    }

    $post = $_POST;

    $format = $post['pwad_export_sizes_format'];

    if ( $format == 'json' ) {
      header( 'Content-Description: File Transfer' );
      header( 'Content-Type: application/octet-stream' );
      header( 'Content-Disposition: attachment; filename="pwad-image-sizes.json"');

      echo json_encode( $this->plugin->settings['sizes'] );
    }

    if ( $format == 'php' ) {
      $str_replace = array(
        "  " => "\t",
        "array (" => "array("
      );
      $preg_replace = array(
        '/([\t\r\n]+?)array/'  => 'array',
        '/[0-9]+ => array/' => 'array'
      );

      header( 'Content-Description: File Transfer' );
      header( 'Content-Type: application/octet-stream' );
      header( 'Content-Disposition: attachment; filename="pwad-image-sizes.php"');

      echo "<?php";
      echo "\r\n\r\n";
      echo "if ( function_exists( 'pwad_add_image_size' ) ):";
      echo "\r\n\r\n";

      foreach ( $this->plugin->settings['sizes'] as $size ) {
        $code = var_export( $size, true );
        $code = str_replace( array_keys( $str_replace ), array_values( $str_replace ), $code );
        $code = preg_replace( array_keys( $preg_replace ), array_values( $preg_replace ), $code );
        // $code = esc_textarea( $code );

        echo "pwad_add_image_size( " . $code . " );";
        echo "\r\n\r\n";
      }

      echo "endif;";
      echo "\r\n\r\n";
      echo "?>";
    }

    die();
  }

  //

  public function admin_migrate_data() {
    if ( ! wp_verify_nonce( $_POST['_wpnonce'], $this->plugin->key . '_migrate_data' ) ) {
      wp_die( 'Nonce verification failed' );
    }

    $post = $_POST;
    // $include_default = false;
    //
    // if ( ! empty( $post['pwad_clear_default_sizes'] ) && $post['pwad_clear_default_sizes'] == 'on' ) {
    //   $include_default = true;
    // }

    $delete_old_meta = ( ! empty( $post['pwad_delete_old_meta'] ) );

    global $wpdb;

    // My Eye Are Up Here
    $query = 'SELECT * FROM ' . $wpdb->postmeta . ' WHERE meta_key = "hotspots"';
    $results = $wpdb->get_results( $query, ARRAY_A );

    $hotspots = array();
    foreach ( $results as $result ) {
      $hotspots = unserialize( $result['meta_value'] );
      // $faces = get_post_meta( $result['post_id'], 'faces', true );
      // if ( ! empty( $faces ) ) {
      //   $hotspots = array_merge( $hotspots, $faces );
      // }

      $value = array(
        'left' => 0,
        'top' => 0,
      );
      foreach ( $hotspots as $hotspot ) {
        $value['left'] += $hotspot['x'] + ( $hotspot['width'] / 2 );
        $value['top'] += $hotspot['y'] + ( $hotspot['width'] / 2 );
      }

      $image_data = $this->plugin->get_clean_metadata( $result['post_id'] );

      $value['left'] = ( $value['left'] / count( $hotspots ) ) / $image_data['width'];
      $value['top'] = ( $value['top'] / count( $hotspots ) ) / $image_data['height'];

      $items[] = array(
        'post' => $result['post_id'],
        'value' => $value,
      );
    }

    foreach ( $items as $item ) {
      $settings = $item['value'];

      update_post_meta( $item['post'], '_pwad_focal_point', $settings );

      if ( $delete_old_meta ) {
        delete_post_meta( $item['post'], 'hotspots' );
        delete_post_meta( $item['post'], 'faces' );
      }

      $this->plugin->delete_cache( $item['post'], true ); // include default
    }

    // Image Focus
    $query = 'SELECT * FROM ' . $wpdb->postmeta . ' WHERE meta_key = "focus_point"';
    $results = $wpdb->get_results( $query, ARRAY_A );

    $items = array();
    foreach ( $results as $result ) {
      $items[] = array(
        'post' => $result['post_id'],
        'value' => unserialize( $result['meta_value'] ),
      );
    }

    foreach ( $items as $item ) {
      $settings = array(
        'left' => $item['value']['x'] / 100,
        'top' => $item['value']['y'] / 100,
      );

      update_post_meta( $item['post'], '_pwad_focal_point', $settings );

      if ( $delete_old_meta ) {
        delete_post_meta( $item['post'], 'focus_point' );
      }

      $this->plugin->delete_cache( $item['post'], true ); // include default
    }

    $_SESSION[ $this->plugin->key . '-migrate-data' ] = true;

    wp_redirect( admin_url( 'options-general.php?page=' . $this->plugin->slug . '-settings' ), 303 );
  }

  //

  public function admin_notices() {
    if ( isset( $_SESSION[ $this->plugin->key . '-settings-updated' ] ) ) {
      unset( $_SESSION[ $this->plugin->key . '-settings-updated' ] );
      ?>
      <div class="notice updated is-dismissible">
        <p><strong>Settings saved.</strong></p>
        <button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>
      </div>
      <?php
    }

    if ( isset( $_SESSION[ $this->plugin->key . '-settings-error' ] ) ) {
      unset( $_SESSION[ $this->plugin->key . '-settings-error' ] );
      ?>
      <div class="notice error is-dismissible">
        <p><strong>Error saving settings.</strong></p>
        <button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>
      </div>
      <?php
    }

    if ( isset( $_SESSION[ $this->plugin->key . '-clear-cache' ] ) ) {
      unset( $_SESSION[ $this->plugin->key . '-clear-cache' ] );
      ?>
      <div class="notice updated is-dismissible">
        <p><strong>Image cache cleared.</strong></p>
        <button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>
      </div>
      <?php
    }

    if ( isset( $_SESSION[ $this->plugin->key . '-import-complete' ] ) ) {
      unset( $_SESSION[ $this->plugin->key . '-import-complete' ] );
      ?>
      <div class="notice updated is-dismissible">
        <p><strong>Sizes imported.</strong></p>
        <button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>
      </div>
      <?php
    }

    if ( isset( $_SESSION[ $this->plugin->key . '-import-error' ] ) ) {
      unset( $_SESSION[ $this->plugin->key . '-import-error' ] );
      ?>
      <div class="notice error is-dismissible">
        <p><strong>Error importing sizes.</strong></p>
        <button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>
      </div>
      <?php
    }

    if ( isset( $_SESSION[ $this->plugin->key . '-migrate-data' ] ) ) {
      unset( $_SESSION[ $this->plugin->key . '-migrate-data' ] );
      ?>
      <div class="notice updated is-dismissible">
        <p><strong>Focal point hotspot data migrated.</strong></p>
        <button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>
      </div>
      <?php
    }
  }

}

Presswell_Art_Direction_Settings::get_instance();
