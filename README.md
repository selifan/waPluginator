# Code generator for modules, plugins and landing pages

Majority of web applications, CMS systems, frameworks uses "modular" (or plugin) architecture. 
It allows easy developing of new functionality and adding it to the system.
Quite often modules, or plugins, have a similar structure, so it would be great to have a kind of "template" with places for inserting specific "variable" values, 
and then create "initial stub" for a new module with these values inside. After that developer can modify generated files, adding needed specific (function implementation, style sheets etc.)

Another often task is creating a collection of files for a "single page" - for example, adaptive landing page, based on predefined design.

For example, you want to create a landing page based on some of royalty free pages available in internet, but you don't want each time to search places where to insert your titles, text blocks, css color codes etc. 

In that case you just modify source page files, by adding special macros (like `%textblock01%`), register them in waPluginator,
and next time, when you press "generate", new files will be created with your values, entered in generator's "designer" form.


## Installing
* Download current version of waPluginator and install it in current folder of your project or one of folders listed in "include_path"
* If you're planning to use scss source files and want them to be compiled to css, you have to download current version of scssphp PHP library from [github](https://github.com/leafo/scssphp) and install it into scssphp subfolder in one of folders listed in "include_path", so this code will work: `include_once("scssphp/scss.inc.php")`
* If you're planning to use less source files and want them to be compiled to css, you have to download current version of lessc.inc.php library from [github](https://github.com/leafo/lessphp) and copy it in one of folders listed in "include_path", so this code will work: `include_once("lessc.inc.php")`
* In your code add a command `include_once("waPluginator/waPluginator.php")`
* Don't forget to add one of **jquery** versions in your html code (any version from 1.10 to 2.x will do)

## Using example
```php
        include_once('waPluginator/waPluginator.php');
        waPluginator::setBaseUri('./yourpage.php');
        waPluginator::autoLocalize();
        waPluginator::setOptions(array(
                'appname' =>'Your application name'
               ,'author' =>'My Name'
               ,'email' =>'Myemail [at] acme.com'
               ,'link' =>'http://www.yoursite.com'
            )
        );
        $params = array_merge($_GET,$_POST);
        if(!empty($params['action'])) {
            waPluginator::performAction($params);
            exit;
        }
        else {
            app::setPageTitle('Plugin generator');
            app::appendHtml('<br>');
            app::appendHtml(waPluginator::designerForm(true));
            app::finalize();
        }
```

Collection of prepared template files, images, font files etc. can be packed in one zip file or placed in a sub-direcory of waPluginator directory, 
and described in the main [configuration XML file](https://github.com/selifan/waPluginator/wiki/waConfigurator.xml-file-structure).

Working demo can be found in [demo](demo/) folder - [generator.php](demo/generator.php)

See using details in [wiki](https://github.com/selifan/waPluginator/wiki/)
