miniWork
========

1. Einbinden der Klasse
	require_once (pfad-zu/miniwork/class-miniwork.php);

	
2. Starten von miniWork
	
	miniWork arbeitet mit Exceptions als Fehlermeldungen die abgefangen werden sollten. Deswegen ist jeder Aufruf von miniWork
	oder einer Instanz von miniWork in einem try-catch-Block am besten aufgehoben. Über catch können die Fehlermeldungen
	verarbeitet, ausgegeben, geloggt oder verworfen werden.
	
	miniWork erwartet als minimalste Angabe den Pfad zur aufrufenden Datei. Dies wird in den meisten Fällen mittels __FILE__
	übergeben (siehe Pkt. 2.1) . Wird miniWork ohne weitere Parameter aufgerufen, verwendet es Vorgabewerte (siehe Pkt. 3. Defaults).
	Sollen die Vorgabewerte durch andere Werte ersetzt werden, kann dies über ein Array oder über die Setter-Methode von miniWork
	erfolgen (siehe Pkt. 2.2 - 2.3) 

	2.1 Mit Vorgabewerten (defaults)
	
		try {
			$mw = new miniWork( __FILE__ );
		} catch ( Exception $e ) {
			die( var_dump( $e->getMessage() ) );
		}
		
	2.2 Mit Array
	
		$args = array( 'parent' => __FILE__, 'wp_version' => '3.1.0' );
		
		try{
			$mw = new miniWork( $args );
		} catch ( Exception $e ) {
			var_dump( $e->getMessage() );
		}

	2.3 Mit Setter
	
		try {
			$mw = new miniWork( __FILE__ );
		} catch ( Exception $e ) {
			die( var_dump( $e->getMessage() ) );
		}

		$mw->textdomain = 'a_textdomain';
		$mw->domainpath = '/languages';
		
		try {
			$mw->LoadTextDomain();
		} catch( Exception $e ) {
			$error->add( $e->getMessage() );
		}

		
3. Defaults (Voreinstellungen)
	
	miniWork versucht so viele Daten wie möglich selbstständig zu erfassen. Zudem arbeitet miniWork mit vorgegebenen Werten.
	Aus dem Plugin-Header versucht miniWork folgende Daten zu erfassen
	(in Klammern steht jeweils die Eigenschaft über die man den Wert auslesen kann):
	
		- Name des Plugins ($plugin_data)
		- URL des Plugins ($plugin_data)
		- Version ($plugin_data)
		- Beschreibung ($plugin_data)
		- Autor ($plugin_data)
		- URL des Autoren ($plugin_data)
		- Sprachdatei des Plugins ($textdomain)
		- Pfad zur Sprachdatei ($domainpath)
		- Network ($plugin_data)
		- Sitewide ($plugin_data)
	
	Folgende Pfade versucht miniWork zu ermitteln:
	 	
		- Absoluter Pfad zum Plugin ($parent)
		- WP-Home ($wp_home)
		- Plugin-Basename ($_basename)
		- Plugin-Basedir ($_basedir)
		[- Plugin URL ($_baseurl)] wird ggf. zukünftig nicht mehr unterstützt
		- Plugin-Verzeichnis ($plugindir)
		[- Content-Verzeichnis ($contentdir)] wird ggf. in Zukunft nicht weiter unterstützt
		
	Folgende Daten sind fest voreingestellt:
	
		- WordPress Version ($settings['wp_version'] -> 3.0.0)
		- PHP-Version ($settings['php_version'] -> 5.2.8)  
	
	Folgende Aktionen werden von miniWork automatisch durchgeführt:
		
		- IsWP -> Prüfung ob WordPress bereits gestartet wurde und ob eine gültige Verbindung zur Datenbank aufgebaut wurde
		- UseActivationHook -> Prüfung auf WP- und PHP-Version bei Aktivierung des Plugins. Verwendet den Hook register_activation_hook()
		- NoUpgradeCheck -> Verhindert das Überprüfen auf Updates. Verwendet den Filter http_request_args
		- LoadTextdomain -> Lädt die Sprachdatei des Plugins/Themes anhand von Daten im Plugin-Header oder durch Angaben via Parameter


4. Methoden

	miniWork bietet vier Methoden für Standardaufgaben an.
	
	4.1 IsWP (is_WP)
	4.2 UseActivationHook
	4.3 NoUpgradeCheck
	4.4 LoadTextdoamin
	

5. Eigenschaften

	5.1 $wp_version
	5.2 $php_version
	5.3 $textdomain
	5.4 $domainpath
	5.5 $plugin_data (array)
	
	