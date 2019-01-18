<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://codex.wordpress.org/Editing_wp-config.php
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'wordpress');

/** MySQL database username */
define('DB_USER', 'root');

/** MySQL database password */
define('DB_PASSWORD', 'password');

/** MySQL hostname */
define('DB_HOST', '127.0.0.1');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8mb4');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         'r4SVD0iKyayu`ldUf/.g?0N)=$y-+}/hHq{|=_jiupXj)xW/cox+w`!g8A@)PK;O');
define('SECURE_AUTH_KEY',  '/HgNF1k|9/B_2!8RQD/^{#}In$kTBt5<o;C-IlvDr}9n:Q(.wHp:P5tK0wI_<!rm');
define('LOGGED_IN_KEY',    '`CV>,m yJ:5>Nl4*Je$.r!(0Hc`_Bi#( Rza%ByguG&ZY$-Efhp$.}i-F->`+/6v');
define('NONCE_KEY',        'fy*~qpr+3(tyEnPd%Ch/+)Z.s>gALwJ]T^dw VK>:HD#y5=GvB=~9zHLm]7RV~%x');
define('AUTH_SALT',        'EiSc /YFF<VIv^7n6skZMs`@z|C,Vi*@DNet]%[6VFQa7Z<7KiF`W0yK7o`,Nb;:');
define('SECURE_AUTH_SALT', '&O,dg]x?jlyvX,Dsh31j|T+ecA]Xp>seAt6{5,k!LS=0rTdrv/OS;IATN~!HWZ`p');
define('LOGGED_IN_SALT',   'f#Xr2C2PZi+M!u0S|O,$Q}dGt`qR3,7c*M!nI+:9S%f*tIY8_{&DL+76TjZC]:+i');
define('NONCE_SALT',       'n;/7OVHP-u-hN::N:s`V+*pS8iMa5k}DPgD#gN(No,h-yMe5D*u5p6qUI})fVG55');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the Codex.
 *
 * @link https://codex.wordpress.org/Debugging_in_WordPress
 */
define('WP_DEBUG', false);

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
