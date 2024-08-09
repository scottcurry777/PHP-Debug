# PHP-Debug
Powerful PHP Debug Function

-------------------------------------------------------------------
WARNING! DESIGNED FOR PHP 5.6. MAY NOT WORK WITH PHP 7.1 AND ABOVE.
-------------------------------------------------------------------

This is a set of powerful PHP debug functions that I wrote in 2016.<br>
To use, just include the debug.php file in your project.

You can use this to get the result of variables, objects, and functions.  You can also include text if you want.

d() will print the debug results to the screen.<br>
dr() will return the debug results.  Useful when used in a function return.<br>
c() will print the debug results in a format that is easily readable in the console log.  Useful when debugging ajax.<br>
cr() will return the console formatted debug results.  Useful when used in an ajax function return.<br>
cl() will cause PHP to send the debug results to the console log.  Useful when debugging live code and you don't want to display information on the screen.

Use commas to seperate different debug items in the same function.<br>
You can also call these functions across multiple lines.

EXAMPLES:<br>
d($a);<br>
d($a + $b);<br>
d($a, $b);<br>
d('text'.$string, foo($bar), $bat);<br>
d($a,<br>
&nbsp;&nbsp;$b,<br>
&nbsp;&nbsp;foo($bar)<br>
);

When using d() and dr(), click on the green line number to see which file your debug code is in.
