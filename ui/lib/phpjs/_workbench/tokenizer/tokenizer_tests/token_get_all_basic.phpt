--TEST--
Test token_get_all() function : basic functionality 
--FILE--
/* Prototype  : array token_get_all(string $source)
 * Description : splits the given source into an array of PHP languange tokens
 * Source code: ext/tokenizer/tokenizer.c
*/

document.body.write( "*** Testing token_get_all() : basic functionality ***\n");

// with php open/close tags
source = '<?php echo "Hello World"; ?>';
document.body.write( "-- source string with PHP open and close tags --\n");
var_dump( token_get_all(source) );

// without php open/close tags testing for T_INLINE_HTML
source = "echo 'Hello World';";
document.body.write( "-- source string without PHP open and close tags --\n");
var_dump( token_get_all(source) );

document.body.write( "Done");

--EXPECTF--
*** Testing token_get_all() : basic functionality ***
-- source string with PHP open and close tags --
array(7) {
  [0]=>
  array(3) {
    [0]=>
    int(370)
    [1]=>
    string(6) "<?php "
    [2]=>
    int(1)
  }
  [1]=>
  array(3) {
    [0]=>
    int(318)
    [1]=>
    string(4) "echo"
    [2]=>
    int(1)
  }
  [2]=>
  array(3) {
    [0]=>
    int(373)
    [1]=>
    string(1) " "
    [2]=>
    int(1)
  }
  [3]=>
  array(3) {
    [0]=>
    int(317)
    [1]=>
    string(13) ""Hello World""
    [2]=>
    int(1)
  }
  [4]=>
  string(1) ";"
  [5]=>
  array(3) {
    [0]=>
    int(373)
    [1]=>
    string(1) " "
    [2]=>
    int(1)
  }
  [6]=>
  array(3) {
    [0]=>
    int(372)
    [1]=>
    string(2) "?>"
    [2]=>
    int(1)
  }
}
-- source string without PHP open and close tags --
array(1) {
  [0]=>
  array(3) {
    [0]=>
    int(313)
    [1]=>
    string(19) "echo 'Hello World';"
    [2]=>
    int(1)
  }
}
Done
