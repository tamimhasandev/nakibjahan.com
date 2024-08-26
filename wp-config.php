<?php
define( 'WP_CACHE', true ); // Added by WP Rocket

// BEGIN iThemes Security - Do not modify or remove this line
// iThemes Security Config Details: 2
define( 'DISALLOW_FILE_EDIT', false ); // Disable File Editor - Security > Settings > WordPress Tweaks > File Editor
// END iThemes Security - Do not modify or remove this line
define( 'ITSEC_ENCRYPTION_KEY', 'OlRmPXB9WDFBVUdsP2xyPWg9TWpjMFpUPH12cjphLS4xPzVrNipUMVtkb3d9KVdOZSp0R1QsXmFbbShkL240Og==' );
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://wordpress.org/documentation/article/editing-wp-config-php/
 *
 * @package WordPress
 */
// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'nakiyjif_main' );
/** Database username */
define( 'DB_USER', 'nakiyjif_main' );
/** Database password */
define( 'DB_PASSWORD', 'nnF?Tq!nRID1' );
/** Database hostname */
define( 'DB_HOST', 'localhost' );
/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );
/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );
/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'vyzy53ikmh5ludcv4xsxf2gypt98kgqgtsty1eyzafcw6kdhhnxghdmgofyjp61v' );
define( 'SECURE_AUTH_KEY',  '11tu6x20ay3wqyqxhlsedfmk8eydgk2k0zek5pgjkqf6s53zswfwg7pdxmacsgiu' );
define( 'LOGGED_IN_KEY',    'yojzej3kgizbie7oxlv5idiim6muyxbh64mzelnaz0pxpcoum98ovv4yjdjrigvs' );
define( 'NONCE_KEY',        'iddzpjvrzmzhbnyghsm1vvyu1b2wa3aioxocjqwiincvoqe1glrb3gw04z3tkopb' );
define( 'AUTH_SALT',        '0llsqreuaj4knzei2eosmlzf3is49t5c5jqbrx0nhwqjjzzi2u42q9qixhucxnu6' );
define( 'SECURE_AUTH_SALT', 'vgimwhphyqxqmohio9kjemsixxvftusg1zkzzrp4ph2s9plraqqoh2sfl8by3whg' );
define( 'LOGGED_IN_SALT',   'hltu5xq8pbgubpsndgm8mov5butk4iw5z1gbttgciuwkdzoqbcud8cf7wzbe2jf9' );
define( 'NONCE_SALT',       'nwjkbv1evr88yrhcqkbn0jk1kvnlnftuxfextzpep0sauaz1sbbrkt3arszqwgzn' );
/**#@-*/
/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wpce_';
/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://wordpress.org/documentation/article/debugging-in-wordpress/
 */
define( 'WP_DEBUG', false );
/* Add any custom values between this line and the "stop editing" line. */
/* That's all, stop editing! Happy publishing. */
/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}
/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';