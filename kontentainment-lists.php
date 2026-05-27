<?php
/**
 * Plugin Name: Kontentainment Lists
 * Plugin URI: https://github.com/kollectivco/kontentainment-lists
 * Description: Premium music lists intelligence and editorial publishing platform.
 * Version: 1.7.0
 * Author: Kollectiv
 * Author URI: https://kollectiv.net
 * Update URI: https://github.com/kollectivco/kontentainment-lists
 * Text Domain: kontentainment-lists
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define canonical constants for the rebrand.
define( 'KONTENTAINMENT_LISTS_VERSION', '1.7.0' );
define( 'KONTENTAINMENT_LISTS_PLUGIN_SLUG', 'kontentainment-lists' );
define( 'KONTENTAINMENT_LISTS_PLUGIN_FILE', __FILE__ );
define( 'KONTENTAINMENT_LISTS_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'KONTENTAINMENT_LISTS_LEGACY_PLUGIN_BASENAME', 'kontentainment-charts/charts.php' );
define( 'KONTENTAINMENT_LISTS_PATH', plugin_dir_path( __FILE__ ) );
define( 'KONTENTAINMENT_LISTS_URL', plugin_dir_url( __FILE__ ) );
define( 'KONTENTAINMENT_LISTS_GITHUB_OWNER', 'kollectivco' );
define( 'KONTENTAINMENT_LISTS_GITHUB_REPO', 'kontentainment-lists' );

// Legacy aliases preserved for backward compatibility with the existing codebase.
defined( 'CHARTS_VERSION' ) || define( 'CHARTS_VERSION', KONTENTAINMENT_LISTS_VERSION );
defined( 'CHARTS_PLUGIN_SLUG' ) || define( 'CHARTS_PLUGIN_SLUG', KONTENTAINMENT_LISTS_PLUGIN_SLUG );
defined( 'CHARTS_PLUGIN_FILE' ) || define( 'CHARTS_PLUGIN_FILE', KONTENTAINMENT_LISTS_PLUGIN_FILE );
defined( 'CHARTS_PLUGIN_BASENAME' ) || define( 'CHARTS_PLUGIN_BASENAME', KONTENTAINMENT_LISTS_PLUGIN_BASENAME );
defined( 'CHARTS_PATH' ) || define( 'CHARTS_PATH', KONTENTAINMENT_LISTS_PATH );
defined( 'CHARTS_URL' ) || define( 'CHARTS_URL', KONTENTAINMENT_LISTS_URL );
defined( 'CHARTS_GITHUB_OWNER' ) || define( 'CHARTS_GITHUB_OWNER', KONTENTAINMENT_LISTS_GITHUB_OWNER );
defined( 'CHARTS_GITHUB_REPO' ) || define( 'CHARTS_GITHUB_REPO', KONTENTAINMENT_LISTS_GITHUB_REPO );

/**
 * Autoloader for Lists plugin.
 */
spl_autoload_register( function ( $class ) {
	$prefix   = 'Charts\\';
	$base_dir = CHARTS_PATH . 'inc/';

	$len = strlen( $prefix );
	if ( strncmp( $prefix, $class, $len ) !== 0 ) {
		return;
	}

	$relative_class = substr( $class, $len );
	$file           = str_replace( '\\', '/', $relative_class ) . '.php';
	$file           = $base_dir . $file;

	if ( file_exists( $file ) ) {
		require $file;
	}
} );

/**
 * Main plugin class.
 */
final class Charts {

	/**
	 * Instance of this class.
	 *
	 * @var Charts
	 */
	private static $instance;

	/**
	 * Get the instance of this class.
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize hooks
	 */
	private function init_hooks() {
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

		add_action( 'plugins_loaded', array( $this, 'init' ) );

		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'add_updater_link' ) );
		add_filter( 'plugin_action_links_' . KONTENTAINMENT_LISTS_LEGACY_PLUGIN_BASENAME, array( $this, 'add_updater_link' ) );

		add_action( 'admin_init', array( $this, 'handle_manual_update_check' ) );
		add_action( 'admin_notices', array( $this, 'show_update_notice' ) );
	}

	/**
	 * Show success notice after update check.
	 */
	public function show_update_notice() {
		if ( isset( $_GET['lists_updated_checked'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Lists update cache cleared. Checking GitHub...', 'kontentainment-lists' ) . '</p></div>';
		}
	}

	/**
	 * Handle manual update check action.
	 */
	public function handle_manual_update_check() {
		if ( ! is_admin() || ! isset( $_GET['charts_action'] ) || $_GET['charts_action'] !== 'check_updates' ) {
			return;
		}

		if ( ! current_user_can( 'update_plugins' ) ) {
			return;
		}

		delete_transient( 'lists_github_update_check' );
		delete_transient( 'charts_github_update_check' );
		delete_site_transient( 'update_plugins' );

		$redirect_url = remove_query_arg( array( 'charts_action', '_wpnonce' ), admin_url( 'plugins.php' ) );
		$redirect_url = add_query_arg( 'lists_updated_checked', '1', $redirect_url );

		wp_redirect( $redirect_url );
		exit;
	}

	/**
	 * Add "Check for updates" link to plugins list.
	 */
	public function add_updater_link( $links ) {
		$url = add_query_arg( 'charts_action', 'check_updates', admin_url( 'plugins.php' ) );
		$update_link = '<a href="' . esc_url( $url ) . '" style="font-weight:700;color:#6366f1;">' . esc_html__( 'Check for Update', 'kontentainment-lists' ) . '</a>';
		array_unshift( $links, $update_link );
		return $links;
	}

	/**
	 * Activation hook
	 */
	public function activate() {
		$this->migrate();
		\Charts\Core\Router::add_rewrite_rules();
		flush_rewrite_rules();
	}

	/**
	 * Migration logic
	 */
	public function migrate() {
		$schema = new \Charts\Database\Schema();
		$schema->install();

		$sources = new \Charts\Admin\SourceManager();
		$sources->seed_defaults();

		update_option( 'charts_db_version', CHARTS_VERSION );
		update_option( 'lists_db_version', KONTENTAINMENT_LISTS_VERSION );
	}

	/**
	 * Deactivation hook
	 */
	public function deactivate() {
		flush_rewrite_rules();
	}

	/**
	 * Initialize the plugin
	 */
	public function init() {
		load_plugin_textdomain(
			'kontentainment-lists',
			false,
			dirname( plugin_basename( __FILE__ ) ) . '/languages'
		);

		$db_version = get_option( 'lists_db_version', get_option( 'charts_db_version' ) );
		if ( version_compare( (string) $db_version, KONTENTAINMENT_LISTS_VERSION, '<' ) ) {
			$this->migrate();
			\Charts\Core\Router::add_rewrite_rules();
			flush_rewrite_rules( false );
		}

		\Charts\Core\Bootstrap::init();
		new \Charts\Core\Updater( KONTENTAINMENT_LISTS_PLUGIN_FILE );

		if ( is_admin() ) {
			\Charts\Admin\Bootstrap::init();
		}

		\Charts\Frontend\Bootstrap::init();
		\Charts\Integrations\Elementor\Bootstrap::init();
	}
}

/**
 * Global accessors.
 */
function charts() {
	return Charts::get_instance();
}

function lists() {
	return Charts::get_instance();
}

/**
 * Global translation helper for Egyptian Arabic.
 */
function charts_tr( $text ) {
	static $translations = array(
		// Header/Footer/Nav
		'KONTENTAINMENT LISTS &middot; WEEK OF' => 'قوائم كونتنتمنت &middot; أسبوع',
		'Powered by streaming data from Spotify &middot; YouTube Music &middot; TikTok' => 'بدعم من بيانات البث من Spotify وYouTube Music وTikTok',
		'Home' => 'الرئيسية',
		'Lists' => 'القوائم',
		'Trending' => 'الرائج',
		'Tracks' => 'الأغاني',
		'Artists' => 'الفنانين',
		'Albums' => 'الألبومات',
		'DASHBOARD' => 'لوحة التحكم',
		'The definitive source for weekly music lists, powered by real streaming intelligence data.' => 'المصدر الرئيسي لقوائم الموسيقى الأسبوعية، مدعومًا ببيانات البث الذكية الحقيقية.',
		'Updated weekly &middot; Lists based on multi-platform streaming data' => 'تُحدث أسبوعيًا &middot; قوائم مبنية على بيانات البث عبر منصات متعددة',
		'All rights reserved.' => 'جميع الحقوق محفوظة.',
		'Updated weekly' => 'تُحدث أسبوعيًا',
		'Updated Weekly' => 'تُحدث أسبوعياً',
		'WEEKLY LIST' => 'قائمة أسبوعية',
		'WEEKLY' => 'أسبوعية',
		'MONTHLY' => 'شهرية',
		'DAILY' => 'يومية',
		'weekly' => 'أسبوعية',
		'monthly' => 'شهرية',
		'daily' => 'يومية',
		'Discover' => 'اكتشف',
		'Data Sources' => 'مصادر البيانات',
		'Top Track Lists' => 'قوائم أفضل الأغاني',
		'Top Artist Lists' => 'قوائم أفضل الفنانين',
		'Top Album Lists' => 'قوائم أفضل الألبومات',
		'Hot 100' => 'أفضل ١٠٠ أغنية',
		'Spotify Streaming' => 'بث Spotify',
		'YouTube Music' => 'موسيقى YouTube',
		'TikTok Plays' => 'تشغيلات TikTok',
		'Radio Display' => 'عرض الراديو',
		'Digital Sales' => 'المبيعات الرقمية',
		'Kontentainment' => 'كونتنتمنت',
		'Discovery' => 'اكتشف',
		'Featured List' => 'القائمة المميزة',
		'Hot 100 Artists' => 'أفضل ١٠٠ فنان',
		'Top Artists' => 'أفضل الفنانين',
		'Major List' => 'قائمة رئيسية',
		'by' => 'للفنان',
		'New entries: %s.' => 'المشاركات الجديدة: %s.',
		'featured artists' => 'الفنانين المتميزين',

		// Landing / Index
		'Intelligence Explorer' => 'مستكشف الذكاء الموسيقي',
		'Weekly music lists, artist movement, breakout entries, and editorial intelligence built for discovery, traffic, and social sharing.' => 'قوائم الموسيقى الأسبوعية، حركة الفنانين، المشاركات الصاعدة، والتحليلات التحريرية المصممة للاكتشاف والتفاعل والمشاركة.',
		'Open Trending Hub' => 'افتح مركز الرائج',
		'Browse Weekly Lists' => 'تصفح القوائم الأسبوعية',
		'LIVE MOMENTUM' => 'الحركة المباشرة',
		'Trending Now' => 'الرائج الآن',
		'Biggest Climbers' => 'الأكثر صعوداً',
		'New This Week' => 'جديد هذا الأسبوع',
		'FEATURED LISTS' => 'القوائم المميزة',
		'Editor-Selected List Destinations' => 'قوائم مختارة من المحررين',
		'FEATURED LIST' => 'قائمة مميزة',
		'Open &rarr;' => 'افتح &larr;',
		'FEATURED ARTISTS' => 'الفنانين المتميزين',
		'Editorial Focus Artists' => 'فنانين تحت الأضواء التحريرية',
		'FEATURED ARTIST' => 'فنان مميز',
		'Top Artists Lists' => 'قوائم أفضل الفنانين',
		'Full List &rarr;' => 'القائمة الكاملة &larr;',
		'MEDITERRANEAN POP' => 'موسيقى البحر المتوسط',
		'EXPLORE' => 'استكشف',
		'All Lists' => 'كل القوائم',
		'Browse All &rarr;' => 'تصفح الكل &larr;',
		'AWAITING DATA SYNC' => 'في انتظار مزامنة البيانات',
		'See Full List &rarr;' => 'عرض القائمة الكاملة &larr;',

		// Artist Archive
		'Discovery Artists' => 'اكتشف الفنانين',
		'Browse the most influential voices currently shaping the regional music lists.' => 'تصفح الأصوات الأكثر تأثيراً التي تشكل قوائم الموسيقى حالياً.',
		'&larr; Back to Lists' => '&rarr; العودة للقوائم',
		'No artists found' => 'لم يتم العثور على فنانين',
		'Import some list data to populate the artist discovery section.' => 'قم باستيراد بعض بيانات القوائم لملء قسم اكتشاف الفنانين.',
		'List Appearances' => 'مرات الظهور في القوائم',

		// Artist Single
		'Artist Not Found' => 'لم يتم العثور على الفنان',
		'ARTIST' => 'فنان',
		'OFFICIAL PROFILE' => 'الملف التعريفي الرسمي',
		'HOME' => 'الرئيسية',
		'TOP ARTIST LISTS' => 'قوائم أفضل الفنانين',
		'Share Artist' => 'مشاركة الفنان',
		'Copy Caption' => 'نسخ النص',
		'Monthly Listeners' => 'المستمعون شهرياً',
		'Hotness Score' => 'درجة التفاعل',
		'Total Entries' => 'إجمالي المشاركات',
		'Best Peak' => 'أفضل مركز',
		'ABOUT' => 'نبذة عن الفنان',
		'LISTING TRACKS' => 'الأغاني في القوائم',
		'POPULAR TRACKS' => 'الأغاني الأكثر شعبية',
		'LIST RANKINGS' => 'تصنيفات القوائم',
		'ALBUMS' => 'الألبومات',
		'More Lists' => 'المزيد من القوائم',

		// Track/Video Single
		'Intelligence Not Found' => 'لم يتم العثور على معلومات الأغنية',
		'The record for this item is not yet synchronized.' => 'سجل هذا العنصر لم يتم مزامنته بعد.',
		'Share Track' => 'مشاركة الأغنية',
		'PERFORMANCE INTELLIGENCE' => 'تحليلات الأداء',
		'Momentum' => 'الزخم',
		'Growth' => 'النمو',
		'Active Trend' => 'الاتجاه الحالي',
		'Velocity' => 'السرعة',
		'Predicted Rank' => 'المركز المتوقع',
		'Prediction' => 'التوقع',
		'Historical Progress' => 'التقدم التاريخي',
		'ENTRY' => 'الدخول',
		'PEAK' => 'الذروة',
		'LIST APPEARANCES' => 'الظهور في القوائم',
		'Month of ' => 'شهر ',
		'View Artist &rarr;' => 'عرض الفنان &larr;',
		'Stable' => 'مستقر',
		'Copied' => 'تم النسخ',

		// Single Chart
		'List Not Found' => 'لم يتم العثور على القائمة',
		'Awaiting Intelligence' => 'في انتظار التحليلات',
		'Awaiting Imports' => 'في انتظار الاستيراد',
		'LISTS' => 'القوائم',
		'Updated ' => 'تُحدث ',
		'entries' => 'مشاركة',
		'Share on X' => 'نشر على X',
		'Share on Facebook' => 'نشر على فيسبوك',
		'EDITORIAL OVERVIEW' => 'نظرة عامة تحريرية',
		'#1 THIS WEEK' => 'المركز الأول هذا الأسبوع',
		'Total Streams' => 'إجمالي البث',
		'Peak' => 'الذروة',
		'wks on list' => 'أسبوعاً في القائمة',
		'FULL RANKINGS' => 'التصنيف الكامل',
		'Rank' => 'المركز',
		'Move' => 'الحركة',
		'Cover' => 'الغلاف',
		'Title' => 'العنوان',
		'Last Wk' => 'الأسبوع الماضي',
		'Weeks on List' => 'الأسابيع في القائمة',

		// Trending
		'LIVE EDITORIAL INTELLIGENCE' => 'التحليلات التحريرية المباشرة',
		'Trending Music Hub' => 'مركز الموسيقى الرائجة',
		'Follow the tracks and artists moving fastest across weekly music lists, from rising breakouts to editor-backed picks.' => 'تابع الأغاني والفنانين الأكثر حركة في قوائم الموسيقى الأسبوعية، من الصاعدين الجدد إلى اختيارات المحررين.',
		'Rising Fast' => 'الصعود السريع',
		'Editor Picks' => 'اختيارات المحررين',
		'EDITOR PICK' => 'اختيار المحرر',

		// EditorialService dynamic templates
		'%1$s is the definitive weekly music list for %2$s, updated with fresh ranking intelligence for the week of %3$s. This week, %4$s leads the field, while momentum shifts across the top positions highlight where audience attention is moving next.' => '%1$s هي قائمة الموسيقى الأسبوعية النهائية لـ %2$s، تم تحديثها ببيانات تصنيف جديدة للأسبوع من %3$s. هذا الأسبوع، %4$s يتصدر القائمة، بينما تسلط تحركات المراكز الأولى الضوء على أين تتجه أنظار الجمهور لاحقاً.',
		'#1 analysis: %1$s by %2$s holds the top spot with a peak rank of #%3$d and %4$d weeks on the list.' => 'تحليل المركز الأول: %1$s للفنان %2$s يحتل الصدارة مع ذروة مركز #%3$d و%4$d أسبوعاً في القائمة.',
		'Biggest climber: %1$s by %2$s jumps %3$d places to #%4$d.' => 'الأكثر صعوداً: %1$s للفنان %2$s يقفز %3$d مراكز ليصل إلى المركز #%4$d.',
		'New entries: %s.' => 'المشاركات الجديدة: %s.',
		'the latest top-ranked release' => 'أحدث إصدار متصدر القائمة',
		'%s is making major moves across this week\'s music lists.' => 'يحقق %s تحركات كبيرة عبر قوائم الموسيقى هذا الأسبوع.',
		'Now trending: %s by %s on Kontentainment Lists.' => 'رائج الآن: %s بواسطة %s على قوائم كونتنتمنت.',
		'#1 this week %1$s %2$s by %3$s leads %4$s.' => 'المركز الأول هذا الأسبوع %1$s %2$s بواسطة %3$s يتصدر %4$s.',
		'Weekly Music Lists, Trending Artists & Track Rankings' => 'قوائم الموسيقى الأسبوعية، الفنانين الرائجين وتصنيفات الأغاني',
		'Explore weekly music lists, trending tracks, rising artists, and editorial highlights across the Egyptian and regional music market.' => 'استكشف قوائم الموسيقى الأسبوعية، الأغاني الرائجة، الفنانين الصاعدين، وأبرز اللقطات التحريرية عبر سوق الموسيقى المصري والإقليمي.',
		'Trending Music Lists: Rising Tracks, New Entries & Editorial Picks' => 'قوائم الموسيقى الرائجة: الأغاني الصاعدة، المشاركات الجديدة واختيارات المحررين',
		'Track what is trending now across the biggest music lists, from rising tracks and breakout entries to editor picks and fast-moving records.' => 'تتبع ما هو رائج الآن عبر أكبر قوائم الموسيقى، من الأغاني الصاعدة والمشاركات الجديدة إلى اختيارات المحررين والتسجيلات سريعة الحركة.',
		'%s This Week (Updated %s)' => '%s هذا الأسبوع (مُحدث %s)',
		'Explore %1$s for the week of %2$s, including the #1 track, biggest climbers, new entries, and movement across this week\'s music list.' => 'استكشف %1$s للأسبوع من %2$s، بما في ذلك الأغنية رقم 1، الأكثر صعوداً، المشاركات الجديدة، والحركة عبر قائمة موسيقى هذا الأسبوع.',
		'%s Charts, Rankings & Music List Profile' => 'قوائم وتصنيفات وملف الموسيقى لـ %s',
		'Explore rankings, chart appearances, and editorial intelligence for %s.' => 'استكشف التصنيفات، مرات الظهور في القوائم، والتحليلات التحريرية لـ %s.',
		'%s by %s Rankings, Trends & List History' => 'تصنيفات واتجاهات وتاريخ قوائم %s للفنان %s',
		'See trend movement, list appearances, and prediction signals for %s.' => 'شاهد حركة الاتجاه، مرات الظهور في القوائم، وإشارات التوقع لـ %s.',
		'Weekly Music Lists' => 'قوائم الموسيقى الأسبوعية',
		'Editorial intelligence powered by weekly rankings' => 'تحليلات تحريرية مدعومة بالتصنيفات الأسبوعية',
		'FEATURED' => 'مميز',
		'TRENDING' => 'رائج',
		'Updated editorial rankings' => 'تصنيفات تحريرية محدثة',
		'LIST' => 'قائمة',
		'Track intelligence profile' => 'الملف التعريفي للأغنية',
		'Artist intelligence profile' => 'الملف التعريفي للفنان',
		'Trending now on Kontentainment Lists.' => 'رائج الآن على قوائم كونتنتمنت.',
	);

	$trimmed = trim( $text );
	if ( isset( $translations[ $trimmed ] ) ) {
		return $translations[ $trimmed ];
	}
	if ( isset( $translations[ $text ] ) ) {
		return $translations[ $text ];
	}
	return $text;
}

/**
 * Global date translation helper for Egyptian Arabic.
 */
function charts_tr_date( $date_string ) {
	$months = array(
		'January' => 'يناير', 'February' => 'فبراير', 'March' => 'مارس',
		'April' => 'أبريل', 'May' => 'مايو', 'June' => 'يونيو',
		'July' => 'يوليو', 'August' => 'أغسطس', 'September' => 'سبتمبر',
		'October' => 'أكتوبر', 'November' => 'نوفمبر', 'December' => 'ديسمبر'
	);
	foreach ( $months as $eng => $ara ) {
		if ( stripos( $date_string, $eng ) !== false ) {
			$date_string = str_ireplace( $eng, $ara, $date_string );
			break;
		}
	}
	return $date_string;
}

charts();

