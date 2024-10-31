=== Presswell Art Direction ===
Contributors: presswell, benplum
Tags: image, images, thumbnail, picture, crop, cropping, media library, custom sizes, resize, dynamic, regenerate, focal point, hotspot
Requires at least: 4.0
Tested up to: 6.4.2
Stable tag: trunk
License: GNU General Public License v2.0 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Control how custom image thumbnail sizes are defined, cropped, and generated.

== Description ==

Presswell Art Direction helps simplify how custom image thumbnail sizes are defined, cropped, and generated.

**Features**

* Control image cropping with hot-spots
* Select custom image sizes in the WordPress editor
* Dynamically generate image thumbnails
* Delete all cached thumbnails

***Image Cropping***

Presswell Art Direction adds the ability to set a focal point hot-spot for all images in the media library for fine grain cropping control. Simply identify where the subject of the photo is and the plugin will crop all custom image thumbnails to ensure it stays in frame.

***Thumbnail Sizes***

Presswell Art Direction adds an easy to use interface for defining and editing custom image thumbnail sizes. Custom image sizes will be available for selection when inserting images in the post editor.

***Dynamic Images***

Presswell Art Direction prevents custom image sizes from being generated automatically. Only standard WordPress thumbnails will be generated when a new image is uploaded. All other image sizes will be dynamically generated when called via code or requested via URL, saving server space when many custom image sizes are defined. Note: URL based image generation requires that [pretty permalinks](https://codex.wordpress.org/Using_Permalinks) are enabled.

= Documentation =

**pwad_add_image_size( $args )**

* **$args** (array) (required) - Keyed array containing `name`, `key`, `height`, `width`, and optionally `thumbnails` values; Thumbnail keys are prefixed with parent's key like '[size]-[thumbnail]'

Example:

`
pwad_add_image_size( array(
  'name' => 'Square',
  'key' => 'square',
  'width' => '1200',
  'height' => '1200',
  'thumbnails' => array(
    array(
      'name' => 'Medium',
      'key' => 'medium',
      'width' => '800',
      'height' => '800',
    ),
    array(
      'name' => 'Small',
      'key' => 'small',
      'width' => '400',
      'height' => '400',
    ),
  ),
) );
`

**pwad_get_image( $attachment_ID, $size_key, $thumbnail_key )**

  * **$attachment_ID** (int) (required) - ID of image attachment
  * **$size_key** (string) (required) - Image size identifier; Overload using '[size]-[thumbnail]' pattern
  * **$thumbnail_key** (string) (required) - Image size thumbnail identifier

Returns an image tag.

Example:

`
$square_large = pwad_get_image( $img_ID, 'square' );
$square_medium = pwad_get_image( $img_ID, 'square', 'medium' );
$square_small = pwad_get_image( $img_ID, 'square-small' ); // Overloaded
`

**pwad_get_image_src( $attachment_ID, $size_key, $thumbnail_key )**

  * **$attachment_ID** (int) (required) - ID of image attachment
  * **$size_key** (string) (required) - Image size identifier; Overload using '[size]-[thumbnail]' pattern
  * **$thumbnail_key** (string) (required) - Image size thumbnail identifier

Returns a keyed array containing the `file`, `url`, `path`, `height`, `width`, and `mime-type` values.

Example:

`
$square_large = pwad_get_image_src( $img_ID, 'square' );
$square_medium = pwad_get_image_src( $img_ID, 'square', 'medium' );
$square_small = pwad_get_image_src( $img_ID, 'square-small' ); // Overloaded
`

== Installation ==

Install using the WordPress plugin installer, or manually as [outlined in the Codex](https://codex.wordpress.org/Managing_Plugins).

**Configuration**

Once activated, navigate to *Settings* -> *Art Direction* to configure custom image thumbnail sizes.

== Frequently Asked Questions ==

= How do I set a focal point? =

When viewing an image in the media library click the 'Set Focal Point' button. Use the size dropdown to change the crop preview. Click and drag the blue focal point marker to identify the subject of the image. When finished, click the 'Save Focal Point' button to update the focal point settings and clear the image thumbnail cache for regeneration.

= Why should I set a focal point? =

Focal point hot-spots are an easy way to control the WordPress image crops because they do not requiring setting custom crop dimensions for every thumbnail size.

= Why should I use dynamic image thumbnails? =

Themes may define many custom image thumbnail sizes, but not all image thumbnails will be displayed on the site. Dynamic image generation ensures only the image thumbnails that are actually used are created.

= Why would I delete all cached images? =

When migrating a large site it is beneficial to delete any generated image sizes to speed up the data transfer. Images will be re-generated dynamically when requested from the new location.

== Screenshots ==

1. Focal point hot-spot editing and thumbnail crop preview
2. Plugin settings screen
3. Custom image thumbnail sizes in media modal

== Changelog ==

= 1.1.8 =
* Adusting multisite rewerite rules.

= 1.1.7 =
* Adding multisite support.

= 1.1.6 =
* Fixing duplicate path on original image.

= 1.1.5 =
* Fixing erroneous array key.

= 1.1.4 =
* Fixing empty array.

= 1.1.3 =
* Fixing errors and warnings.

= 1.1.2 =
* Fixing issue with thumbnail sizes that match the original upload's dimensions.

= 1.1.1 =
* Reading real image sizes.

= 1.1.0 =
* New image regeneration method.

= 1.0.9 =
* Fixing issue with erroneous crops.

= 1.0.8 =
* Fixing empty image data array.

= 1.0.7 =
* Fixing issue with character encoding in filenames.

= 1.0.6 =
* Adding ability to disable smart caching.

= 1.0.5 =
* Fixing permalink protocol mismatch.

= 1.0.4 =
* Fixing media modal issue.
* Adding Elementor support.

= 1.0.3 =
* Fixing updater issue.

= 1.0.0 =
* First public release.
