# Code generator for modules, plugins and landing pages

Majority of web applications, CMS systems, frameworks use "modular" (or plugin) architecture. 
It allows easy developing of new functionality and adding it to the system independently from other modules.
Quite often modules, or plugins, have a similar structure, so it would be great to have a kind of "template" with places for inserting specific "variable" values, 
and then create "initial stub" for a new module with these values inside. After that developer can modify generated files, adding needed specific (function implementation, CSS styling etc.)

Another often task is creating a collection of files for a "single page" - for example, adaptive landing page, based on predefined design.

For example, you want to create a landing page based on some royalty free pages available in internet, but you don't want each time to search places where to insert your titles, text blocks, css color codes etc. 

In that case you just modify source page files, by adding special macros (like `%textblock01%`), and describe them in waPluginator config file.

waPluginator renders an html "designer" form for choosing template, entering user values and executing the "module creation process".

## Using example

```php
include_once('waPluginator/waPluginator.php');
// waPluginator::setBaseUri('./backend.php'); // Use it if you have your own backend module
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

**waPluginator** uses [CodePreprocessor](https://github.com/selifan/CodePreprocessor) to parse template files, 
but it allows attaching any additional text/source code processors (compilers), like "scss to css", "less to css" etc.
You can attach compiler if it is implemented as PHP class with callable "compiling" method.
 
Working demo can be found in [demo](demo/) folder - [generator.php](demo/generator.php)

See using details in [wiki](https://github.com/selifan/waPluginator/wiki/)
