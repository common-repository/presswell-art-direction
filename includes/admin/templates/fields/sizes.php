<tr class="pwad-sizes">
  <td>

    <div id="pwadvue">
      <fieldset>
        <label class="screen-reader-text" for="pwad_sizes"><?php echo __( 'Sizes', $this->plugin->slug ); ?></label>
        <div class="pwad-size-table">
          <div class="pwad-size-table-header">
            <!-- <strong class="pwad-size-table-col pwad-size-table-col-enable">&nbsp;<?php // echo __( 'Enabled', $this->plugin->slug ); ?></strong> -->
            <strong class="pwad-size-table-col pwad-size-table-col-name"><?php echo __( 'Name', $this->plugin->slug ); ?></strong>
            <strong class="pwad-size-table-col pwad-size-table-col-key"><?php echo __( 'Key', $this->plugin->slug ); ?></strong>
            <strong class="pwad-size-table-col pwad-size-table-col-dim"><?php echo __( 'Width', $this->plugin->slug ); ?></strong>
            <strong class="pwad-size-table-col pwad-size-table-col-dim"><?php echo __( 'Height', $this->plugin->slug ); ?></strong>
            <strong class="pwad-size-table-col pwad-size-table-col-crop"><?php echo __( 'Crop', $this->plugin->slug ); ?></strong>
          </div>
          <div class="pwad-size-table-row">

            <!-- Start Size Set -->
            <div v-for="size, sindex in sizes" :class="[{ 'pwad-editable': size.editable }, { 'pwad-editing': size.editing }, 'pwad-size-table-row']">
              <div class="pwad-size-table-header">
                <!-- <div class="pwad-size-table-col pwad-size-table-col-enable">

                  <label :for="attr([sindex, 'enabled'])">
                    <span class="screen-reader-text"><?php echo __( 'Enabled', $this->plugin->slug ); ?></span>
                    <input type="checkbox" :name="attr([sindex, 'enabled'])" :id="attr([sindex, 'enabled'])" :ref="attr([sindex, 'enabled'])" v-model="size.enabled">
                    <span></span>
                  </label>

                </div> -->
                <div class="pwad-size-table-col pwad-size-table-col-name">

                  <label :for="attr([sindex, 'name'])" class="screen-reader-text"><?php echo __( 'Name', $this->plugin->slug ); ?></label>
                  <input type="text" :name="attr([sindex, 'name'])" :id="attr([sindex, 'name'])" :ref="attr([sindex, 'name'])" v-model="size.name" required="required" :readonly="readOnly(size)" @blur="nameBlur(size, $event)">

                </div>
                <div class="pwad-size-table-col pwad-size-table-col-key">

                  <label :for="attr([sindex, 'key'])" class="screen-reader-text"><?php echo __( 'Key', $this->plugin->slug ); ?></label>
                  <input type="text" :name="attr([sindex, 'key'])" :id="attr([sindex, 'key'])" :ref="attr([sindex, 'key'])" v-model="size.key" required="required" :readonly="readOnly(size)">

                </div>
                <div class="pwad-size-table-col pwad-size-table-col-dim">

                  <label :for="attr([sindex, 'width'])" class="screen-reader-text"><?php echo __( 'Width', $this->plugin->slug ); ?></label>
                  <input type="number" :name="attr([sindex, 'width'])" :id="attr([sindex, 'width'])" :ref="attr([sindex, 'width'])" v-model="size.width" min="0" required="required" :readonly="readOnly(size)" @blur="sizeDimBlur(size, $event)">

                </div>
                <div class="pwad-size-table-col pwad-size-table-col-dim">

                  <label :for="attr([sindex, 'height'])" class="screen-reader-text"><?php echo __( 'Height', $this->plugin->slug ); ?></label>
                  <input type="number" :name="attr([sindex, 'height'])" :id="attr([sindex, 'height'])" :ref="attr([sindex, 'height'])" v-model="size.height" min="0" required="required" :readonly="readOnly(size)" @blur="sizeDimBlur(size, $event)">

                </div>
                <div class="pwad-size-table-col pwad-size-table-col-crop">

                  <label :for="attr([sindex, 'crop'])" class="screen-reader-text"><?php echo __( 'Crop', $this->plugin->slug ); ?></label>
                  <input type="checkbox" :name="attr([sindex, 'crop'])" :id="attr([sindex, 'crop'])" :ref="attr([sindex, 'crop'])" v-model="size.crop" :readonly="readOnly(size)">

                </div>
                <div class="pwad-size-table-col pwad-size-table-actions">

                  <span v-if="size.editable">
                    <button type="button" class="pwad-size-thumb button" @click.prevent.stop="addThumbnail(size)">
                      <span class="screen-reader-text"><?php echo __( 'Add', $this->plugin->slug ); ?></span>
                      <span class="dashicons dashicons-format-gallery"></span>
                    </button>
                    <button v-if="size.editing" type="button" class="pwad-size-save button button-primary" @click.prevent.stop="toggleSize(size)">
                      <?php echo __( 'Save', $this->plugin->slug ); ?>
                    </button>
                    <button v-else type="button" class="pwad-size-edit button" @click.prevent.stop="toggleSize(size)">
                      <span class="screen-reader-text"><?php echo __( 'Edit', $this->plugin->slug ); ?></span>
                      <span class="dashicons dashicons-admin-generic"></span>
                    </button>
                    <button type="button" class="pwad-size-remove button" @click.prevent.stop="removeSize(size)">
                      <span class="screen-reader-text"><?php echo __( 'Delete', $this->plugin->slug ); ?></span>
                      <span class="dashicons dashicons-trash"></span>
                    </button>
                  </span>
                  <span v-else class="pwad-size-table-token">
                    {{ size.type }}
                  </span>

                </div>
              </div>

              <!-- Start Thumb Items -->
              <div class="pwad-size-table-rows">

                <!-- Start Thumb Item -->
                <div v-for="thumb, tindex in size.thumbnails" :class="[{ 'pwad-editing': thumb.editing }, 'pwad-size-table-row', 'pwad-size-table-thumb']">
                  <div class="pwad-size-table-header">
                    <!-- <div class="pwad-size-table-col pwad-size-table-col-enable">

                      <label :for="attr([sindex, tindex, 'enabled'])">
                        <span class="screen-reader-text"><?php echo __( 'Enabled', $this->plugin->slug ); ?></span>
                        <input type="checkbox" :name="attr([sindex, tindex, 'enabled'])" :id="attr([sindex, tindex, 'enabled'])" :ref="attr([sindex, tindex, 'enabled'])" v-model="thumb.enabled">
                        <span></span>
                      </label>

                    </div> -->
                    <div class="pwad-size-table-col pwad-size-table-col-name">

                      <label :for="attr([sindex, tindex, 'name'])" class="screen-reader-text"><?php echo __( 'Name', $this->plugin->slug ); ?></label>
                      <input type="text" :name="attr([sindex, tindex, 'name'])" :id="attr([sindex, tindex, 'name'])" :ref="attr([sindex, tindex, 'name'])" v-model="thumb.name" required="required" :readonly="readOnly(thumb)" @blur="nameBlur(thumb, $event)">

                    </div>
                    <div class="pwad-size-table-col pwad-size-table-col-key">

                      <label :for="attr([sindex, tindex, 'key'])" class="screen-reader-text"><?php echo __( 'Key', $this->plugin->slug ); ?></label>
                      <span>{{ size.key }}-</span><input type="text" :name="attr([sindex, tindex, 'key'])" :id="attr([sindex, tindex, 'key'])" :ref="attr([sindex, tindex, 'key'])" v-model="thumb.key" required="required" :readonly="readOnly(thumb)">

                    </div>
                    <div class="pwad-size-table-col pwad-size-table-col-dim">

                      <label :for="attr([sindex, tindex, 'width'])" class="screen-reader-text"><?php echo __( 'Width', $this->plugin->slug ); ?></label>
                      <input type="number" :name="attr([sindex, tindex, 'width'])" :id="attr([sindex, tindex, 'width'])" :ref="attr([sindex, tindex, 'width'])" v-model="thumb.width" min="0" required="required" :readonly="readOnly(thumb)" @blur="thumbDimBlur(size, thumb, attr([sindex, 'width']), $event)">

                    </div>
                    <div class="pwad-size-table-col pwad-size-table-col-dim">

                      <label :for="attr([sindex, tindex, 'height'])" class="screen-reader-text"><?php echo __( 'Height', $this->plugin->slug ); ?></label>
                      <input type="number" :name="attr([sindex, tindex, 'height'])" :id="attr([sindex, tindex, 'height'])" :ref="attr([sindex, tindex, 'height'])" v-model="thumb.height" min="0" required="required" :readonly="readOnly(thumb)" @blur="thumbDimBlur(size, thumb, attr([sindex, 'height']), $event)">

                    </div>
                    <div class="pwad-size-table-col pwad-size-table-col-crop">

                      <label :for="attr([sindex, tindex, 'crop'])" class="screen-reader-text"><?php echo __( 'Crop', $this->plugin->slug ); ?></label>
                      <input type="checkbox" :id="attr([sindex, tindex, 'crop'])" :ref="attr([sindex, tindex, 'crop'])" v-model="size.crop" readonly>

                    </div>
                    <div class="pwad-size-table-col pwad-size-table-actions">

                      <span v-if="size.editable">
                        <button v-if="thumb.editing" type="button" class="pwad-size-save button button-primary" @click.prevent.stop="toggleThumbnail(thumb)">
                          <?php echo __( 'Save', $this->plugin->slug ); ?>
                        </button>
                        <button v-else type="button" class="pwad-size-edit button" @click.prevent.stop="toggleThumbnail(thumb)">
                          <span class="screen-reader-text"><?php echo __( 'Edit', $this->plugin->slug ); ?></span>
                          <span class="dashicons dashicons-admin-generic"></span>
                        </button>
                        <button type="button" class="pwad-size-remove button" @click.prevent.stop="removeThumbnail(size, thumb)">
                          <span class="screen-reader-text"><?php echo __( 'Delete', $this->plugin->slug ); ?></span>
                          <span class="dashicons dashicons-trash"></span>
                        </button>
                      </span>
                      <span v-else class="pwad-size-table-token">
                        {{ size.type }}
                      </span>

                    </div>
                  </div>
                </div>
                <!-- End Thumb Item -->

              </div>
              <!-- End Thumb Items -->

            </div>
            <!-- End Size Set -->

          </div>
          <div class="pwad-size-table-footer">
            <button type="button" class="button" @click.prevent.stop="addSize">+ <?php echo __( 'Add Size', $this->plugin->slug ); ?></button>
          </div>
        </div>
      </fieldset>

      <input type="hidden" name="_pwad_sizes" ref="post" :value="JSON.stringify(sizes)">

      <!-- <pre>{{ sizes }}</pre> -->

    </div>

  </td>
</tr>
