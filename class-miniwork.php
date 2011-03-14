<?php
/**
 * @package WordPress
 * @subpackage miniWork
 * @author Ralf Albert
 * @version 0.1.4
 * 
 * Klasse mit Standard-Methoden, derzeit Pr�fung von WP- und PHP-Version,
 * laden der Textdomain
 * 
 * @todo: Methode getPluginData()
 * @todo on deactivate-> unload textdomain
 */
class miniWork
{
	/**
	 * 
	 * settings
	 * @var array
	 */
	public $settings = array();
	
	/**
	 * 
	 * helper class for loadtextdomain
	 * @var object
	 */	
	private $helper;
	
	/**
	 * 
	 * path-vars plugin/theme
	 * @var string
	 * @todo: YAGNI? -> $_baseurl, $contentdir
	 */
	public $_parent, $_basename, $_basedir, $_baseurl;
	public $wp_home, $wp_abspath, $plugindir, $contentdir;
	
	/**
	 * 
	 * textdomain
	 * @var string
	 */
	public $textdomain, $domainpath;

	/**
	 * 
	 * data from the plugin header
	 * @var array
	 */
	public $plugin_data = array();
	
	/**
	 * 
	 * path-vars miniWork
	 * @var string
	 */
	private $miniwork_abspath, $miniwork_url;

	public function __construct( $args = false ){
		
		if( is_array( $args ) ){
			$this->configure( $args );	
		}
		else {
			// set parent path
			$this->_getParent( $args );
			// set all other vars from defaults
			$this->configure();
		}
		
		$this->_init();

		//doing checks, adding hooks&filters
		if( $this->settings['do_all'] ){
			
			$this->IsWP();
			$this->UseActivationHook();
			$this->NoUpgradeCheck();
			$this->LoadTextdomain( $this->textdomain, $this->domainpath );

		} else {
			
			if( $this->settings['is_wp_loaded'] )
				$this->IsWP();

			if( $this->settings['use_activation_hook'] ){
				$this->UseActivationHook();
			}
				
			if( $this->settings['no_upgrade_check'] ){
				$this->NoUpgradeCheck();
			}
			
			if( $this->settings['load_textdomain'] )
				$this->LoadTextdomain( $this->textdomain, $this->domainpath );
				
		}
		
	}

	/**
	 * 
	 * Setting vars used by the class
	 * @since 0.1.0
	 */
	protected function _init(){
		if( defined( 'ABSPATH' ) ){
			$this->wp_abspath = ABSPATH;
		}
		
		if( function_exists( 'home_url' ) ){
			// since 3.0			
			$this->wp_home = home_url();
		} else {
			// since 2.6 (maybe deprecated in future versions of wp)
			$this->wp_home = site_url();
		}
	
		// pluginfolder/pluginfile.php
		$this->_basename = plugin_basename( $this->_parent );

		// /pluginfolder
		$this->_basedir = '/' . dirname( $this->_basename );

		// /wp-content/plugins/pluginfolder
		$this->plugindir = str_replace( $this->wp_home, '', plugins_url() ) . $this->_basedir;
		
		// wp-content/
		$this->contentdir = str_replace( $this->wp_home, '', content_url() ) . '/';

		// miniWork related
		$this->miniwork_abspath = dirname( __FILE__ );
		$this->miniwork_url = str_replace( rtrim( $this->wp_abspath, '/' ), $this->wp_home, $this->miniwork_abspath );
		
		// windows only (dev-system)
		$this->miniwork_url = str_replace( '\\', '/', $this->miniwork_url );
		
		// data-collector
		// fetch data from plugin header
		$this->_collector();
		
		// fetch these data from the plugin header if they wasn't set before
		if( empty( $this->textdomain ) )
			$this->textdomain = $this->plugin_data['TextDomain'];
			
		if( empty( $this->domainpath ) )	
			$this->domainpath = $this->plugin_data['DomainPath'];
			
		// register the helper class TextdomainTools
		try {
			
			require_once 'class-textdomaintools.php';
			$this->helper = new TextdomainTools( $this );
			
		} catch (Exception $e) {
			$this->helper = false;
		}	
	}
	 
	/**
	 * Data-Collector
	 * Return al plugin coment data
	 *
	 * @since 0.1.1
	 * @return array $plugin_data
	 */
	protected function _collector(){
	
		$default_headers = array(
			'Name' => 'Plugin Name',
			'PluginURI' => 'Plugin URI',
			'Version' => 'Version',
			'Description' => 'Description',
			'Author' => 'Author',
			'AuthorURI' => 'Author URI',
			'TextDomain' => 'Text Domain',
			'DomainPath' => 'Domain Path',
			'Network' => 'Network',
			// Site Wide Only is deprecated in favor of Network.
			'_sitewide' => 'Site Wide Only',
		);			
		
		$this->plugin_data = get_file_data( $this->_parent, $default_headers, 'plugin' );
	 }

	/**
	 * 
	 * Check if parent is set and if it is a file or directory
	 * @since 0.1.4
	 * @param string $parent
	 * @throws Exception
	 */
	protected function _getParent( $parent ){
		if( empty($parent) || false == $parent ){
			throw new Exception('miniWork need a parent. Use <em>new miniWork( __FILE__ )</em> to set the parent or use the configuration array.');
			return false;
		}

		if( !is_file( $parent ) && !is_dir( $parent ) ){
			throw new Exception('miniWork need a parent. Use e.g. <em>new miniWork( __FILE__ )</em> to set the parent.<br /><em>'.$parent.'</em> is not a file or directory.');
		}
		
		$this->_parent = $parent;
	} 
	
	/**
	 * Setter
	 */
	public function __set( $var, $val ){
		if( key_exists( $var, $this->settings ) )
			$this->settings[$var] = $val;
		else
			$this->$var = $val;
	}
	
	/**
	 * Getter
	 */
	public function __get( $var ){
		return $this->$var;
	}
	
		 
/* ----- methods for public use (maybe somthing like wrappers) ----- */
	
	/**
	 * 
	 * Settings via array
	 * Not really for public use, but an alternative way to configure miniWork
	 * @since 0.1.4
	 * @param array $args
	 */
	public function configure( $args = array() ){
		$defaults = array(
			'parent' => false,
			'wp_version' => '3.0.0',
			'php_version' => '5.2.8',
			'textdomain' => false,
			'domainpath' => false,
		
			'do_all' => true,
			'is_wp_loaded' => false,
			'use_activation_hook' => false,
			'no_upgrade_check' => false,
			'load_textdomain' => false,
		);
		
		$this->settings = array_merge( $defaults, $args );
		
		if( !$this->_parent ){
			$this->_getParent( $this->settings['parent'] );
			unset( $this->settings['parent'] );
		}
		
		$this->textdomain = $this->settings['textdomain'];
		$this->domainpath = $this->settings['domainpath'];
		unset( $this->settings['textdomain'], $this->settings['domainpath'] );
		
	}
	
	/**
	 * 
	 * Register the Plugin-Activation-Hook (PAK)
	 */
	public function UseActivationHook(){
		register_activation_hook( $this->_parent, array( &$this, 'activate' ) );
	}
	
	/**
	 * 
	 * Set the filter to block upgrade checks on the plugin
	 */
	public function NoUpgradeCheck(){
		add_filter( 'http_request_args', array( &$this, 'no_upgrade_check' ), 5, 2 );
	}
	
	/**
	 * Load the (plugin-)textdomain
	 * 
	 * @since 0.1.1
	 *  
	 */
	public function LoadTextdomain( $textdomain = false, $domainpath = false ){

		if( !$this->helper )
			return false;			
			
		return $this->helper->loadtextdomain( $textdomain, $domainpath );
	}
	
	
	/**
	 * 
	 * Checks if WordPress core or framework is used.
	 * Returns true|false if $abort is false, else exit the script with sending headers
	 * @sine 0.1.1
	 * @param bool $abort true [default]|false
	 * @return bool static is_WP true|false
	 */
	public function IsWP( $abort = true ){

		global $wpdb;
		$is_WP = false;
		
		if( isset( $wpdb ) && ( $wpdb instanceof wpdb ) ){
			$is_WP = true;
		}

		if( !$abort && !$is_WP ){
			return $is_WP;
			
		} elseif( $abort && !$is_WP ) {
			header( 'Status: 403 Forbidden' );
			header( 'HTTP/1.1 403 Forbidden' );
			exit();
		}
		
		return $is_WP;
	}
	
	/**
	 * 
	 * Just another name. Don't think about it... WP-style,nothing more.
	 * @param bool $abort
	 */
	public function is_WP( $abort = true ){
		$this->IsWP( $abort );
	}

/* ----- methods for internal use. still public, but used by the methods above  ----- */	
			
	
	/**
	 * Blocks update checks for this plugin.
	 * Unset the basename in the array of update plugins
	 * url: http://api.wordpress.org/plugins/update-check/1.0/
	 *
	 * @author Mark Jaquith http://markjaquith.wordpress.com
	 * @link   http://wp.me/p56-65
	 * @param  array $r
	 * @param  string $url
	 * @return array $r
	 * @since  0.1.0
	 */
	public function no_upgrade_check($r, $url) {			
		
		if ( 0 !== strpos(
				$url, 
				'http://api.wordpress.org/plugins/update-check'
				)
			) { // Not a plugin update request. Bail immediately.
			return $r;
		}
	
		$plugins = unserialize( $r['body']['plugins'] );
		unset (
			$plugins->plugins[$this->_basename],
			$plugins->active[array_search( $this->_basename, $plugins->active )]
		);
		
		$r['body']['plugins'] = serialize($plugins);
		
		return $r;
	}
	
	
	/**
	 * check on activate for wp- and php-version
	 * 
	 * @since 0.1.0
	 */
	public function activate() {

		global $wp_version;
		$textdomain = 'miniwork';

		// In case of using miniWork->activate() the function deactivate_plugins is not present on deactivation of plugins
		// So do not check on deactivation
		if( !function_exists( 'deactivate_plugins') ){
			return false;
		}
		
			
		if ( !version_compare( $wp_version, $this->settings['wp_version'], '>=') ) {
			$this->_activate_textdomain( $textdomain );
			
			deactivate_plugins( $this->_parent );
			// @todo throw exception instead of die()
			die( 
				wp_sprintf( 
					'<strong>%1s:</strong><br />'
				. __( 'Sorry, This plugin requires WordPress %2s+', $textdomain )
					, $this->plugin_data['Name'], $this->settings['wp_version']
				)
			);
		}
		
		if ( version_compare(PHP_VERSION, $this->settings['php_version'], '<') ) {
			$this->_activate_textdomain( $textdomain );
			
			deactivate_plugins( $this->_parent ); // Deactivate ourself
			// @todo throw exception instead of die()
			die( 
				wp_sprintf(
					'<strong>%1s:</strong><br />'
				. __( 'Sorry, This plugin has taken a bold step in requiring PHP %2s+. Your server is currently running PHP %3s, Please bug your host to upgrade to a recent version of PHP which is less bug-prone. At last count, <strong>over 80%% of WordPress installs are using PHP 5.2+</strong>.', $textdomain )
					, $this->plugin_data['Name'], $this->settings['php_version'], PHP_VERSION 
				)
			);
		}
	}
	
	/**
	 * 
	 * Load textdomain for error-messages in activate
	 * @throws Exception
	 */
	private function _activate_textdomain( $textdomain = 'miniwork' ){
		global $l10n;
		$load_td = true;

		// textdomain already loaded			
		if( isset( $l10n[$textdomain] ) ){
			return;
		}
				
		$mofile = $this->miniwork_abspath.'/babel/'.$textdomain.'-'.get_locale().'.mo';
			if( !is_readable( $mofile ) ){
				throw new Exception('mo-file not found. mo-file: '.$mofile);
				$load_td = false;
			}
			

		if( $load_td ){
			$success = load_textdomain( $textdomain, $mofile );
			if( !$success ){
				throw new Exception('textdomain was not loaded');
				$textdomain = false;
			}
		}
		
		// garbage collection
		unset( $load_td, $success, $mofile );
	}

} // end miniWork	
?>