<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

class Presswell_Art_Direction_Regenerator {

  protected static $instance;

  public $file = __FILE__;
  public $plugin;
  public $table;
  public $version = '0.0.3';

  public static function get_instance() {
    if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Presswell_Art_Direction_Regenerator ) ) {
      self::$instance = new Presswell_Art_Direction_Regenerator();
    }
    return self::$instance;
  }

  public function __construct() {
    $this->plugin = Presswell_Art_Direction::get_instance();

    //

    $this->check_schema();

    add_action( 'wp_ajax_' . $this->plugin->key . '_regenerate_queue', array( $this, 'build_queue' ) );

    add_action( 'wp_ajax_' . $this->plugin->key . '_regenerate_thumbnails', array( $this, 'regenerate_thumbnails' ) );

    add_action( 'wp_ajax_' . $this->plugin->key . '_compress_thumbnails', array( $this, 'compress_thumbnails' ) );
  }

  //

  public function check_schema() {
    global $wpdb;

    $this->table = $wpdb->prefix . $this->plugin->key . '_image_queue';

    $version = get_option( $this->table . '_db_version' );

    if ( $this->version != $version ) {
      require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

      // Clients
      $sql = "CREATE TABLE " . $this->table . " (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        attachment bigint(20) NOT NULL,
        size varchar(255) NOT NULL,
        file varchar(255) NOT NULL,
        complete varchar(255) NOT NULL,
        PRIMARY KEY (id),
        KEY attachment (attachment),
        KEY complete (complete)
      );";

      dbDelta( $sql );

      update_option( $this->table . '_db_version', $this->version );
    }
  }

  //

  public function build_queue() {
    global $wpdb;

    $page = ( ! empty( $_GET['page'] ) ) ? $_GET['page'] : 1;
    $per_page = 10;

    if ( $page == 1 ) {
      $wpdb->query( "DELETE FROM `" . $this->table . "` WHERE 1" );
    }

    $query = new WP_Query( array(
      'post_type' => 'attachment',
      'post_status' => 'any',
      'post_mime_type' => 'image',
      'posts_per_page' => $per_page,
      'paged' => $page,
      'orderby' => 'ID',
      'order' => 'DESC',
    ) );

    if ( empty( $query->posts ) ) {
      echo 'Complete';
      die();
    }

    foreach ( $query->posts as $image ) {
      $this->push_queue( $image );
    }

    echo 'Page ' . $page . '<br>';
    die();
  }

  public function push_queue( $image = false ) {
    if ( empty( $image ) ) {
      return;
    }

    global $wpdb;

    $image = get_post( $image );
    $image_data = wp_get_attachment_metadata( $image->ID );

    foreach ( $image_data['sizes'] as $size_key => $size ) {
      if ( $size['mime-type'] == 'image/gif' ) {
        continue;
      }

      $path = path_join( dirname( $image_data['file'] ), $size['file'] );

      $key_parts = array(
        "`attachment`",
        "`size`",
        "`file`",
      );

      $value_parts = array(
        "'" . esc_sql( $image->ID ) . "'",
        "'" . esc_sql( $size_key ) . "'",
        "'" . esc_sql( $path ) . "'",
      );

      $check_parts = array();

      for ( $i = 0; $i < count( $key_parts ); $i++ ) {
        $check_parts[] = $key_parts[ $i ] . ' = ' . $value_parts[ $i ];
      }

      $check = $wpdb->get_results( "SELECT * FROM `" . $this->table . "` WHERE " . implode( " AND ", $check_parts ) . " LIMIT 1", ARRAY_A );

      if ( ! empty( $check ) ) {
        $wpdb->query( "DELETE FROM `" . $this->table . "` WHERE " . implode( " AND ", $check_parts ) );
      }

      $result = $wpdb->query( "INSERT INTO `" . $this->table . "` (" . implode( "," , $key_parts ) . ") VALUES (" . implode( ",", $value_parts ) . ")" );
    }
  }

  public function regenerate_thumbnails() {
    global $wpdb;

    $per_page = 5;

    $parts = array(
      "`complete` != 'yes'",
    );

    $image = ( ! empty( $_GET['image'] ) ) ? $_GET['image'] : false;

    if ( ! empty( $image ) ) {
      $parts[] = "`attachment` = '" . esc_sql( $image ) . "'";
    }

    $rows = $wpdb->get_results( "SELECT * FROM `" . $this->table . "` WHERE " . implode( " AND ", $parts ) . " LIMIT " . $per_page, ARRAY_A );

    if ( empty( $rows ) ) {
      echo 'Complete';
      die();
    }

    foreach ( $rows as $row ) {
      $this->plugin->generate_image( $row['attachment'], $row['size'] );

      $parts = array(
        "`attachment` = '" . esc_sql( $row['attachment'] ) . "'",
        "`size` = '" . esc_sql( $row['size'] ) . "'",
      );

      $wpdb->query( "UPDATE `" . $this->table . "` SET `complete` = 'yes' WHERE " . implode( " AND ", $parts ) );

      echo $row['file'] . ' [' . $row['size'] . '] (' . $row['attachment'] . ')<br>';
    }

    die();
  }

  public function compress_thumbnails() {

  }

}

Presswell_Art_Direction_Regenerator::get_instance();
