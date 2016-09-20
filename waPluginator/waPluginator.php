<?PHP
/**
* @package waPluginator - Web Applications plugin generator
* @name waPluginator.php
* Main class module
* @Author Alexander Selifonov <alex [at] selifan {dot} ru>
* @link https://github.com/selifan/waPluginator
* @Version 0.3.072
* started 2013-01-18
* modified 2016-09-20
**/
class WaPluginator {

    static $FOLDER_OUTPUT = 'plugins/';
    static $FOLDER_I18N = 'i18n/';
    static $FOLDER_CFG = 'cfg/';
    static $FOLDER_TMP = 'tmp/';
	/**
	* $TYPES_TXT contains all extensions for files that supposed to be "text" files and so can be processed
	* by code preprocessor. All other files (jpg, png etc.) just will be copied to destination folder
	* @var array $TYPES_TXT
	*/
    static $TYPES_TXT = array(
        'txt', 'php', 'inc', 'phtml', 'css', 'scss', 'less', 'htm', 'html',
        'js', 'rb', 'py', 'c', 'cpp', 'xml', 'xhtml', 'json'
    );
	// Messgaes / labels, to be localized by WaPluginator::setLocalization($arr)
    private static $_msg = array(
       'author_name' => 'Author Name',
       'author_email' => 'Author Email',
       'author_site' => 'Author web page (link)',
       'creating_from' => 'Creating from',
       'base_class_name' => 'Plugin Base Class Name',
       'plugin_folder' => 'Plugin File/Folder Name',
       'plugin_type' => 'Plugin Type',
       'main_title' => 'Main title',
       'description' => 'Plugin Вescription',
       'acl_list' => 'ACL right name(s) (comma delimited)',
       'color_scheme' => 'Color Scheme (Theme)',
       'create_bkend' => 'Create Backend Module',
       'create_js' => 'Create JavaScript Module',
       'create_locales' => 'Create Localization Files',
       'btn_generate' => 'Generate',
       'btn_reverseing' => 'Code -&gt; Template',
       'title_log' => 'Generation log',

       'size' => 'size',
       'compile_ok' => 'Sass/Less Compilation successful',
       'err_create_folder' => 'Create folder error',
       'err_zipopen' => 'File open error',
       'err_zipnotfound' => 'Zip file not found',
       'err_bad_scss' => 'Compile scss file error',
       'err_bad_less' => 'Compile less file error',
       'job_finished' => 'Files creation finished'
    );
    private static $_cfgFile = false;
    private static $_schemeFile = false;
    private static $_baseuri = '';

    private static $_compilers = array(); // one element: 'scss' => array('object'=><compiler object>, 'method'=>{compile_method_name}, 'outext'=>'css')

    private static $FOLDER_ROOT = null;
    private static $_saveBak = true;
    private static $_langs = array('en' => 'English', 'ru' => 'Russian');
    private static $_p = array();
    private static $_tplfile_main = '';
    private static $_tplfile_backend = '';
    private static $_options = array();
    private static $_result = '';
    private static $_plgtypes = array(array('basename'=>'std', 'title'=>'Stub module'));
    private static $author = '';
    private static $author_email = '';
    private static $author_site = 'www.no-site.net';
    private static $_log = array();
    private static $parsvars = array();
    private static $subst = array();
    private static $preproc = null;
    private static $_varsets = array(); // predefined variable sets to be reused
    private static $_schemes = array(); // color schemes / themes

    static private $scss_comp = null; # sass compiler object to be here!
    static private $less_comp = null; # less compiler object to be here!

    private static $_scheme = array(); // chosen color scheme
    private static $_useSchemes = false;
    private static $_clrsubst = array();

	/**
	* Set localized interface strings
	*
	* @param mixed $arr associative array with new labels/message values
	*/
    public static function setLocalization($arr) {
    	if (is_array($arr) && count($arr))
    		self::$_msg = array_merge(self::$_msg, $arr);
	}
    /**
    * Add a compiler for file type/extension
    *
    * @param mixed $uri
    */
    public static function addCompiler($ext, &$compObj, $funcName, $outext='') {

        (is_object($compObj) && method_exists($compObj,$funcName))
            or die ('addCompiler call with wrong parameters!');
        $ext = strtolower($ext);
        self::$_compilers[$ext] = array(
            'object' => $compObj,
            'method' => $funcName,
            'outext' => $outext
        );
    }
    public static function setBaseUri($uri) {
        self::$_baseuri = $uri;
    }
    /**
    * Adds one language name
    *
    * @param mixed $langKey short key (en, fr, it ...)
    * @param mixed $langName language name (french, italian, ...)
    */
    public static function addLanguage($langKey, $langName='') {
        self::$_langs[$langKey] = ($langName) ? $langName : $langKey;
    }
	/**
	* Automatic interface localization
	* Uses current PHP locale setting or WebApp::getClientLanguage
	*/
    public static function autoLocalize() {
        $currentLocal = setlocale(LC_CTYPE, 0);
        $mct = explode('_',$currentLocal);
#		$lc = preg_match_all("/LC_CTYPE=([a-zA-Z\\.\\_]+)/i", $currentLocal,$matches);
		$langid = '';
		switch(strtolower($mct[0])) {
			case 'russian': $langid = 'ru'; break;
			case 'english': $langid = 'en'; break;
			case 'german': $langid = 'de'; break;
			case 'finnish': $langid = 'fi'; break;
			case 'french': $langid = 'fr'; break;
			case 'italian': $langid = 'it'; break;
			case 'japanese': $langid = 'ja'; break;
			case 'dutch': $langid = 'nl'; break;
			case 'czech': $langid = 'cs'; break;
			case 'bulgarian': $langid = 'bg'; break;
			case 'korean': $langid = 'ko'; break;
			// case 'your_lang': $langid = 'xx'; break;
			default: $langid = strtolower($mct[0]); break;
		}
		if (class_exists('WebApp') && is_callable('WebApp::getClientLanguage'))
			$langid = WebApp::getClientLanguage();

		$lngFile = __DIR__ . DIRECTORY_SEPARATOR . "waPluginator.lng.$langid.php";
		if ($langid && is_file($lngFile)) {
			// automatic localization
			$localStrg = include($lngFile);
			self::setLocalization($localStrg);
			unset($localStrg);
		}
	}

    /**
    * Setting user configuration and color scheme definition files
    *
    * @param mixed $cfgFile
    * @param mixed $schemeFile
    */
    public static function setConfigFile($cfgFile='', $schemeFile='') {

        if (($schemeFile) && is_file($schemeFile))
            self::$_cfgFile = $schemeFile;
        if (($schemeFile) && is_file($schemeFile))
            self::$_schemeFile = $schemeFile;
    }

    /**
    * Loads configuration from xml files
    *
    */
    public static function loadConfig() {

        $cfgname = (self::$_cfgFile) ? self::$_cfgFile : __DIR__ . '/waPluginator.xml';
        $schname = (self::$_schemeFile) ? self::$_schemeFile : __DIR__ . '/colorSchemes.xml';
        if (defined('FOLDER_ROOT')) self::$FOLDER_ROOT = constant('FOLDER_ROOT');
        if (is_file($cfgname)) {

            $xml = simplexml_load_file($cfgname);

            if (isset($xml->credits)) {
                self::$author       = (isset($xml->credits['author']) ? (string)$xml->credits['author'] : '');
                self::$author_email = (isset($xml->credits['email']) ? (string)$xml->credits['email'] : '');
                self::$author_site  = (isset($xml->credits['site']) ? (string)$xml->credits['site'] : '');
            }

            // read predefined var sets
            if (isset($xml->varsets)) {
                foreach($xml->varsets->children() as $id=>$vset) {
                    $id = isset($vset['id']) ? (string)$vset['id'] : 'vset'.(1+count(self::$_varsets));
                    self::$_varsets[$id] = self::readCfgVars($vset);
                }
            }
            // read plugintypes
            if (isset($xml->plugintypes)) {
                self::$_plgtypes = array();
                foreach($xml->plugintypes->children() as $cid=>$obj) {

                    $plgtype = (string)$obj['basename'];
                    $ptype = array(
                    	'basename'=>$plgtype,
                    	'title'=>(string)$obj['title'],
                    	'colorschemes' => (isset($obj['colorschemes']) ? (int)$obj['colorschemes'] : false)
                    );

                    if (class_exists('ZipArchive') && !empty($obj->srcarchive))
                    	$ptype['srcarchive'] = (string) $obj->srcarchive; # template files in zip file

                    $files = array();
                    if (isset($obj->files)) foreach($obj->files->children() as $itm) {
                        $files[] = array(
                             'name'   => (string)$itm['name']
                            ,'src'    => (string)$itm['src']
                            ,'makeif' => (isset($itm['makeif'])? (string)$itm['makeif'] : '')
                        );
                    }
                    $ptype['files'] = $files;
                    if (isset($obj->vars)) {
                        $plg_vars = array();
                        $ptype['vars'] = self::readCfgVars($obj->vars, true);
                    }
                    self::$_plgtypes[$plgtype] = $ptype;
                }
            }
            $xml = null;
        }

		// read color schemes
        if (is_file($schname)) {

            $xml = simplexml_load_file($schname);

            if (isset($xml->sets)) {
                foreach($xml->sets->children() as $id=>$vset) {
#					WriteDebugInfo("$id: ", $vset);
                    $id = isset($vset['id']) ? (string)$vset['id'] : 'vset'.(1+count(self::$_varsets));
                    self::$_schemes[$id] = array();
                    foreach($vset as $item) {
                    	$name = isset($item['name']) ? (string)$item['name'] : 'item'.(1+count(self::$_schemes[$id]));
                    	self::$_schemes[$id][$name] = isset($item['value']) ? (string)$item['value'] : '';
					}
				}
            }
			$xml = null;
		}
    }

    public static function readCfgVars($obj, $withVarset=false) {
        $ret = array();

        foreach($obj->children() as $id => $onevar) {
            if ($id === 'usevarset') {
                $vsetid = (string) $onevar;

                if ($withVarset && isset(self::$_varsets[$vsetid])) {
                    $ret = array_merge($ret, self::$_varsets[$vsetid]);
                }
            }
            elseif ($id === 'var' && isset($onevar['name'])) {
                $varname = (string)$onevar['name'];
                $ret[$varname] = array(
                    'type'  => (isset($onevar['type'])? (string)$onevar['type'] : 'text')
                   ,'label' => (isset($onevar['label'])? toMainCset((string)$onevar['label']) : $varname)
                   ,'width' => (isset($onevar['width'])? (string)$onevar['width'] : '')
                   ,'height' => (isset($onevar['height'])? (string)$onevar['height'] : '')
                   ,'options' => (isset($onevar['options'])? toMainCset((string)$onevar['options']) : '')
                   ,'default' => (isset($onevar['default'])? (string)$onevar['default'] : '')
                );
            }
        }
        return $ret;
    }
    public static function delTree($dir) {
        $files = array_diff(scandir($dir), array('.','..'));
        foreach ($files as $file) {
          (is_dir("$dir/$file")) ? self::delTree("$dir/$file") : unlink("$dir/$file");
        }
        return rmdir($dir);
    }

    public static function designerForm($buffered=false) {

        $uri = self::$_baseuri;
        $str_langs = '';
        self::loadConfig();

        $author = self::$author;
        $author_email = self::$author_email;
        $author_site  = self::$author_site;

        $plgOptions = '';
        $mdl = array();
        $var_blocks = '';
        $withColors = '';
        foreach (self::$_plgtypes as $item) {

            $basename = $item['basename'];
            if ($item['colorschemes'])
            	$withColors .= ($withColors ? ',':'') . "'$basename'";
            // prepare "accessibility" for checkboxes based on existance of respective template file
            $isjs = (is_file(__DIR__ . "/$basename.js.txt") ? 1:0);
            $isbk = (is_file(__DIR__ . "/$basename.backend.txt") ? 1:0);
            $plgOptions .= "<option value='$basename'>$item[title]</option>";
            $mdl[] = "'$basename':{ backend:$isbk, js:$isjs }";
            if (isset($item['vars'])) {
                $rowclass = 'vars_' . $basename;
                foreach ($item['vars'] as $varid => $vobj) {
                    $inputid = 'input_' . $basename . '_' . $varid;

                    $attr = array();
                    if (!empty($vobj['width'])) $attr[] = 'width:'. self::makeCssAttrib($vobj['width']);
                    if (!empty($vobj['height'])) $attr[] = 'height:'. self::makeCssAttrib($vobj['height']);
                    if ($vobj['type']==='textarea') {
                    	$attr[] = 'max-height:300px; resize:vertical; overflow:auto';
                    	if (empty($vobj['height'])) $attr[] = 'height:40px';
                    	if (empty($vobj['width']))  $attr[] = 'width:100%';
					}
                    # TODO other possible attribs...
					$strattr = count($attr) ? "style='".implode('; ', $attr)."'" : '';

                    $defvar= empty($vobj['default']) ? '' : (string) $vobj['default'];
                    switch ($vobj['type']) {
                        case 'checkbox':
                            $chk = empty($vobj['default']) ? '' : 'checked="checked"';
                            $input = "<input type='checkbox' name='$inputid' value='1' $chk />";
                            break;

                        case 'select':
                            $defvar= empty($vobj['default']) ? '0' : $vobj['default'];
#                            WriteDebugInfo('select options:', $vobj['options']);
                            $optlist = GetArrayFromString($vobj['options']);
                            $input = "<select name='$inputid' $strattr >" . DrawSelectOptions($optlist,$defvar,TRUE) . '</select>';
                            break;

                        case 'textarea':
                            $defvar = str_replace("\\n","\n", $defvar);
                            $input = "<textarea name='$inputid' class='ibox' $strattr >$defvar</textarea>";
                            break;

                        default: // 'text'
                            $w = empty($vobj['width']) ? '60px' : ($vobj['width'].'px');
                            $input = "<input type='text' name='$inputid' class='ibox' $strattr value='$defvar' />";
                            break;
                    }
                    $strg = "<tr id='{$rowclass}_$varid' style='display:none'><td style='padding-left:1.5em'>$vobj[label]</td>"
                     . '<td>' . $input . "</td></tr>\n";
                    $var_blocks .= $strg;
                }
            }
        }
        $mdl = implode(', ', $mdl);
        $colorshemeOptions = '';
        foreach (self::$_schemes as $schemeId=>$svars) {
			$colorshemeOptions.= "<option value='$schemeId'>$schemeId</option>";
		}
        foreach(self::$_langs as $key=>$langname) {
            $str_langs .= "<input type=\"checkbox\" name=\"locale_$key\" id=\"locale_$key\" value=\"1\" /> <label for=\"locale_$key\">$langname</label> &nbsp;\n";
        }

        $m = self::$_msg;

        $ret = <<< EOHTM
<script type="text/javascript">
waPluginator = {
   f1: false
  ,f2: false
  ,f3: false
  ,chgRights:false
  ,withCsheme: [$withColors]
  ,mdl: { $mdl }
  ,restoreOpt: function(obj) {
      var params = { action: 'restoreoptions', plg_basename: obj.value };
      SendServerRequest("$uri", params);
      setTimeout("$('#plugintype').trigger('change');", 100);
   }
  ,generate: function() {
      if($('#plg_basename').val() === '') { alert('Empty plugin name !'); return false; }
      var params = $('#fm_wapluginator').serialize();
      $('#wapluginator_result').text('');
      $.post("$uri", params, function(data){
          $("#wapluginator_result").html(data);
      });
   }
   ,baseKeyUp : function() {
        if(!this.f1) this.f1 = $('#plg_basename');
        if(!this.f2) this.f2 = $('#plg_folder');
        if(!this.f3) this.f3 = $('#plg_rights');
        this.f2.val( this.f1.val().toLowerCase() );
        if(!this.chgRights) this.f3.val(this.f1.val().toLowerCase());
        $('#waBtnReverse').prop('disabled', (this.f1.val() ===''));
   }
   ,chgPlgtype: function() {
      var pltype = $('select#plugintype').val();
      $('tr[id^=vars_]').hide();
      if (pltype === '') { $('#waBtnStart').prop('disabled',true); return; }
      $('tr[id^=vars_'+pltype+']').show();
      $('#waBtnStart').prop('disabled',false);

      if (waPluginator.withCsheme.indexOf(pltype)>-1)
      	$('#tr_colorSchemes').show();
      else $('#tr_colorSchemes').hide();

      if (waPluginator.mdl[pltype]['backend']) $('#plg_createbkend').attr('disabled',false);
      else $('#plg_createbkend').attr({'disabled':true,'checked':false});

      if (waPluginator.mdl[pltype]['js']) $('#plg_createjs').attr('disabled',false);
      else $('#plg_createjs').attr({'disabled':true,'checked':false});
   }
   ,startReverse: function() {
      var opts = {
          width : 500
         ,title : 'Creating template from codes'
         ,closeOnEscape: true
         ,text:
           'Enter new plugin base name' +
           " <input type='text' id='new_templatename' class='ibox w200' /><br>" +
           "<br>Start creating template (can overwrite existing) ?"
      };
      dlgConfirm(opts, this.doReverse, null);
   }
   ,doReverse: function() {
      var tplid = $('#new_templatename').val();
      if(tplid === '') { TimeAlert('Empty template name !',1,'msg_error'); return false; }
      var params = { action: 'reverse_template', plugin: waPluginator.f1.val(), templateid: tplid};
      $.post("$uri", params, function(data){
          var splt = data.split("|");
          if (splt[0] === 'ERR') TimeAlert(splt[1],4,'msg_error');
          else $("#wapluginator_result").html(data);

      });
   }
};
</script>

<form id="fm_wapluginator"><input type="hidden" name="action" value="generateall" />
<div class="div_outline" style="width:600px; min-height:400px; position:relative;float:left">
<table>
  <tr>
    <td>$m[author_name]</td>
    <td><input type="text" name="author" id="author" class="ibox w300" value="$author"/></td>
  </tr>
  <tr>
    <td>$m[author_email]</td>
    <td><input type="text" name="email" id="email" class="ibox w300"  value="$author_email"/></td>
  </tr>
  <tr>
    <td>$m[author_site]</td>
    <td><input type="text" name="link" id="link" class="ibox w300" value="$author_site" /></td>
  </tr>
  <tr>
    <td>$m[base_class_name] *</td>
    <td><input type="text" name="plg_basename" id="plg_basename" class="ibox w100" onkeyup="waPluginator.baseKeyUp()" onchange="waPluginator.restoreOpt(this)"/></td>
  </tr>
  <tr>
    <td>$m[plugin_folder] *</td>
    <td><input type="text" name="plg_folder" id="plg_folder" class="ibox w100" /></td>
  </tr>
  <tr>
    <td>$m[plugin_type] *</td>
    <td><select name="plugintype" id="plugintype" style="width:300px" onchange="waPluginator.chgPlgtype()">
    <option value="">---</option>
    $plgOptions</select>
    </td>
  </tr>
  <tr>
    <td>$m[main_title]</td>
    <td><input type="text" name="plg_title" id="plg_title" class="ibox w300" /></td>
  </tr>
  <tr>
    <td>$m[description]</td>
    <td><input type="text" name="description" id="description" class="ibox w300" /></td>
  </tr>
  <tr>
    <td>$m[acl_list]</td>
    <td><input type="text" name="plg_rights" id="plg_rights" class="ibox w300" onchange="waPluginator.chgRights=true"/></td>
  </tr>
  <tr id="tr_colorSchemes" style="display:none">
    <td>$m[color_scheme]</td>
    <td><select name="_color_cscheme_" id="_color_cscheme_" style="min-width:200px"><option value=''>---</option>
    $colorshemeOptions
    </select></td>
  </tr>
  $var_blocks
  <tr>
    <td><label for="plg_createbkend">$m[create_bkend]</label></td>
    <td><input type="checkbox" name="plg_createbkend" id="plg_createbkend" /></td>
  </tr>
  <tr>
    <td><label for="plg_createjs">$m[create_js]</label></td>
    <td><input type="checkbox" name="plg_createjs" id="plg_createjs" /></td>
  </tr>
  <tr>
    <td>$m[create_locales]</td>
    <td>$str_langs </td>
  </tr>

  <tr>
    <td colspan="2">
     <input type="button" class="button w200" value="$m[btn_generate]" onclick="waPluginator.generate()" id="waBtnStart" disabled="disabled"/>
     <input type="button" class="button w200" value="$m[btn_reverseing]" onclick="waPluginator.startReverse()" id="waBtnReverse" disabled="disabled"/>
    </td>
  </tr>
</table>
</div>
</form>
<div class="div_outline" style="width:500px; height:400px; position:relative;float:left; background:#eef">
    <div style="padding: 4px 0; border:1px solid #aaf; margin-bottom:0.4em;">$m[title_log]</div>
    <div id="wapluginator_result" style="max-height:350px; overflow:auto; text-align:left; padding: 2px 8px">
    </div>
</div>

EOHTM;
        if ($buffered) return $ret;
        echo $ret;
    }

    /**
    * Performs request AJAXed from client
    *
    * @param mixed $params
    */
    public static function performAction($params=false) {
        if(!is_array($params)) $params = decodePostData(1);
        self::$_p = $params;
        $ret = 'undefined action';
        $action = isset($params['action']) ? $params['action'] : '';
        if (method_exists('WaPluginator', $action)) $ret = self::$action();
        else  switch($action) {
            case 'generate':
                $ret = self::generateAll();
                break;
#            case 'reverse_template':
        }
        exit($ret);
    }

    public static function restoreOptions() {
        $plg_basename = isset(self::$_p['plg_basename']) ? trim(self::$_p['plg_basename']) : '';
        $ret = '1';
        if ($plg_basename) {
            $memoFile = self::$FOLDER_ROOT . self::$FOLDER_CFG . 'waPluginator.data';
            if (is_file($memoFile)) {
                $curmem = unserialize(file_get_contents($memoFile));
                if (isset($curmem[$plg_basename])) foreach ($curmem[$plg_basename] as $k=>$v) {
                    $ret .= "\tset\f$k\f$v";
                }
            }
        }
        exit(encodeResponseData($ret));
    }

    public static function setOptions($opts) {
        if (is_array($opts)) self::$_options = $opts;
    }
    public static function generateAll() {

        try {
            if (!class_exists('CodePreprocessor'))
                @include_once('class.codePreprocessor.php');
        	self::$preproc = new CodePreprocessor;
		}
		catch (Exception $e) { };

        if (!class_exists('CodePreprocessor')) die ('class.codePreprocessor.php not found. Process aborted');
        self::$preproc -> setLF('windows') -> setSourceFolder(__DIR__ . '/');

        self::loadConfig();
#        WriteDebugInfo('params: ', self::$_p);
        self::$_result = '';

        $plg_basename = isset(self::$_p['plg_basename']) ? trim(self::$_p['plg_basename']) : '';
        $plugintype   = isset(self::$_p['plugintype']) ? trim(self::$_p['plugintype']) : '';
        $locls = strtolower($plg_basename);
        $plg_folder = isset(self::$_p['plg_folder']) ? trim(self::$_p['plg_folder']) : '';
        $mainFile = $basefilename = $plg_folder;
        if($plg_basename === '' OR $plg_folder === '') return 'Empty base name OR plugin file/folder name !';
        if(substr($plg_folder, -1) !=='/') $plg_folder .= '/';
/*
        if (!empty(self::$_p['__outfolder__']))
            $subFolder = self::$_options['__outfolder__'];
        else
*/
        $subFolder = self::$FOLDER_ROOT . self::$FOLDER_OUTPUT . $plg_folder;

        # update current params into "created plugins memory" file
        $fapp = self::$FOLDER_ROOT . self::$FOLDER_CFG;
        if (!is_dir($fapp)) mkdir($fapp,077,true);
        $memoFile = $fapp . 'waPluginator.data';
        if (is_file($memoFile)) {
            $curmem = unserialize(file_get_contents($memoFile));
        }
        if (!isset($curmem[$plg_basename])) $curmem[$plg_basename] = array();
        $curmem[$plg_basename] = self::$_p;
        file_put_contents($memoFile, serialize($curmem));

        $os = strtoupper(PHP_OS);
        $CRLF = (substr($os,0,3) === 'WIN') ? "\r\n" : "\n";
        if(!is_dir($subFolder)) {
            $mk = @mkdir($subFolder,0777,true);
            if(!$mk) {
            	self::addError(self::$_msg['err_create_folder'].' : '.$subFolder);
            	return self::$_result;
			}
        }

        if (!empty(self::$_p['description'])) self::$_options['description'] = self::$_p['description'];
        if (!empty(self::$_p['author'])) self::$_options['author'] = self::$_p['author'];
        if (!empty(self::$_p['email']))  self::$_options['email'] = self::$_p['email'];
        if (!empty(self::$_p['plg_rights'])) self::$_options['plg_rights'] = self::$_p['plg_rights'];
        if (!empty(self::$_p['plg_title'])) self::$_options['plg_title'] = self::$_p['plg_title'];
        if (!empty(self::$_p['plg_createbkend'])) self::$_options['plg_createbkend'] = 1;
        if (!empty(self::$_p['plg_createjs']))    self::$_options['plg_createjs'] = 1;

        $mainFile =  self::$FOLDER_ROOT . self::$FOLDER_OUTPUT . $locls . '.php';
        $backendFile =  $subFolder . 'backend.php';

        $tplname_main    = __DIR__ ."/$plugintype.main.txt";
        $tplname_backend = __DIR__ ."/$plugintype.backend.txt";
        $tplname_js      = __DIR__ ."/$plugintype.js.txt";

        self::$subst = array(
            '%appname%' => (isset(self::$_options['appname']) ? self::$_options['appname'] : 'Noname')
           ,'%description%' => (isset(self::$_options['description']) ? self::$_options['description'] : '')
           ,'%author%' => (isset(self::$_options['author']) ? self::$_options['author'] : 'Author here')
           ,'%email%' => (isset(self::$_options['email']) ? self::$_options['email'] : '')
           ,'%link%' => (isset(self::$_options['link']) ? self::$_options['link'] : 'no link')
           ,'%date%' => date('Y-m-d')
           ,'%classname%' => $plg_basename
           ,'%lowclassname%' => $locls
           ,'%my_folder%' => $plg_folder
           ,'%plg_rights%' => (isset(self::$_options['plg_rights']) ? self::$_options['plg_rights'] : $locls)
           ,'%plg_title%' => (isset(self::$_options['plg_title']) ? self::$_options['plg_title'] : $locls)
		   # predefined macros for current date elements:
           ,'%_curdate_year_%' => date('Y')
           ,'%_curdate_month_%' => date('m')
           ,'%_curdate_day_%' => date('d')

        );
        if (empty(self::$subst['%description%']) && !empty(self::$_options['plg_title'])) self::$subst['%description%'] = self::$_options['plg_title'];

        // Add user entered parameters values for selected plugin type
        self::$parsvars = array();
        if ( isset(self::$_plgtypes[$plugintype]['vars']) && count(self::$_plgtypes[$plugintype]['vars']>0) )
            foreach( self::$_plgtypes[$plugintype]['vars'] as $varid => $vobj ) {
                $pname = 'input_'.$plugintype.'_'.$varid;
                $value = isset(self::$_p[$pname])? self::$_p[$pname] : '';
                if ($vobj['type'] === 'checkbox') $value = $value ? 'TRUE':'FALSE';
                self::$subst["%$varid%"] = self::$parsvars[$varid] = $value;
        }

        self::$_scheme = isset(self::$_p['_color_cscheme_']) ? (self::$_p['_color_cscheme_']) : '';
        self::$_useSchemes = self::$_plgtypes[$plugintype]['colorschemes'];
#        exit($plugintype . ', color scheme:' . self::$_scheme . ', all plgtype:<pre>' . print_r(self::$_plgtypes[$plugintype],1).'</pre>'); // debug

        self::$parsvars = array_merge(self::$parsvars, self::$_options);

        self::$_result = '<table class="zebra">';

        if (self::  $_useSchemes && self::$_scheme!='' && isset(self::$_schemes[self::$_scheme])) {
            self::$_clrsubst = array();
        	foreach(self::$_schemes[self::$_scheme] as $itemid => $value) {
				self::$_clrsubst['{'.$itemid.'}'] = $value;
			}
			self::$subst = array_merge(self::$subst, self::$_clrsubst);
#			WriteDebugInfo("colors in chosen scheme:",self::$_clrsubst);
		}
        # 1. Create plugins/plg_name.php self::$_plgtypes[$plugintype]['files']
        if (is_file($tplname_main)) {
            $body = @file_get_contents($tplname_main);
            $body = self::$preproc->parse($body, self::$parsvars);
            self::$subst['%filename%'] = $mainFile;
            $body = str_replace(array_keys(self::$subst), array_values(self::$subst), $body);
            self::_saveNewFile($mainFile, $body);
        }
        if (!empty(self::$_options['plg_createbkend']) && is_file($tplname_backend)) {
            # 2. Create plugins/subfolder/backend.php
            $body = @file_get_contents($tplname_backend);
            $body = self::$preproc->parse($body, self::$parsvars);
            self::$subst['%filename%'] = $backendFile;
            $body = str_replace(array_keys(self::$subst), array_values(self::$subst), $body);
            self::_saveNewFile($backendFile, $body);  # file_put_contents($backendFile, $body);
        }
        if (!empty(self::$_options['plg_createjs']) && is_file($tplname_js)) {
            # 3. Create plugins/subfolder/%module_name%.js
            $body = @file_get_contents($tplname_js);
            $body = self::$preproc->parse($body, self::$parsvars);
            $jsFile = $subFolder . $locls . '.js';
            self::$subst['%filename%'] = $jsFile;
            $body = str_replace(array_keys(self::$subst), array_values(self::$subst), $body);
            self::_saveNewFile($jsFile, $body);  # file_put_contents($backendFile, $body);
        }
        # 3. Create additional files if exist in config
        $var_pref = 'input_'.$plugintype.'_';
        if (!empty(self::$_plgtypes[$plugintype]['files']) && count(self::$_plgtypes[$plugintype]['files'])>0) {

            foreach (self::$_plgtypes[$plugintype]['files'] as $pfile) {

                if ( isset($pfile['makeif']) && !empty($pfile['makeif'])) {
                    $prmname = $var_pref . $pfile['makeif'];
                    $doit = isset(self::$_p[$prmname]) ? self::$_p[$prmname] : FALSE;
                    if (!$doit) continue;
                }
                $src = __DIR__ . '/'. $pfile['src'];
                if (!is_file($src)) {
                    self::$_result .= "<tr><td colspan=2>Template File skipped: $pfile[src]</td></tr>";
                    continue;
                }
                $dest = $subFolder . str_replace('%plugin%',$locls, $pfile['name']);
                $dirOk = true;
                $destdir = dirname($dest);
                if (!is_dir($destdir)) $dirOk = mkdir($destdir,077,true);
                if (!$dirOk) continue;
                $is_less = (substr($pfile['src'],-5) === '.less') || (substr($pfile['src'],-9) === '.less.txt');
                $is_scss = (substr($pfile['src'],-5) === '.scss') || (substr($pfile['src'],-9) === '.scss.txt');
                $body = file_get_contents($src);

                $body = self::$preproc->parse($body, self::$parsvars); # perform #IF .. #ELSE .. #ENDIF #INCLUDE macros

                self::$subst['%filename%'] = $dest;
                $body = str_replace(array_keys(self::$subst), array_values(self::$subst), $body);
                if ($is_scss) { // Compile scss to CSS file !
                	self::compileScss($body, $dest);

                }
                if ($is_less) { // Compile LESS to CSS file !
                    self::compileLess($body, $dest);
                }
                self::_saveNewFile($dest, $body);  # file_put_contents($backendFile, $body);
            }
        }
        # 4. i18n language file stubs
        foreach (self::$_langs as $key => $name) {
            if(!empty(self::$_p['locale_'.$key])) {
                $lng_tpl = __DIR__ . "/$plugintype.i18n.$key.txt";
                if (is_readable($lng_tpl)) $body = file_get_contents($lng_tpl);
                else $body = <<< EOTXT
<?php
/**
* Localization module for plugin $plg_basename.php (class %classname%)
* Language: $name
* %description%
* created: %date%
*/
return array(
   '$locls:main_title' => '%plg_title%'
);
EOTXT;
                $body = self::$preproc->parse($body, self::$parsvars);
                $body = str_replace(array_keys(self::$subst), array_values(self::$subst), $body);
#                if(!is_dir(self::$FOLDER_ROOT . self::$FOLDER_I18N . $key)) mkdir(self::$FOLDER_ROOT . self::$FOLDER_I18N . $key, 0777,true);
                $langFname = $subFolder . "strings.$key.php";
                self::_saveNewFile($langFname, $body);
            }
        }

        # 5. If srcarchive set, handle zip with all templates
        if (!empty(self::$_plgtypes[$plugintype]['srcarchive']) && is_file(__DIR__ . DIRECTORY_SEPARATOR . self::$_plgtypes[$plugintype]['srcarchive'])) {
            $zipFile = __DIR__ . DIRECTORY_SEPARATOR . self::$_plgtypes[$plugintype]['srcarchive'];
            self::addLog(self::$_msg['creating_from'] . ' '. self::$_plgtypes[$plugintype]['srcarchive']);
            $tmpfold = self::$FOLDER_TMP . 'tmp-' . date('His') . rand(1000,9000);
            mkdir($tmpfold, 077, true);
            if (is_dir($tmpfold)) {
                $srczip = new ZipArchive();
                $opened = $srczip->open($zipFile);
                if($opened === true) {
	                $srczip->extractTo($tmpfold);
	                self::processFolder($tmpfold, $subFolder);
	                self::delTree($tmpfold); # tmp cleanup from source files
				}
				else
                {
                	self::addError(basename($zipFile) . ' : ' . self::$_msg['err_zipopen'] );
				}
            } else
                self::addError('tmp sub-folder creation error');
        } else
            self::addError(self::$_msg[''] . ': ' . __DIR__ . DIRECTORY_SEPARATOR . self::$_plgtypes[$plugintype]['srcarchive']);

        self::addLog(self::$_msg['job_finished']);
        return self::$_result;
    }

    public static function processFolder($dirFrom, $dirTo) {

        $files = array_diff(scandir($dirFrom), array('.','..'));
        foreach($files as $fname) {
            if (is_dir("$dirFrom/$fname")) {
                if (!is_dir($dirTo . $fname)) @mkdir($dirTo . $fname);
                if (is_dir($dirTo . $fname))
                    self::processFolder("$dirFrom/$fname", "$dirTo{$fname}/");
                else
                    self::addError("Folder create error: ".$dirTo . $fname);
            }
            else
                self::processOneFile("$dirFrom/$fname", $dirTo . $fname);
        }
    }

    private static function processOneFile($fileSrc, $fileDest) {
        $point = strrpos($fileSrc, '.');
        $ext = ($point!==false) ? strtolower(substr($fileSrc, $point+1)) : '';

        if (($ext) && (in_array($ext, self::$TYPES_TXT))) {

	        $is_scss = ($ext === 'scss');
	        $is_less = ($ext === 'less');

	        if ($is_scss || $is_less) { // Use sass parser and change destination file name to css
	            $oldfn = $fileDest;
        		$fileDest = substr($fileDest,0,$point+2) . 'css';
        		WriteDebugInfo("Changed filename $oldfn to $fileDest");
			}

            self::$subst['%filename%'] = $fileDest;
            $body = @file_get_contents($fileSrc);
            $body = self::$preproc->parse($body, self::$parsvars);
            $body = str_replace(array_keys(self::$subst), array_values(self::$subst), $body);

            if ($is_scss) {
                self::compileScss($body, $fileDest);
            }
            if ($is_less) {
                self::compileLess($body, $fileDest);
            }

            self::_saveNewFile($fileDest, $body);  # file_put_contents($backendFile, $body);
        }
        else {
            copy($fileSrc, $fileDest);
            self::addLog($fileDest, ' '. self::$_msg['size'] . ': '.number_format(filesize($fileDest),0,'.',' '));
        }
    }
    public static function makeCssAttrib($par) {
		$fval = floatval ($par);
		$ret =(("$fval" === $par) ? ($par . 'px') : $par);
#		WriteDebugInfo("float val: $fval, $par => $ret");
		return $ret;
    }

    public static function compileScss(&$body, $srcname) {

    	if (!self::$scss_comp) {
            try {
                if (!class_exists('Leafo\ScssPhp\Compiler'))
                    @include_once('scssphp/scss.inc.php');
				if (class_exists('Leafo\ScssPhp\Compiler'))
	            	self::$scss_comp = new Leafo\ScssPhp\Compiler();
	            else {
					self::$scss_comp = 1;
					self::addError('scssphp/scss.inc.php not found, SCSS compilation impossible');
				}
			}
			catch(Exception $e) {
				self::$scss_comp = 1;
				self::addError('scssphp/scss.inc.php not found, SCSS compilation impossible');
			}
		}

    	if (is_object(self::$scss_comp)) {
            try {
                $orig_body = $body;
                $body = self::$scss_comp->compile($body);
                file_put_contents("_compiled.css", $body);
                self::addLog(self::$_msg['compile_ok']. ' : ' . $srcname);
            } catch (exception $e) {
                self::addError(self::$_msg['err_bad_scss']. ' : ' . $srcname);
                self::addError($e->getMessage());
                $body = $orig_body; # back to original content
            }

		}
	}

    public static function compileLess(&$body, $srcname) {

    	if (!self::$less_comp) {
    		try {
                if (!class_exists('lessc'))
                    @include_once('lessc.inc.php');
	            if (class_exists('lessc'))
	            	self::$less_comp = new lessc;
	            else {
	            	self::$less_comp = 1;
					self::addError('lessc.inc.php not found, LESS compilation impossible');
				}
			}
			catch(Exception $e) {
				self::$less_comp  = 1;
				self::addError('lessc.inc.php not found, LESS compilation impossible');
			}
		}

    	if (is_object(self::$less_comp)) {
            try {
                $orig_body = $body;
                $body = self::$less_comp->compile($body);
                self::addLog(self::$_msg['compile_ok']. ' : ' . $srcname);
            } catch (exception $e) {
                self::addError(self::$_msg['err_bad_less'] . ' : '. $srcname);
                self::addError($e->getMessage());
                $body = $orig_body; # back to original content
            }
		}
	}

    private static function addLog($strk1,$strk2='') {
        self::$_result .= '<tr><td>' . date('H:i:s');
        if ($strk2) self::$_result .= "<td><b>$strk1</b></td><td>$strk2</td</tr>";
        else        self::$_result .=  "<td colspan=\"2\">$strk1</td></tr>";
    }
    private static function addError($strk1) {
        self::$_result .= '<tr style="background:#fedddd; color:#900"><td>' . date('H:i:s')
        	. "</td><td colspan='2'>$strk1</td></tr>";
	}

    public static function reverse_template() {

        self::loadConfig();
        self::$_result = '';
        $templateid = isset(self::$_p['templateid']) ? trim(self::$_p['templateid']) : 'noname_template';
        $fromplugin = isset(self::$_p['plugin']) ? trim(self::$_p['plugin']) : '';

        # 1) main plugin file. Stop if it does not exist !
        $baseFile = self::$FOLDER_OUTPUT . "$fromplugin.php";
        if (!file_exists($baseFile)) {
            self::$_result = "ERR|Plugin file $baseFile not found,<br>process rejected";
            return self::$_result;
        }

        self::$_result = '<table class="zebra">';

        self::$subst = array($fromplugin => '%lowclassname%');

        $destName = __DIR__ . "/$templateid.main.txt";
        $body = str_replace(array_keys(self::$subst), array_values(self::$subst), file_get_contents($baseFile));
        self::_saveNewFile($destName, $body, TRUE);

        # 2) backend file...
        $fromFile = self::$FOLDER_OUTPUT . "$fromplugin/backend.php";
        if (is_file($fromFile)) {
            $destName = __DIR__ . "/$templateid.backend.txt";
            $body = str_replace(array_keys(self::$subst), array_values(self::$subst), file_get_contents($fromFile));
            self::_saveNewFile($destName, $body, TRUE);
        }
        # 3) js file...
        $fromFile = self::$FOLDER_OUTPUT . "$fromplugin/$fromplugin.js";
        if (is_file($fromFile)) {
            $destName = __DIR__ . "/$templateid.js.txt";
            $body = str_replace(array_keys(self::$subst), array_values(self::$subst), file_get_contents($fromFile));
            self::_saveNewFile($destName, $body, TRUE);
        }

        # TODO: handle all other files in plugins/{templateid} folder (tpl, thml ...)

        self::$_result .= '<tr><td>' . date('H:i:s') . '<td colspan="2">Job done</td></tr></table>';
        return self::$_result;

    }
    private static function _saveNewFile($filename, $contents, $saveback=false) {
        $folder = dirname($filename) .'/';
        $justfile = substr($filename, strlen($folder));
        if ( (self::$_saveBak || $saveback) && file_exists($folder . $justfile) && (md5_file($folder . $justfile) != md5($contents)) ) {
            $bakname = $filename . '.bak';
            if(file_exists($bakname)) @unlink($bakname);
            rename($filename, $bakname);
        }
        file_put_contents($filename, $contents);
        self::addLog($filename, ' ' . self::$_msg['size'] . ': ' . number_format(filesize($filename),0,'.',' '));
    }
}
function toMainCset($value) {
    $cset = defined('MAINCHARSET') ? MAINCHARSET : 'UTF-8';
#    WriteDebugInfo('dest cset:',$cset, ' src string: ', $value);
    if (strtoupper($cset) !== 'UTF-8')
        return @iconv('UTF-8',$cset, $value);
    else
        return $value;
}
