<?php
/**
 * @package WordPress
 * @subpackage miniWork
 * @author Ralf Albert
 * @version 0.1.4
 * 
 * Klasse mit Standard-Methoden, derzeit Prüfung von WP- und PHP-Version,
 * laden der Textdomain
 * 
 * @todo: Methode getPluginData()
 * @todo on deactivate-> unload textdomain
 */
if( ! class_exists( 'miniWork' ) ){
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
		 * path-vars plugin/theme
		 * @var string
		 * @todo: YAGNI? -> $_baseurl, $contentdir
		 */
		public $_parent, $_basename, $_basedir, $_baseurl;
		public $wp_home, $wp_abspath, $plugindir, $contentdir;
		
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
	
		/**
		 * 
		 * constructor
		 * @since 0.1.0
		 * @param mixed string|array $args path to parent file
		 */
		public function __construct( $args = false ){
			
			if( is_array( $args ) ){
				$this->_configure( $args );	
			}
			else {
				// set parent path
				$this->_getParent( $args );
				// set all other vars from defaults
				$this->_configure();
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
			
			// fetch these data from the plugin header if they wasn't set in configure()
			if( empty( $this->settings['textdomain'] ) )
				$this->settings['textdomain'] = $this->plugin_data['TextDomain'];
				
			if( empty( $this->settings['domainpath'] ) )	
				$this->settings['domainpath'] = $this->plugin_data['DomainPath'];
				
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
		 * 
		 * Settings via array
		 * Not really for public use, but an alternative way to configure miniWork
		 * @since 0.1.4
		 * @param array $args
		 */
		protected function _configure( $args = array() ){
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
			
		}
		
		
		/**
		 * Setter
		 * @since 0.1.2
		 * @param string $var
		 * @param mixed $val
		 */
		public function __set( $var, $val ){
			if( key_exists( $var, $this->settings ) )
				$this->settings[$var] = $val;
			else
				$this->$var = $val;
		}
		
		/**
		 * Getter
		 * @since 0.1.2
		 * @param string $var
		 */
		public function __get( $var ){
			return $this->$var;
		}
		
			 
	/* ----- methods for public use (maybe somthing like wrappers) ----- */
		
		/**
		 * 
		 * Register the Plugin-Activation-Hook (PAK)
		 * @since 0.1.3
		 */
		public function UseActivationHook(){
			register_activation_hook( $this->_parent, array( &$this, 'activate' ) );
		}
		
		/**
		 * 
		 * Set the filter to block upgrade checks on the plugin
		 * @since 0.1.3
		 */
		public function NoUpgradeCheck(){
			add_filter( 'http_request_args', array( &$this, 'no_upgrade_check' ), 5, 2 );
		}
		
		/**
		 * 
		 * Load the (plugin-)textdomain
		 * @since 0.1.3
		 */
		public function LoadTextdomain( $textdomain = false, $domainpath = false ){
	
			$success = $this->_loadtextdomain( $textdomain, $domainpath );
				if( !$success ){
					throw new Exception('textdomain not loaded');
				}
		}
		
		
		/**
		 * 
		 * Checks if WordPress core or framework is used.
		 * Returns true|false if $abort is false, else exit the script with sending headers
		 * @since 0.1.3
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
		 * @since 0.1.0
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
		 * 
		 * check on activate for wp- and php-version
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
				$this->_textdomain( $textdomain );
				
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
				$this->_textdomain( $textdomain );
				
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
		 * @since 0.1.4
		 * @throws Exception
		 */
		private function _textdomain( $textdomain = false, $domainpath = false ){
			
			if( !$textdomain ){
				
				if( !empty( $this->settings['textdomain'] ) ){
					$textdomain = $this->settings['textdomain'];
					
				} elseif( !empty( $this->plugin_data['TextDomain'] ) ){
					$textdomain = $this->plugin_data['TextDomain'];
					
				} elseif( !$textdomain || '' == $textdomain  ){
					$textdomain = 'de_DE';
					throw new Exception('No textdomain');		
				}
			}
	
			if( !$domainpath ){
				
				if( !empty( $this->settings['domainpath'] ) ){
					$domainpath = $this->settings['domainpath'];
					
				} elseif( !empty( $this->plugin_data['DomainPath'] ) ){
					$domainpath = $this->plugin_data['DomainPath'];
					
				} elseif( !$domainpath || '' == $domainpath  ){
					$domainpath = '/languages';
					throw new Exception('No domainpath');				
				}
			}
			
			return load_plugin_textdomain( $textdomain, $domainpath );
		}
		
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
}	
?>