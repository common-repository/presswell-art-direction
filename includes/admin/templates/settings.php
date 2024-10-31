<div class="wrap pwad-settings">
  <h1><?php echo __( 'Art Direction Settings', $this->plugin->slug ); ?></h1>
  <p><?php echo __( 'Configure custom images sizes below.', $this->plugin->slug ); ?></p>

  <!-- Settings -->
  <form action="<?php echo admin_url( 'admin-post.php' ) ?>" method="POST">
    <input type="hidden" name="action" value="<?php echo $this->plugin->key; ?>_update_settings">
    <?php wp_nonce_field( $this->plugin->key . '_update_settings' ); ?>
    <table class="form-table">
      <?php $this->draw_fields(); ?>
    </table>

    <label for="pwad_enable_smart_cache">
      <input type="checkbox" name="_pwad_smart_cache" id="pwad_enable_smart_cache" value="on" <?php if ( $this->plugin->settings['smart_cache'] == 'on' ) echo 'checked="checked"' ?>>
      <?php echo __( 'Enable smart caching', $this->plugin->slug ); ?>
    </label>

    <?php submit_button(); ?>
  </form>

  <!-- Clear Cache -->
  <?php if ( $this->plugin->settings['smart_cache'] == 'on' ) : ?>
  <h2><?php echo __( 'Delete Cache', $this->plugin->slug ); ?></h2>
  <p><?php echo __( 'Delete cached image files. This can not be undone.', $this->plugin->slug ); ?></p>
  <form action="<?php echo admin_url( 'admin-post.php' ) ?>" method="POST">
    <input type="hidden" name="action" value="<?php echo $this->plugin->key; ?>_clear_cache">
    <?php wp_nonce_field( $this->plugin->key . '_clear_cache' ); ?>
    <label for="pwad_clear_default_sizes">
      <input type="checkbox" name="pwad_clear_default_sizes" id="pwad_clear_default_sizes" value="on">
      <?php echo __( 'Delete standard WordPress sizes', $this->plugin->slug ); ?>
    </label>
    <?php submit_button( 'Clear Cache', 'delete' ); ?>
  </form>
  <?php else : ?>
  <h2><?php echo __( 'Regenerate Thumbnails', $this->plugin->slug ); ?></h2>
  <p><?php echo __( 'Regenerate cached image files.', $this->plugin->slug ); ?></p>
  <form action="" method="POST" class="pwad-regenerate-thumbnails">
    <div class="pwad-regenerate-output">
    </div>
    <?php submit_button( 'Regenerate Cache', 'delete' ); ?>
  </form>
  <?php endif; ?>

  <!-- Import Sizes -->
  <h2><?php echo __( 'Import Sizes', $this->plugin->slug ); ?></h2>
  <p><?php echo __( 'Import custom image sizes.', $this->plugin->slug ); ?></p>
  <form action="<?php echo admin_url( 'admin-post.php' ) ?>" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="action" value="<?php echo $this->plugin->key; ?>_import_sizes">
    <?php wp_nonce_field( $this->plugin->key . '_import_sizes' ); ?>
    <label for="pwad_import_sizes_file"><?php echo __( 'JSON File', $this->plugin->slug ); ?></label>
    <input type="file" name="pwad_import_sizes_file" id="pwad_import_sizes_file">
    <?php submit_button( 'Import Sizes', 'delete' ); ?>
  </form>

  <!-- Export Sizes -->
  <h2><?php echo __( 'Export Sizes', $this->plugin->slug ); ?></h2>
  <p><?php echo __( 'Export custom image sizes as PHP to include in a theme or plugin, or as JSON to migrate between sites.', $this->plugin->slug ); ?></p>
  <form action="<?php echo admin_url( 'admin-post.php' ) ?>" method="POST">
    <input type="hidden" name="action" value="<?php echo $this->plugin->key; ?>_export_sizes">
    <?php wp_nonce_field( $this->plugin->key . '_export_sizes' ); ?>
    <label for="pwad_export_sizes_format"><?php echo __( 'Export Format', $this->plugin->slug ); ?></label>
    <select name="pwad_export_sizes_format" id="pwad_export_sizes_format">
      <option value="php">PHP</option>
      <option value="json">JSON</option>
    </select>
    <?php submit_button( 'Export Sizes', 'delete' ); ?>
  </form>

  <!-- Migrate Data -->
  <h2><?php echo __( 'Migrate Data', $this->plugin->slug ); ?></h2>
  <p><?php echo __( 'Migrate focal point hotspot data from other plugins (My Eyes Are Up Here or Image Focus).', $this->plugin->slug ); ?></p>
  <form action="<?php echo admin_url( 'admin-post.php' ) ?>" method="POST">
    <input type="hidden" name="action" value="<?php echo $this->plugin->key; ?>_migrate_data">
    <?php wp_nonce_field( $this->plugin->key . '_migrate_data' ); ?>
    <label for="pwad_delete_old_meta">
      <input type="checkbox" name="pwad_delete_old_meta" id="pwad_delete_old_meta" value="on">
      <?php echo __( 'Delete old plugin metadata', $this->plugin->slug ); ?>
    </label>
    <?php submit_button( 'Migrate Data', 'delete' ); ?>
  </form>

</div>
