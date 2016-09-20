# Source code generator for modules, plugins and landing pages
Majority of web applications, CMS systems, frameworks uses "modular" (or pligin) architecture. 
It allows easy developing of new functionality and adding it to the system.
Quite often modules, or plugins, have a similar structure, so it would be great to have a king of "template" with places for inserting specific "variable" values, 
and then create "initial stub" for a new module with these values. after that developer can modify generated source files, adding needed specific
(function implementation, image files, style sheets etc.)

Another often task is creating a collection of files for a "single page" - for example, adaptive landing page, based on predefined design.
Suppose, you want to create a landing page based on some of royalty free pages available in internet, but you don't want each time to search
places where to insert your text block, css color codes etc. 
In that case you just modify source page files, by adding special macros (like `%textblock01%`),
and they will be filled with your values, entered in generator's "designer" form.

This library was written to help in creating plugin stubs for web applications, and easify creating of multiple landing pages.

## Installing
* Download current version of waPluginator and install it in current folder of your project or one of folders listed in "include_path"
* If you're planning to use scss source files and want them to be compiled to css, you have to download current version of scssphp PHP library from [github](https://github.com/leafo/scssphp) and install it into scssphp subfolder in one of folders listed in "include_path", so this code will work: `include_once("scssphp/scss.inc.php")`
* If you're planning to use less source files and want them to be compiled to css, you have to download current version of lessc.inc.php library from [github](https://github.com/leafo/lessphp) and copy it in one of folders listed in "include_path", so this code will work: `include_once("lessc.inc.php")`

