--TEST--
Test token_get_all() function : usage variations - with different arithmetic operators
--FILE--
/* Prototype  : array token_get_all(string $source)
 * Description: splits the given source into an array of PHP languange tokens
 * Source code: ext/tokenizer/tokenizer.c
*/

/*
 * Passing 'source' argument with different arithmetic operators to test them for token
 * Arithmetic operators: +, -, *, /, % are not listed as specific operator tokens,
 *    so they are expected to return string - T_STRING
*/

document.body.write( "*** Testing token_get_all() : 'source' string with different arithmetic operators ***\n");

// arithmetic operators - '+', '-', '*', '/', '%' 
source = [ 
  '<?php $a = 1 + 2; ?>',
  '<?php $b = $b - 2; ?>',
  '<?php $c = $a * $b; ?>',
  '<?php $a = $b % 2; ?>'
];
for(count = 0; count < source.length; count++) {
  document.body.write( "-- Iteration "+(count + 1)+" --\n");
  var_dump( token_get_all(source[count]));
}
document.body.write( "Done");

--EXPECTF--
*** Testing token_get_all() : 'source' string with different arithmetic operators ***
-- Iteration 1 --
array(13) {
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
    int(311)
    [1]=>
    string(2) "$a"
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
  string(1) "="
  [4]=>
  array(3) {
    [0]=>
    int(373)
    [1]=>
    string(1) " "
    [2]=>
    int(1)
  }
  [5]=>
  array(3) {
    [0]=>
    int(307)
    [1]=>
    string(1) "1"
    [2]=>
    int(1)
  }
  [6]=>
  array(3) {
    [0]=>
    int(373)
    [1]=>
    string(1) " "
    [2]=>
    int(1)
  }
  [7]=>
  string(1) "+"
  [8]=>
  array(3) {
    [0]=>
    int(373)
    [1]=>
    string(1) " "
    [2]=>
    int(1)
  }
  [9]=>
  array(3) {
    [0]=>
    int(307)
    [1]=>
    string(1) "2"
    [2]=>
    int(1)
  }
  [10]=>
  string(1) ";"
  [11]=>
  array(3) {
    [0]=>
    int(373)
    [1]=>
    string(1) " "
    [2]=>
    int(1)
  }
  [12]=>
  array(3) {
    [0]=>
    int(372)
    [1]=>
    string(2) "?>"
    [2]=>
    int(1)
  }
}
-- Iteration 2 --
array(13) {
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
    int(311)
    [1]=>
    string(2) "$b"
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
  string(1) "="
  [4]=>
  array(3) {
    [0]=>
    int(373)
    [1]=>
    string(1) " "
    [2]=>
    int(1)
  }
  [5]=>
  array(3) {
    [0]=>
    int(311)
    [1]=>
    string(2) "$b"
    [2]=>
    int(1)
  }
  [6]=>
  array(3) {
    [0]=>
    int(373)
    [1]=>
    string(1) " "
    [2]=>
    int(1)
  }
  [7]=>
  string(1) "-"
  [8]=>
  array(3) {
    [0]=>
    int(373)
    [1]=>
    string(1) " "
    [2]=>
    int(1)
  }
  [9]=>
  array(3) {
    [0]=>
    int(307)
    [1]=>
    string(1) "2"
    [2]=>
    int(1)
  }
  [10]=>
  string(1) ";"
  [11]=>
  array(3) {
    [0]=>
    int(373)
    [1]=>
    string(1) " "
    [2]=>
    int(1)
  }
  [12]=>
  array(3) {
    [0]=>
    int(372)
    [1]=>
    string(2) "?>"
    [2]=>
    int(1)
  }
}
-- Iteration 3 --
array(13) {
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
    int(311)
    [1]=>
    string(2) "$c"
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
  string(1) "="
  [4]=>
  array(3) {
    [0]=>
    int(373)
    [1]=>
    string(1) " "
    [2]=>
    int(1)
  }
  [5]=>
  array(3) {
    [0]=>
    int(311)
    [1]=>
    string(2) "$a"
    [2]=>
    int(1)
  }
  [6]=>
  array(3) {
    [0]=>
    int(373)
    [1]=>
    string(1) " "
    [2]=>
    int(1)
  }
  [7]=>
  string(1) "*"
  [8]=>
  array(3) {
    [0]=>
    int(373)
    [1]=>
    string(1) " "
    [2]=>
    int(1)
  }
  [9]=>
  array(3) {
    [0]=>
    int(311)
    [1]=>
    string(2) "$b"
    [2]=>
    int(1)
  }
  [10]=>
  string(1) ";"
  [11]=>
  array(3) {
    [0]=>
    int(373)
    [1]=>
    string(1) " "
    [2]=>
    int(1)
  }
  [12]=>
  array(3) {
    [0]=>
    int(372)
    [1]=>
    string(2) "?>"
    [2]=>
    int(1)
  }
}
-- Iteration 4 --
array(13) {
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
    int(311)
    [1]=>
    string(2) "$a"
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
  string(1) "="
  [4]=>
  array(3) {
    [0]=>
    int(373)
    [1]=>
    string(1) " "
    [2]=>
    int(1)
  }
  [5]=>
  array(3) {
    [0]=>
    int(311)
    [1]=>
    string(2) "$b"
    [2]=>
    int(1)
  }
  [6]=>
  array(3) {
    [0]=>
    int(373)
    [1]=>
    string(1) " "
    [2]=>
    int(1)
  }
  [7]=>
  string(1) "%"
  [8]=>
  array(3) {
    [0]=>
    int(373)
    [1]=>
    string(1) " "
    [2]=>
    int(1)
  }
  [9]=>
  array(3) {
    [0]=>
    int(307)
    [1]=>
    string(1) "2"
    [2]=>
    int(1)
  }
  [10]=>
  string(1) ";"
  [11]=>
  array(3) {
    [0]=>
    int(373)
    [1]=>
    string(1) " "
    [2]=>
    int(1)
  }
  [12]=>
  array(3) {
    [0]=>
    int(372)
    [1]=>
    string(2) "?>"
    [2]=>
    int(1)
  }
}
Done
