<?php
/**
* @package waPluginator
* @name generator.php - demo page
*/

class app {

    static $ptitle = 'page title';
    static $body = '';
	static $htmlhead = <<< EOHTM
<!DOCTYPE html>
<html>
<head>
<title>{title}</title>
<meta charset="UTF-8">
<link rel="stylesheet" type="text/css" href="styles.css" />
<script type="text/javascript" src="jquery-2.2.2.min.js"></script>
<script type="text/javascript" src="as_jsfunclib.js"></script>
</head>
<body>
<h1>{title}</h1>

EOHTM;

	static $html_footer = "</body></html>";

	public static function setPagetitle($title) {
		self::$ptitle = $title;
	}

	public static function appendHtml($html) {
		self::$body .= $html;
	}
	public static function renderPage() {

		$header =  str_replace(
			array('{title}'),
			array(self::$ptitle),
			self::$htmlhead
		);
		echo $header;
		echo self::$body;
		echo self::$html_footer;
	}
	public static function footer() {
		echo self::$html_footer;
	}
}

include_once('../lib/class.codePreprocessor.php');
include_once('../src/waPluginator.php');

waPluginator::setBaseUri($_SERVER['PHP_SELF']);
// waPluginator::autoLocalize();

// waPluginator::addLanguage('fr' , 'French');
waPluginator::addStdCompilers();
waPluginator::setOptions(array(
        'appname' =>'My web application'
       ,'author' =>'Here is My Name'
       ,'email' =>'info@mycompany.com'
       ,'link' =>'http://www.mycompany.com'
    )
);

$p = array_merge($_GET, $_POST);
if(!empty($p['action'])) {

    waPluginator::performAction($p);
    exit;
}
else {
    app::setPageTitle('Module/Plugin Generator');

    app::appendHtml(waPluginator::designerForm(true));
    app::renderPage();
#    echo "wa HTML:";
#    echo $html;

}
