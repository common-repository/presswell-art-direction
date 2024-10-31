<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

class Presswell_Art_Direction_Attachment {

  protected static $instance;

  public $file = __FILE__;
  public $plugin;

  public static function get_instance() {
    if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Presswell_Art_Direction_Attachment ) ) {
      self::$instance = new Presswell_Art_Direction_Attachment();
    }
    return self::$instance;
  }

  public function __construct() {
    $this->plugin = Presswell_Art_Direction::get_instance();

    add_filter( 'image_size_names_choose', array( $this, 'image_size_names_choose' ) );

    add_action( 'media_row_actions', array( $this, 'media_row_actions' ), 999, 2 );

    add_filter( 'attachment_fields_to_edit', array( $this, 'attachment_fields_to_edit' ), 999, 2 );

    add_action( 'wp_ajax_pwad_get_focal_point', array( $this, 'ajax_get_focal_point' ) );

    add_action( 'wp_ajax_pwad_set_focal_point', array( $this, 'ajax_set_focal_point' ) );

    add_action( 'wp_ajax_pwad_clear_image_cache', array( $this, 'ajax_clear_image_cache' ) );
  }

  //

  public function image_size_names_choose( $sizes ) {
    $all_sizes = $this->plugin->get_all_sizes();

    foreach ( $all_sizes as $size_key => $size_data ) {
      if ( empty( $sizes[ $size_key ] ) ) {
        $size_name = ( ! empty( $size_data['name'] ) ) ? $size_data['name'] : $size_key;

        if ( ! empty( $size_data['parent'] ) ) {
          $parent = $all_sizes[ $size_data['parent'] ];

          $size_name = $parent['name'] . ' ' . $size_name;
        }

        $sizes[ $size_key ] = $size_name;
      }
    }

    return $sizes;
  }

  public function media_row_actions( $links, $post ) {
    if ( strpos( $post->post_mime_type, 'image' ) !== false ) {
      $links['pwad_cache'] = $this->get_cache_link( $post->ID );
    }

    return $links;
  }

  public function get_cache_link( $post_id, $class = '' ) {
    $link = '';

    if ( $this->plugin->settings['smart_cache'] == 'on' ) {
      $link = '<a href="#" class="pwad-clear-cache ' . $class . '" data-id="' . $post_id . '">' . __( 'Clear Image Cache', $this->plugin->slug ) . '</a>';
    } else {
      // $link = '<a href="#" class="pwad-regenerate-cache ' . $class . '" data-id="' . $post_id . '">' . __( 'Regenerate Image Cache', $this->plugin->slug ) . '</a>';
    }

    return $link;
  }

  public function attachment_fields_to_edit( $fields, $post ) {
    $fields['pwad_id'] = array(
      'input' => 'hidden',
      'value' => $post->ID,
      'label' => __( 'ID' ),
      'helps' => __( 'Attachment ID' ),
    );

    return $fields;
  }

  //

  public function ajax_get_focal_point() {
    if ( empty( $_GET['image'] ) ) {
      echo 'Error';
      wp_die();
    }

    $image_ID = $_GET['image'];
    $focal_point = $this->plugin->get_image_focal_point( $image_ID );
    $all_sizes = $this->plugin->get_all_sizes( $image_ID );

    $sizes = array();
    foreach ( $all_sizes as $size_key => $size_data ) {
      if ( ! empty( $size_data['crop'] ) && empty( $size_data['parent'] ) ) {
        $sizes[ $size_key ] = $size_data;
      }
    }

    echo json_encode( array(
      'id' => $image_ID,
      'focalpoint' => $focal_point,
      'sizes' => $sizes,
      'smart_cache' => ( $this->plugin->settings['smart_cache'] == 'on' ),
    ) );
    wp_die();
  }

  public function ajax_set_focal_point() {
    if ( empty( $_GET['image'] ) || empty( $_GET['top'] ) || empty( $_GET['left'] ) ) {
      echo 'Error';
      wp_die();
    }

    $image_ID = $_GET['image'];
    $focal_point = array(
      'top' => $_GET['top'],
      'left' => $_GET['left'],
    );

    $this->plugin->set_image_focal_point( $image_ID, $focal_point );
    $this->plugin->delete_cache( $image_ID, true ); // include default

    echo 'Success';
    wp_die();
  }

  //

  public function ajax_clear_image_cache() {
    if ( empty( $_GET['image'] ) ) {
      return 'Error';
    }

    $image_ID = $_GET['image'];

    $this->plugin->delete_cache( $image_ID ); // include default

    echo 'Success';
    wp_die();
  }
}

Presswell_Art_Direction_Attachment::get_instance();
