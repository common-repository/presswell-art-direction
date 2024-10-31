<?php
/**
* Plugin Name: Presswell Art Direction
* Description: Control how image thumbnail sizes are defined, cropped, and generated.
* Author: Presswell
* Version: 1.1.8
* Plugin URI: http://wordpress.org/plugins/
* Author URI: http://presswell.co
* License: GPLv2 or later
* License URI: http://www.gnu.org/licenses/gpl-2.0.html
*
* @package Presswell Art Direction
* @author Presswell
*
* Presswell Art Direction is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 2 of the License, or
* any later version.
*
* Presswell Art Direction is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Presswell Art Direction. If not, see <http://www.gnu.org/licenses/>.
*/

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

class Presswell_Art_Direction {

  protected static $instance;

  public $version = '1.1.8';
  public $name = 'Presswell Art Direction';
  public $slug = 'presswell-art-direction';
  public $key = 'pwad';
  public $file = __FILE__;
  public $mode = 'basic';
  public $settings;

  public $grouped_sizes = array();
  public $plugin_sizes = array();
  public $settings_sizes = array();

  public static function get_instance() {
    if ( empty( self::$instance ) && ! ( self::$instance instanceof Presswell_Art_Direction ) ) {
      self::$instance = new Presswell_Art_Direction();
    }

    return self::$instance;
  }

  public function __construct() {
    do_action( $this->key . '_pre_init' );

    add_action( 'init', array( $this, 'init' ) );

    add_action( 'plugins_loaded', array( $this, 'load_plugin_textdomain' ) );

    add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_resources' ) );
    add_action( 'elementor/editor/after_enqueue_scripts', array( $this, 'admin_enqueue_resources' ) );

    add_action( 'activated_plugin', array( $this, 'activated_plugin' ), 10, 2 );

    add_action( 'deactivated_plugin', array( $this, 'deactivated_plugin' ), 10, 2 );

    add_action( 'admin_notices', array( $this, 'admin_notices' ) );

    //

    add_filter( 'query_vars', array( $this, 'query_vars' ) );

    add_filter( 'pre_handle_404', array( $this, 'pre_handle_404' ), 999, 2 );

    //

    add_filter( 'wp_get_attachment_metadata',  array( $this, 'wp_get_attachment_metadata' ), 999, 3 );

    add_filter( 'intermediate_image_sizes', array( $this, 'intermediate_image_sizes' ), 10, 1 );

    add_filter( 'intermediate_image_sizes_advanced', array( $this, 'intermediate_image_sizes_advanced' ), 10, 1 );

    //

    add_action( 'upgrader_process_complete', array( $this, 'upgrader_process_complete' ), 999, 2 );

    add_action( '_core_updated_successfully', array( $this, 'core_updated_successfully' ), 999 );
  }

  // Setup

  public function load_plugin_textdomain() {
    load_plugin_textdomain( $this->slug );
  }

  public function init() {
    do_action( $this->key . '_init' );

    $this->load_settings();

    $this->require_pro();

    if ( is_admin() ) {
      $this->require_admin();
    }

    $this->require_global();

    $this->add_rewrite_rule();

    do_action( $this->key . '_loaded' );
  }

  public function admin_enqueue_resources() {
    wp_enqueue_media();

    wp_enqueue_style( $this->slug . '-admin-css', $this->get_url() . 'assets/css/admin.css', array(), $this->version );

    wp_enqueue_script( $this->slug . '-admin-js', $this->get_url() . 'assets/js/admin.js', array( 'jquery', 'plupload', 'plupload-handlers' ), $this->version, true );

    wp_localize_script( $this->slug . '-admin-js', 'PWAD_TRANSLATION_DATA', $this->localize_translations() );

    do_action( $this->key . '_resources' );
  }

  public function localize_translations() {
    return array(
      'confirm' => __( 'Are you sure you want to remove this item?', $this->slug ),
      'drag_focalpoint' => __( 'Drag Focal Point', $this->slug ),
      'set_focalpoint' => __( 'Set Focal Point', $this->slug ),
      'save_focalpoint' => __( 'Save Focal Point', $this->slug ),
      'delete_cache' => __( 'Clear Image Cache', $this->slug ),
      'regenerate_cache' => __( 'Regenerate Image Cache', $this->slug ),
    );
  }

  public function get_path() {
    return plugin_dir_path( $this->file );
  }

  public function get_url() {
    return plugin_dir_url( $this->file );
  }

  public function load_settings() {
    if ( ! get_option( $this->key . '_settings', false ) ) {
      $this->set_default_settings();
    }

    $this->settings = get_option( $this->key . '_settings' );

    if ( is_array( $this->settings['sizes'] ) && ! empty( $this->settings['sizes'] ) ) {
      foreach ( $this->settings['sizes'] as $size ) {
        $this->add_image_size( $size, true );
      }
    }

    if ( ! get_option('permalink_structure') ) {
      $this->settings['smart_cache'] = 'off';
    } else if ( empty( $this->settings['smart_cache'] ) ) {
      $this->settings['smart_cache'] = 'on';
    }
  }

  public function set_default_settings() {
    $settings = array(
      'sizes' => array(),
    );

    $this->update_settings( $settings );
  }

  public function update_settings( $settings = array() ) {
    update_option( $this->key . '_settings', $settings );
  }

  public function require_pro() {
    $path = $this->get_path();
    $file = $path . 'includes/pro/pro.php';

    if ( file_exists( $file ) ) {
      require $file;
    }
  }

  public function require_admin() {
    $path = $this->get_path();

    require $path . 'includes/admin/attachment.php';
    require $path . 'includes/admin/settings.php';
  }

  public function require_global() {
    $path = $this->get_path();

    require $path . 'includes/regenerator.php';
  }

  // Functions

  public function add_image_size( $size, $settings = false ) {
    if ( ! empty( $this->grouped_sizes[ $size['key'] ] ) ) {
      return;
    }

    $size_defaults = array(
      'name' => '',
      'key' => '',
      'width' => 0,
      'height' => 0,
      'crop' => false,
      'type' => '',
    );
    $size = wp_parse_args( array_filter( $size ), $size_defaults );

    if ( empty( $size['key'] ) && ( empty( $size['width'] ) || empty( $size['height'] ) ) ) {
      return false;
    }
    if ( empty( $size['name'] ) ) {
      $size['name'] = $size['key'];
    }

    if ( empty( $this->grouped_sizes[ $size['key'] ] ) ) {
      // add_image_size( $size['key'], $size['width'], $size['height'], $size['crop'] );

      $size['width'] = intval( $size['width'] );
      $size['height'] = intval( $size['height'] );
      $size['type'] = 'Plugin / Theme';

      $thumbnails = array();

      if ( ! empty( $size['thumbnails'] ) ) {
        foreach ( $size['thumbnails'] as $thumb ) {
          $thumb = wp_parse_args( array_filter( $thumb ), $size_defaults );

          if ( empty( $thumb['key'] ) || ( empty( $thumb['width'] ) && empty( $thumb['height'] ) ) ) {
            continue;
          }
          if ( empty( $thumb['name'] ) ) {
            $thumb['name'] = $thumb['key'];
          }

          $thumb['width'] = intval( $thumb['width'] );
          $thumb['height'] = intval( $thumb['height'] );
          $thumb['suffix'] = $thumb['key'];
          $thumb['key'] = $size['key'] . '-' . $thumb['key'];
          $thumb['crop'] = $size['crop'];
          $thumb['type'] = 'Plugin / Theme';

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

          // add_image_size( $thumb['key'], $thumb['width'], $thumb['height'], $size['crop'] );
        }
      }

      $size['thumbnails'] = $thumbnails;

      $this->grouped_sizes[ $size['key'] ] = $size;

      if ( $settings ) {
        $this->settings_sizes[ $size['key'] ] = $size;
      } else {
        $this->plugin_sizes[ $size['key'] ] = $size;
      }
    }
  }

  // Size Helpers

  public function intermediate_image_sizes( $sizes ) {
    return array_keys( $this->intermediate_image_sizes_advanced( $sizes ) );
  }

  public function intermediate_image_sizes_advanced( $sizes ) {
    if ( $this->settings['smart_cache'] == 'on' ) {
      $sizes = array_intersect_key( $sizes, $this->get_default_sizes() );
    } else {
      $sizes = $this->get_all_sizes();
    }

    return $sizes;
  }

  public function get_default_sizes() {
    $sizes = array(
      'thumbnail'    => __( 'Thumbnail' ),
      'medium'       => __( 'Medium' ),
      'medium_large' => __( 'Medium Large' ),
      'large'        => __( 'Large' ),
      'full'         => __( 'Full Size' ),
    );

    return $sizes;
  }

  public function get_all_sizes() {
    global $_wp_additional_image_sizes;

    $image_sizes = array();
    $default_sizes = array(
      'thumbnail',
      'medium',
      'medium_large',
      'large',
    );

    $default_size_names = $this->get_default_sizes();

    // Add default sizes
    foreach ( $default_sizes as $size_key ) {
      $image_sizes[ $size_key ] = array(
        'name' => $default_size_names[ $size_key ],
        'key' => $size_key,
        'width' => intval( get_option( $size_key . '_size_w' ) ),
        'height' => intval( get_option( $size_key . '_size_h' ) ),
        'crop' => ( $size_key == 'thumbnail' ) ? get_option( $size_key . '_crop' ) : false,
        'type' => 'Default',
      );
    }

    // Next add plugin / theme sizes
    if ( ! empty( $_wp_additional_image_sizes ) ) {
      foreach ( $_wp_additional_image_sizes as $size_key => $size ) {
        $size['name'] = $size_key;
        $size['key'] = $size_key;
        $size['type'] = 'Plugin / Theme';

        $image_sizes[ $size_key ] = $size;
      }
    }

    // Finally add our sizes
    foreach ( $this->grouped_sizes as $size_group ) {
      $image_sizes[ $size_group['key'] ] = $size_group;

      if ( ! empty( $size_group['thumbnails'] ) ) {
        foreach ( $size_group['thumbnails'] as $thumb ) {
          $thumb['parent'] = $size_group['key'];

          $image_sizes[ $thumb['key'] ] = $thumb;
        }
      }
    }

    return $image_sizes;
  }

  public function validate_image_size( $image_data, $size_data ) {
    if ( $size_data['width'] == $image_data['width'] && $size_data['height'] == $image_data['height'] ) {
      return false;
    } else if ( empty( $size_data['crop'] ) ) {
      // scaling
      if (
        ( empty( $size_data['height'] ) && $size_data['width'] <= $image_data['width'] )
        ||
        ( empty( $size_data['width'] ) && $size_data['height'] <= $image_data['height'] )
        ||
        (
          ! empty( $size_data['width'] ) &&
          ! empty( $size_data['height'] ) &&
          $size_data['width'] <= $image_data['width'] &&
          $size_data['height'] <= $image_data['height']
        )
      ) {
        return true;
      }
    } else {
      // cropping
      if ( $size_data['width'] <= $image_data['width'] && $size_data['height'] <= $image_data['height'] ) {
        return true;
      }
    }

    return false;
  }

  public function get_available_sizes( $image_ID ) {
    $image_data = $this->get_clean_metadata( $image_ID );
    $all_sizes = $this->get_all_sizes();

    $sizes = array();

    foreach ( $all_sizes as $size_key => $size_data ) {
      if ( $this->validate_image_size( $image_data, $size_data ) ) {
        $sizes[ $size_key ] = $size_data;
      }
    }

    return $sizes;
  }

  public function get_size_data( $size_key ) {
    $all_sizes = $this->get_all_sizes();

    if ( ! empty( $all_sizes[ $size_key ] ) ) {
      return $all_sizes[ $size_key ];
    }

    return false;
  }

  public function get_size_key( $image_ID, $width, $height ) {
    $image_data = $this->get_clean_metadata( $image_ID );
    $all_sizes = $this->get_available_sizes( $image_ID );

    foreach ( $all_sizes as $key => $size ) {
      if ( $size['crop'] == 1 && $width == $size['width'] && $height == $size['height'] ) {
        return $key;
      } else {
        $file_info = $this->generate_file_info( $image_data, $size );

        if ( $width == $file_info['width'] && $height == $file_info['height'] ) {
          return $key;
        }
      }
    }

    return false;
  }

  // Attachment Helpers

  public function get_attachment( $image_path ) {
    $query = new WP_Query( array(
      'post_type' => 'attachment',
      'post_status' => 'any',
      'meta_query' => array(
        array(
          'key' => '_wp_attached_file',
          'value' => $image_path,
        ),
      ),
    ) );

    if ( empty( $query->posts ) ) {
      return false;
    }

    $attachment = $query->posts[0];

    if ( empty( wp_get_attachment_metadata( $attachment->ID ) ) ) {
      return false;
    }

    return $attachment;
  }

  // File System Helpers

  public function prefix_file_path( $file_name ) {
    $upload_dir = wp_upload_dir();

    if ( strpos( $file_name, $upload_dir['basedir'] ) > -1 ) {
      return $file_name;
    }

    return $upload_dir['basedir'] . '/' . $file_name;
  }

  public function prefix_file_url( $file_name ) {
    $upload_dir = wp_upload_dir();

    if ( strpos( $file_name, $upload_dir['baseurl'] ) > -1 ) {
      return $file_name;
    }

    return $upload_dir['baseurl'] . '/' . $file_name;
  }

  // Image Focal Point

  public function get_image_focal_point( $image_ID ) {
    $focal_point = get_post_meta( $image_ID, '_pwad_focal_point', true );

    return ( ! empty( $focal_point ) ) ? $focal_point : array( 'left' => 0.5, 'top' => 0.5 );
  }

  public function set_image_focal_point( $image_ID, $focal_point = array() ) {
    update_post_meta( $image_ID, '_pwad_focal_point', $focal_point );
  }

  // Image Functions

  public function get_image( $image_ID, $size_key = 'thumbnail', $attr = array() ) {
    if ( $size_key == 'full' ) {
      return wp_get_attachment_image( $image_ID, 'full' );
    }

    $image_data = $this->get_image_src( $image_ID, $size_key );

    if ( empty( $image_data ) ) {
      return '';
    }

    $html = '';
    $hwstring = image_hwstring( $image_data['width'], $image_data['height'] );
    $image = get_post( $image_ID );
    $default_attr = array(
      'src' => $image_data['url'],
      'class' => 'attachment-' . $size_key . ' size-' . $size_key,
      'alt' => trim( strip_tags( get_post_meta( $image_ID, '_wp_attachment_image_alt', true ) ) ),
    );

    if ( empty( $attr['srcset'] ) ) {
      $image_meta = wp_get_attachment_metadata( $image_ID );

      if ( is_array( $image_meta ) ) {
        $size_array = array( absint( $image_data['width'] ), absint( $image_data['height'] ) );
        $srcset = wp_calculate_image_srcset( $size_array, $image_data['url'], $image_meta, $image_ID );
        $sizes = wp_calculate_image_sizes( $size_array, $image_data['url'], $image_meta, $image_ID );

        if ( $srcset && ( $sizes || ! empty( $attr['sizes'] ) ) ) {
          $attr['srcset'] = $srcset;

          if ( empty( $attr['sizes'] ) ) {
            $attr['sizes'] = $sizes;
          }
        }
      }
    }

    $attr = wp_parse_args( $attr, $default_attr );
    $attr = apply_filters( 'wp_get_attachment_image_attributes', $attr, $image, $size_key );
    $attr = array_map( 'esc_attr', $attr );
    $html = rtrim( '<img ' . $hwstring );

    foreach ( $attr as $name => $value ) {
      $html .= ' ' . $name . '="' . $value . '"';
    }
    $html .= '>';

    return $html;
  }

  public function get_image_src( $image_ID, $size_key = 'thumbnail' ) {
    if ( $size_key == 'full' ) {
      return $this->original_image( $image_ID );
    }

    $size_data = $this->get_size_data( $size_key );
    $original_data = $this->get_clean_metadata( $image_ID );

    if ( $size_data['height'] == $original_data['height'] && $size_data['width'] == $original_data['width'] ) {
      return $this->original_image( $image_ID );
    }

    $image_data = image_get_intermediate_size( $image_ID, $size_key ); // --

    if ( empty( $image_data ) ) {
      return $this->original_image( $image_ID );
    }

    $image_path = $this->prefix_file_path( $image_data['path'] );

    if ( ! empty( $image_data ) && file_exists( $image_path ) ) {
    // if ( ! empty( $image_data ) ) {
      $image_data['src'] = $image_data['url'];  // Deprecated: 'src' is not a standard WP key

      return $image_data;
    }

    $image_data = $this->generate_image( $image_ID, $size_key ); // ??

    if ( empty( $image_data ) ) {
      return false;
    }

    $image_meta = wp_get_attachment_metadata( $image_ID ); // --
    $image_url = wp_get_attachment_url( $image_ID ); // --

    $image_data['path'] = path_join( dirname( $image_meta['file'] ), $image_data['file'] );
    $image_data['url'] = path_join( dirname( $image_url ), $image_data['file'] );
    $image_data['src'] = $image_data['url'];  // Deprecated: 'src' is not a standard WP key

    return $image_data;
  }

  public function original_image( $image_ID ) {
    $original = wp_get_attachment_image_src( $image_ID, 'full' );

    $upload_dir = wp_upload_dir();

    $filename = wp_basename( $original[0] );
    $mime = get_post_mime_type( $image_ID );
    $path = str_ireplace( trailingslashit( $upload_dir['baseurl'] ), '', $original[0] );
    $url = $this->prefix_file_url( $path );

    $size_data = array(
      'file'      => $filename,
      'width'     => $original[1],
      'height'    => $original[2],
      'mime-type' => $mime,
      'path'      => $path,
      'url'       => $url,
      'src'       => $url, // Deprecated: 'src' is not a standard WP key
    );

    return $size_data;
  }

  public function generate_image( $image_ID, $size_key ) {
    $image_path = get_attached_file( $image_ID );
    $size_data = $this->get_size_data( $size_key );
    $image_data = $this->get_clean_metadata( $image_ID );
    $file_info = $this->generate_file_info( $image_data, $size_data );
    $image_focal_point = $this->get_image_focal_point( $image_ID );
    $image_editor = wp_get_image_editor( $image_path );

    if ( empty( $size_data ) || empty( $image_data ) || is_wp_error( $image_editor ) ) {
      return false;
    }

    if ( $size_data['height'] == $image_data['height'] && $size_data['width'] == $image_data['width'] ) {
      return false;
    }

    if ( ! $this->validate_image_size( $image_data, $size_data ) ) {
      return false;
    }

    if ( empty( $image_focal_point ) ) {
      $image_editor->resize( $file_info['width'], $file_info['height'], $size_data['crop'] );
    } else {
      $ratio = $image_data['height'] / $image_data['width'];

      $image_width = $file_info['width'];
      $image_height = $file_info['width'] * $ratio;

      if ( $image_height < $file_info['height'] ) {
        $ratio = $image_data['width'] / $image_data['height'];

        $image_height = $file_info['height'];
        $image_width = $file_info['height'] * $ratio;
      }

      $x_ratio = $file_info['width'] / $image_data['width'];
      $y_ratio = $file_info['height'] / $image_data['height'];

      $image_x = ( $image_width * floatval( $image_focal_point['left'] ) ) - ( $image_data['width'] / 2 * $x_ratio );
      $image_y = ( $image_height * floatval( $image_focal_point['top'] ) ) - ( $image_data['height'] / 2 * $y_ratio );

      if ( $image_x + $file_info['width'] > $image_width ) {
        $image_x -= ( $image_x + $file_info['width'] - $image_width );
      }
      if ( $image_x < 0 ) {
        $image_x = 0;
      }

      if ( $image_y + $file_info['height'] > $image_height ) {
        $image_y -= ( $image_y + $file_info['height'] - $image_height );
      }
      if ( $image_y < 0 ) {
        $image_y = 0;
      }
    }

    $final_x = $image_x * ( $image_data['width'] / $image_width );
    $final_y = $image_y * ( $image_data['height'] / $image_height );

    $final_width = $file_info['width'] * ( $image_data['width'] / $image_width );
    $final_height = $file_info['height'] * ( $image_data['height'] / $image_height );

    $image_editor->crop( $final_x, $final_y, $final_width, $final_height, $file_info['width'], $file_info['height'], false );

    $final_size = $image_editor->get_size();
    $suffix = $final_size['width'] . 'x' . $final_size['height'];
    $filename = $image_editor->generate_filename( $suffix );

    $image_editor->save( $filename );
    $final_filename = wp_basename( $filename );
    $final_mime = get_post_mime_type( $image_ID );

    $size_data = array(
      'file'      => $final_filename,
      'width'     => $final_size['width'],
      'height'    => $final_size['height'],
      'mime-type' => $final_mime,
    );

    // $image_data['sizes'][ $size_key ] = $size_data;

    // wp_update_attachment_metadata( $image_ID, $image_data );

    return $size_data;
  }

  // Rewrite Helpers

  public function add_rewrite_rule() {
    $upload_dir = wp_upload_dir();

    $site_url = str_ireplace( 'https://', 'http://', site_url() );
    $upload_url = str_ireplace( 'https://', 'http://', $upload_dir['baseurl'] );
    $rewrite_base = str_ireplace( trailingslashit( $site_url ), '', $upload_url );

    add_rewrite_rule( '^' . $rewrite_base . '/(.*)?', 'index.php?pwad_image=$matches[1]', 'top' );

    if ( is_multisite() ) {
      $site_url = str_ireplace( 'https://', 'http://', network_site_url() );
      $upload_url = str_ireplace( 'https://', 'http://', $upload_dir['baseurl'] );
      $rewrite_base = str_ireplace( trailingslashit( $site_url ), '', $upload_url );

      add_rewrite_rule( '^' . $rewrite_base . '/(.*)?', 'index.php?pwad_image=$matches[1]', 'top' );
    }
  }

  public function query_vars( $vars ) {
    $vars[] = 'pwad_image';

    return $vars;
  }

  public function pre_handle_404( $preempt, $wp_query ) {
    global $wpdb;

    $query_var = get_query_var( 'pwad_image' );

    if ( empty( $query_var ) ) {
      return $preempt;
    }

    $image_filename = urldecode( preg_replace( '/-\\d+[Xx]\\d+\\./u', '.', $query_var ) );
    preg_match( '/-(\\d+[Xx]\\d+)\\./u', $query_var, $image_size_match );

    if ( empty( $image_size_match ) ) {
      return $preempt;
    }

    list( $requested_width, $requested_height ) = explode( 'x', $image_size_match[1] );

    $image = $this->get_attachment( $image_filename );

    if ( empty( $image ) ) {
      return $preempt;
    }

    $size_key = $this->get_size_key( $image->ID, $requested_width, $requested_height );

    if ( empty( $size_key ) ) {
      return $preempt;
    }

    $image_data = $this->get_image_src( $image->ID, $size_key );
    $image_path = $this->prefix_file_path( $image_data['path'] );

    if ( ! empty( $image_data ) && file_exists( $image_path ) ) {
      wp_redirect( $image_data['url'] );
      die();
    } else {
      return $preempt;
    }
  }

  // Image Metadata

  public function wp_get_attachment_metadata( $image_data, $image_ID ) {
    if ( empty( $image_data ) || ! wp_attachment_is_image( $image_ID ) ) {
      return $image_data;
    }

    $all_sizes = $this->get_available_sizes( $image_ID );

    $mime = get_post_mime_type( $image_ID );

    foreach ( $all_sizes as $size_key => $size_data ) {
      if ( $size_data['width'] > $image_data['width'] || $size_data['height'] > $image_data['height'] ) {
        continue;
      }

      $file_info = $this->generate_file_info( $image_data, $size_data );

      $image_data['sizes'][ $size_key ] = array(
        'file' => $file_info['file'],
        'width' => $file_info['width'],
        'height' => $file_info['height'],
        'mime-type' => $mime,
      );
    }

    if ( empty( $image_data['sizes'] ) ) {
      $image_data['sizes'] = [];
    }

    // Discard anything that isn't a registered size
    $image_data['sizes'] = array_intersect_key( $image_data['sizes'], $this->get_all_sizes() );

    return $image_data;
  }

  public function get_clean_metadata( $image_ID ) {
    remove_filter( 'wp_get_attachment_metadata', array( $this, 'wp_get_attachment_metadata' ), 999, 3 );
    $image_meta = wp_get_attachment_metadata( $image_ID );
    add_filter( 'wp_get_attachment_metadata', array( $this, 'wp_get_attachment_metadata' ), 999, 3 );

    $image_path = $this->prefix_file_path( $image_meta['file'] );

    if ( file_exists( $image_path ) /* && mime_content_type( $image_path ) !== 'image/svg+xml' */ ) {
      $image_size = @getimagesize( $image_path );

      if ( ! empty( $image_size ) ) {
        $image_meta['width'] = $image_size[0]; // force real image size?
        $image_meta['height'] = $image_size[1];
      }
    }

    return $image_meta;
  }

  // Image File Name Helper

  public function generate_file_info( $image_data, $size_data ) {
    $width = $size_data['width'];
    $height = $size_data['height'];

    $image_path = $this->prefix_file_path( $image_data['file'] );
    $image_size = getimagesize( $image_path );

    // if ( empty( $size_data['crop'] ) ) {
    if ( ! empty( $image_size ) && empty( $size_data['crop'] ) ) {
      // $ratio_h = $image_data['height'] / $image_data['width'];
      $ratio_h = $image_size[1] / $image_size[0];
      $width_h = $size_data['width'];
      $height_h = round( $width_h * $ratio_h );

      // $ratio_v = $image_data['width'] / $image_data['height'];
      $ratio_v = $image_size[0] / $image_size[1];
      $height_v = $size_data['height'];
      $width_v = round( $height_v * $ratio_v );

      if (
        ( $size_data['width'] == 0 || $width_h <= $size_data['width'] ) &&
        ( $size_data['height'] == 0 || $height_h <= $size_data['height'] )
      ) {
        $width = $width_h;
        $height = $height_h;
      } else if (
        ( $size_data['width'] == 0 || $width_v <= $size_data['width'] ) &&
        ( $size_data['height'] == 0 || $height_v <= $size_data['height'] )
      ) {
        $width = $width_v;
        $height = $height_v;
      }
    }

    $pathinfo = pathinfo( $image_data['file'] );
    $filename = $pathinfo['filename'] . '-' . $width . 'x' . $height . '.' . $pathinfo['extension'];

    return array(
      'file' => $filename,
      'width' => $width,
      'height' => $height,
    );
  }

  // Cache Helpers

  public function get_image_count() {
    $query = new WP_Query( array(
      'post_type' => 'attachment',
      'post_status' => 'any',
      'post_mime_type' => 'image',
      'posts_per_page' => -1,
    ) );

    return count( $query->posts );
  }

  public function delete_cache( $image_ID = false, $include_default = false ) {
    WP_Filesystem();
    global $wp_filesystem;

    $clean_sizes = $this->get_all_sizes();

    if ( ! $include_default ) {
      $clean_sizes = array_diff_key( $clean_sizes, $this->get_default_sizes() );
    }

    $image_data = $this->get_clean_metadata( $image_ID );
    $pathinfo = pathinfo( $image_data['file'] );
    $base_filename = $pathinfo['filename'] . '.' . $pathinfo['extension'];

    foreach ( $clean_sizes as $size_key => $size_data ) {
      $file_info = $this->generate_file_info( $image_data, $size_data );
      $image_path = $this->prefix_file_path( str_ireplace( $base_filename, $file_info['file'], $image_data['file'] ) );

      if ( file_exists( $image_path ) ) {
        $wp_filesystem->delete( $image_path );
      }
    }

    if ( $this->settings['smart_cache'] != 'on' ) {
      $regenerator = Presswell_Art_Direction_Regenerator::get_instance();
      $regenerator->push_queue( $image_ID );

      // foreach ( $clean_sizes as $size_key => $size_data ) {
      //   $this->generate_image( $image_ID, $size_key );
      // }
    }
  }

  public function delete_full_cache( $include_default = false ) {
    $query = new WP_Query( array(
      'post_type' => 'attachment',
      'post_status' => 'any',
      'post_mime_type' => 'image',
      'posts_per_page' => -1,
    ) );

    if ( ! empty( $query->posts ) ) {
      foreach ( $query->posts as $image ) {
        $this->delete_cache( $image->ID, $include_default );
      }
    }
  }

  // Check Plugin Conflicts

  public function activated_plugin( $plugin, $network_wide ) {
    if ( ! function_exists( 'get_plugins' ) ) {
      require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    $all_plugins = get_plugins();
    $active_plugins = get_option('active_plugins');

    $conflict_plugins = array(
      'crop-thumbnails/crop-thumbnails.php',
      'fly-dynamic-image-resizer/fly-dynamic-image-resizer.php',
      'image-focus/image-focus.php',
      'manual-image-crop/manual-image-crop.php',
      'my-eyes-are-up-here/my-eyes-are-up-here.php',
      'optimize-images-resizing/optimize-images-resizing.php',
      'regenerate-thumbnails/regenerate-thumbnails.php',
      'simple-image-sizes/simple_image_sizes.php',
    );

    $found_conflicts = array_intersect( $active_plugins, $conflict_plugins );
    $conflicts = array();

    foreach ( $found_conflicts as $conflict ) {
      $conflicts[ $conflict ] = $all_plugins[ $conflict ];
    }

    update_option( 'pwad_conflicts', $conflicts );
  }

  public function deactivated_plugin( $plugin, $network_wide ) {
    $conflicts = get_option( 'pwad_conflicts' );

    if ( ! empty( $conflicts[ $plugin ] ) ) {
      unset( $conflicts[ $plugin ] );
    }

    update_option( 'pwad_conflicts', $conflicts );
  }

  public function admin_notices() {
    $conflicts = get_option( 'pwad_conflicts' );

    if ( ! empty( $conflicts ) ) {
      ?>
      <div class="notice error">
        <p><strong>The following plugins may conflict with Presswell Art Direction:</strong> <?php echo implode( ', ', array_column( $conflicts, 'Name' ) ); ?></p>
      </div>
      <?php
    }
  }

  // After Update
  public function upgrader_process_complete( $upgrader, $options ) {
    flush_rewrite_rules();
  }

  public function core_updated_successfully() {
    flush_rewrite_rules();
  }

}


// Activation

register_activation_hook( __FILE__, 'presswell_art_direction_activation_hook' );
register_deactivation_hook( __FILE__, 'presswell_art_direction_deactivation_hook' );

function presswell_art_direction_activation_hook( $network_wide ) {
  global $wp_version;

  if ( version_compare( $wp_version, '4.0', '<' ) && ! defined( 'PRESSWELL_ART_DIRECTION_FORCE_ACTIVATION' ) ) {
    deactivate_plugins( plugin_basename( __FILE__ ) );
    wp_die( sprintf( __( 'Sorry, but your version of WordPress does not meet Presswell Art Direction minimum required version of <strong>4.0</strong> to run properly. The plugin has been deactivated. <a href="%s">Click here to return to the Dashboard</a>.', $this->slug ), get_admin_url() ) );
  } else {
    $instance = PressWell_Art_Direction::get_instance();
    $instance->add_rewrite_rule();
    flush_rewrite_rules();
  }
}

function presswell_art_direction_deactivation_hook( $network_wide ) {
  flush_rewrite_rules();
}


// Update

function presswell_art_direction_update_hook( $upgrader, $options ) {
  $instance = PressWell_Art_Direction::get_instance();
  $instance->add_rewrite_rule();
  flush_rewrite_rules();
}
add_action( 'upgrader_process_complete', 'presswell_art_direction_update_hook', 999, 2);


// Instance

PressWell_Art_Direction::get_instance();


// Helpers

function pwad_add_image_size( $args ) {
  $instance = PressWell_Art_Direction::get_instance();

  return $instance->add_image_size( $args );
}

function pwad_get_image( $image_ID, $size_key, $attr = array() ) {
  $instance = PressWell_Art_Direction::get_instance();

  return $instance->get_image( $image_ID, $size_key, $attr );
}

function pwad_get_image_src( $image_ID, $size_key ) {
  $instance = PressWell_Art_Direction::get_instance();

  return $instance->get_image_src( $image_ID, $size_key );
}