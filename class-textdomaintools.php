<?php
/**
 * @package WordPress
 * @subpackage miniWork
 * @author Ralf Albert
 * @version 0.1.1
 * 
 * Klasse zum Laden von Sprachdateien
 * 
 * @todo: Methode getPluginData()
 * @todo on deactivate-> unload textdomain
 */
if( ! class_exists( 'TextdomainTools' ) ){
	class TextdomainTools
	{
		private $mother;
		
		public function __construct( $mother ){
			$this->mother = $mother;
		}
		
		/**
		 * 
		 * Load the plugin-textdomain
		 * 
		 * @since 0.1.0
		 * @param string $textdomain
		 * @param string $domainpath
		 * @return void 
		 */
		public function loadtextdomain( $textdomain = false, $domainpath = false ){
	
			if( !$textdomain ){
				
				if( !empty( $this->mother->plugin_textdomain ) ){
					$textdomain = $this->mother->plugin_textdomain;
					
				} elseif( !empty( $this->mother->plugin_data['TextDomain'] ) ){
					$textdomain = $this->mother->plugin_data['TextDomain'];
					
				} elseif( !$textdomain || '' == $textdomain  ){
					$textdomain = 'de_DE';
					throw new Exception('No textdomain');		
				}
			}
	
			if( !$domainpath ){
				
				if( !empty( $this->mother->plugin_domainpath ) ){
					$domainpath = $this->mother->plugin_domainpath;
					
				} elseif( !empty( $this->mother->plugin_data['DomainPath'] ) ){
					$domainpath = $this->mother->plugin_data['DomainPath'];
					
				} elseif( !$domainpath || '' == $domainpath  ){
					$domainpath = '/languages';
					throw new Exception('No domainpath');				
				}
			}
					
			$mofile = $this->fetchMofile( $textdomain, $domainpath );
	
			if( $mofile ){
				// load language file
				$success = load_textdomain( $textdomain, $mofile );
	
				if( !$success ){
					throw new Exception('Can\'t load textdomain.');
				}
				
				return $success;
			}
			
			return $mofile;
		}
		
		/**
		 * 
		 * Find mo-file
		 * 
		 * @since 0.1.1
		 * @param string $textdomain
		 * @param string $domainpath
		 * @throws Exception
		 */
		private function fetchMofile( $textdomain, $domainpath ){
			$locale = apply_filters( 'plugin_locale', get_locale(), $textdomain );
				
			$path = $this->mother->wp_abspath . ltrim($this->mother->plugindir, '/') . '/' . $domainpath;
			$mofile = $path . '/' . $textdomain . '-' . $locale . '.mo';
		
			if( !$path || !is_dir( $path ) ){
				throw new Exception('No valid path to textdomain (textdomain: '.$textdomain.' | path: '.$path.')');
				return false;
			}
				
			// try to find a matching translation
			// first try domain-locale.mo
			if( !file_exists( $mofile ) ){
				 
				// if not found, try only locale.mo
				$mofile = str_replace( $textdomain . '-', '', $mofile );
					if( !file_exists( $mofile ) ){
						// really nothing was found
						throw new Exception('mo-file not found. mo-file:'.$mofile.' | domain: '.$textdomain.' | domainpath: '.$domainpath);
						$mofile = false;
				}
			}
			
			return $mofile;
		}
	
	} // end class
}