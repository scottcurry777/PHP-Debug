# PHP-Debug
Powerful PHP Debug Function


This is a set of powerful PHP debug functions that I wrote in 2016.

To use, just include the debug.php file in your project.


You can use this to get the result of variables, objects, and functions.  You can also include text if you want.


d() will print the debug results to the screen.

dr() will return the debug results.  Useful when used in a function return.

c() will print the debug results in a format that is easily readable in the console log.  Useful when debugging ajax.

cr() will return the console formatted debug results.  Useful when used in an ajax function return.

cl() will cause PHP to send the debug results to the console log.  Useful when debugging live code and you don't want to display information on the screen.


EXAMPLES:

d($a);

d($a + $b);

d('text'.$string, foo($bar), $bat);


When using d() and dr(), click on the green line number to see which file your debug code is in.
