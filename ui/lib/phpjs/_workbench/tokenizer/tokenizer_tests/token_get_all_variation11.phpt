--TEST--
Test token_get_all() function : usage variations - with control structure tokens
--FILE--
/* Prototype  : array token_get_all(string $source)
 * Description: splits the given source into an array of PHP languange tokens
 * Source code: ext/tokenizer/tokenizer.c
*/

/*
 * Using different control structure keywords 
 *   if..else, elseif - T_IF(303), T_ELSEIF(304), T_ELSE(305)
 *   while - T_WHILE(320)
 *   do...while - T_DO(319)
 *   for - T_ENDFOR(322)
 *   foreach - T_ENDFOREACH(324)
 *   switch...case - T_ENDSWITCH(329), T_CASE(331)
 *   break - T_BREAK(333)
 *   continue - T_CONTINUE(334)
*/

document.body.write( "*** Testing token_get_all() : for control structure tokens ***\n");

// if..elseif....else
document.body.write( "-- with if..elseif..else..tokens --\n");

source = '<?php \
if($a == true) {\
     echo "$a = true";\
}\
elseif($a == false) {\
  echo false;\
}\
else\
  echo 1;\
?>';

var_dump( token_get_all(source));

// while..., do..while, break, continue
document.body.write( "-- with while..., do..while, switch & continue tokens --\n");

source = "<?php while(true) {\
echo 'True';\
break;\
}\
do {\
continue;\
}while(false); ?>";

var_dump( token_get_all(source));

// for..., foreach( as )
document.body.write( "-- with for..foreach( as ) tokens --\n");

source = '<?php for($count=0; $count < 5; $count++) {\
echo $count;\
}\
foreach($values as $index) {\
continue;\
} ?>';

var_dump( token_get_all(source));

// switch..case, default
document.body.write( "-- with switch...case tokens --\n");

source = '<?php switch($var)\
case 1: break;\
default: echo "default"; ?>';

var_dump( token_get_all(source));

document.body.write( "Done");

--EXPECTF--
*** Testing token_get_all() : for control structure tokens ***
-- with if..elseif..else..tokens --
array(49) {
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
    int(373)
    [1]=>
    string(1) "
"
    [2]=>
    int(1)
  }
  [2]=>
  array(3) {
    [0]=>
    int(303)
    [1]=>
    string(2) "if"
    [2]=>
    int(2)
  }
  [3]=>
  string(1) "("
  [4]=>
  array(3) {
    [0]=>
    int(311)
    [1]=>
    string(2) "$a"
    [2]=>
    int(2)
  }
  [5]=>
  array(3) {
    [0]=>
    int(373)
    [1]=>
    string(1) " "
    [2]=>
    int(2)
  }
  [6]=>
  array(3) {
    [0]=>
    int(283)
    [1]=>
    string(2) "=="
    [2]=>
    int(2)
  }
  [7]=>
  array(3) {
    [0]=>
    int(373)
    [1]=>
    string(1) " "
    [2]=>
    int(2)
  }
  [8]=>
  array(3) {
    [0]=>
    int(309)
    [1]=>
    string(4) "true"
    [2]=>
    int(2)
  }
  [9]=>
  string(1) ")"
  [10]=>
  array(3) {
    [0]=>
    int(373)
    [1]=>
    string(1) " "
    [2]=>
    int(2)
  }
  [11]=>
  string(1) "{"
  [12]=>
  array(3) {
    [0]=>
    int(373)
    [1]=>
    string(6) "
     "
    [2]=>
    int(2)
  }
  [13]=>
  array(3) {
    [0]=>
    int(318)
    [1]=>
    string(4) "echo"
    [2]=>
    int(3)
  }
  [14]=>
  array(3) {
    [0]=>
    int(373)
    [1]=>
    string(1) " "
    [2]=>
    int(3)
  }
  [15]=>
  string(1) """
  [16]=>
  array(3) {
    [0]=>
    int(311)
    [1]=>
    string(2) "$a"
    [2]=>
    int(3)
  }
  [17]=>
  array(3) {
    [0]=>
    int(316)
    [1]=>
    string(7) " = true"
    [2]=>
    int(3)
  }
  [18]=>
  string(1) """
  [19]=>
  string(1) ";"
  [20]=>
  array(3) {
    [0]=>
    int(373)
    [1]=>
    string(1) "
"
    [2]=>
    int(3)
  }
  [21]=>
  string(1) "}"
  [22]=>
  array(3) {
    [0]=>
    int(373)
    [1]=>
    string(1) "
"
    [2]=>
    int(4)
  }
  [23]=>
  array(3) {
    [0]=>
    int(304)
    [1]=>
    string(6) "elseif"
    [2]=>
    int(5)
  }
  [24]=>
  string(1) "("
  [25]=>
  array(3) {
    [0]=>
    int(311)
    [1]=>
    string(2) "$a"
    [2]=>
    int(5)
  }
  [26]=>
  array(3) {
    [0]=>
    int(373)
    [1]=>
    string(1) " "
    [2]=>
    int(5)
  }
  [27]=>
  array(3) {
    [0]=>
    int(283)
    [1]=>
    string(2) "=="
    [2]=>
    int(5)
  }
  [28]=>
  array(3) {
    [0]=>
    int(373)
    [1]=>
    string(1) " "
    [2]=>
    int(5)
  }
  [29]=>
  array(3) {
    [0]=>
    int(309)
    [1]=>
    string(5) "false"
    [2]=>
    int(5)
  }
  [30]=>
  string(1) ")"
  [31]=>
  array(3) {
    [0]=>
    int(373)
    [1]=>
    string(1) " "
    [2]=>
    int(5)
  }
  [32]=>
  string(1) "{"
  [33]=>
  array(3) {
    [0]=>
    int(373)
    [1]=>
    string(3) "
  "
    [2]=>
    int(5)
  }
  [34]=>
  array(3) {
    [0]=>
    int(318)
    [1]=>
    string(4) "echo"
    [2]=>
    int(6)
  }
  [35]=>
  array(3) {
    [0]=>
    int(373)
    [1]=>
    string(1) " "
    [2]=>
    int(6)
  }
  [36]=>
  array(3) {
    [0]=>
    int(309)
    [1]=>
    string(5) "false"
    [2]=>
    int(6)
  }
  [37]=>
  string(1) ";"
  [38]=>
  array(3) {
    [0]=>
    int(373)
    [1]=>
    string(1) "
"
    [2]=>
    int(6)
  }
  [39]=>
  string(1) "}"
  [40]=>
  array(3) {
    [0]=>
    int(373)
    [1]=>
    string(1) "
"
    [2]=>
    int(7)
  }
  [41]=>
  array(3) {
    [0]=>
    int(305)
    [1]=>
    string(4) "else"
    [2]=>
    int(8)
  }
  [42]=>
  array(3) {
    [0]=>
    int(373)
    [1]=>
    string(3) "
  "
    [2]=>
    int(8)
  }
  [43]=>
  array(3) {
    [0]=>
    int(318)
    [1]=>
    string(4) "echo"
    [2]=>
    int(9)
  }
  [44]=>
  array(3) {
    [0]=>
    int(373)
    [1]=>
    string(1) " "
    [2]=>
    int(9)
  }
  [45]=>
  array(3) {
    [0]=>
    int(307)
    [1]=>
    string(1) "1"
    [2]=>
    int(9)
  }
  [46]=>
  string(1) ";"
  [47]=>
  array(3) {
    [0]=>
    int(373)
    [1]=>
    string(1) "
"
    [2]=>
    int(9)
  }
  [48]=>
  array(3) {
    [0]=>
    int(372)
    [1]=>
    string(2) "?>"
    [2]=>
    int(10)
  }
}
-- with while..., do..while, switch & continue tokens --
array(33) {
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
    int(320)
    [1]=>
    string(5) "while"
    [2]=>
    int(1)
  }
  [2]=>
  string(1) "("
  [3]=>
  array(3) {
    [0]=>
    int(309)
    [1]=>
    string(4) "true"
    [2]=>
    int(1)
  }
  [4]=>
  string(1) ")"
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
  string(1) "{"
  [7]=>
  array(3) {
    [0]=>
    int(373)
    [1]=>
    string(1) "
"
    [2]=>
    int(1)
  }
  [8]=>
  array(3) {
    [0]=>
    int(318)
    [1]=>
    string(4) "echo"
    [2]=>
    int(2)
  }
  [9]=>
  array(3) {
    [0]=>
    int(373)
    [1]=>
    string(1) " "
    [2]=>
    int(2)
  }
  [10]=>
  array(3) {
    [0]=>
    int(317)
    [1]=>
    string(6) "'True'"
    [2]=>
    int(2)
  }
  [11]=>
  string(1) ";"
  [12]=>
  array(3) {
    [0]=>
    int(373)
    [1]=>
    string(1) "
"
    [2]=>
    int(2)
  }
  [13]=>
  array(3) {
    [0]=>
    int(333)
    [1]=>
    string(5) "break"
    [2]=>
    int(3)
  }
  [14]=>
  string(1) ";"
  [15]=>
  array(3) {
    [0]=>
    int(373)
    [1]=>
    string(1) "
"
    [2]=>
    int(3)
  }
  [16]=>
  string(1) "}"
  [17]=>
  array(3) {
    [0]=>
    int(373)
    [1]=>
    string(1) "
"
    [2]=>
    int(4)
  }
  [18]=>
  array(3) {
    [0]=>
    int(319)
    [1]=>
    string(2) "do"
    [2]=>
    int(5)
  }
  [19]=>
  array(3) {
    [0]=>
    int(373)
    [1]=>
    string(1) " "
    [2]=>
    int(5)
  }
  [20]=>
  string(1) "{"
  [21]=>
  array(3) {
    [0]=>
    int(373)
    [1]=>
    string(1) "
"
    [2]=>
    int(5)
  }
  [22]=>
  array(3) {
    [0]=>
    int(334)
    [1]=>
    string(8) "continue"
    [2]=>
    int(6)
  }
  [23]=>
  string(1) ";"
  [24]=>
  array(3) {
    [0]=>
    int(373)
    [1]=>
    string(1) "
"
    [2]=>
    int(6)
  }
  [25]=>
  string(1) "}"
  [26]=>
  array(3) {
    [0]=>
    int(320)
    [1]=>
    string(5) "while"
    [2]=>
    int(7)
  }
  [27]=>
  string(1) "("
  [28]=>
  array(3) {
    [0]=>
    int(309)
    [1]=>
    string(5) "false"
    [2]=>
    int(7)
  }
  [29]=>
  string(1) ")"
  [30]=>
  string(1) ";"
  [31]=>
  array(3) {
    [0]=>
    int(373)
    [1]=>
    string(1) " "
    [2]=>
    int(7)
  }
  [32]=>
  array(3) {
    [0]=>
    int(372)
    [1]=>
    string(2) "?>"
    [2]=>
    int(7)
  }
}
-- with for..foreach( as ) tokens --
array(45) {
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
    int(322)
    [1]=>
    string(3) "for"
    [2]=>
    int(1)
  }
  [2]=>
  string(1) "("
  [3]=>
  array(3) {
    [0]=>
    int(311)
    [1]=>
    string(6) "$count"
    [2]=>
    int(1)
  }
  [4]=>
  string(1) "="
  [5]=>
  array(3) {
    [0]=>
    int(307)
    [1]=>
    string(1) "0"
    [2]=>
    int(1)
  }
  [6]=>
  string(1) ";"
  [7]=>
  array(3) {
    [0]=>
    int(373)
    [1]=>
    string(1) " "
    [2]=>
    int(1)
  }
  [8]=>
  array(3) {
    [0]=>
    int(311)
    [1]=>
    string(6) "$count"
    [2]=>
    int(1)
  }
  [9]=>
  array(3) {
    [0]=>
    int(373)
    [1]=>
    string(1) " "
    [2]=>
    int(1)
  }
  [10]=>
  string(1) "<"
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
    int(307)
    [1]=>
    string(1) "5"
    [2]=>
    int(1)
  }
  [13]=>
  string(1) ";"
  [14]=>
  array(3) {
    [0]=>
    int(373)
    [1]=>
    string(1) " "
    [2]=>
    int(1)
  }
  [15]=>
  array(3) {
    [0]=>
    int(311)
    [1]=>
    string(6) "$count"
    [2]=>
    int(1)
  }
  [16]=>
  array(3) {
    [0]=>
    int(299)
    [1]=>
    string(2) "++"
    [2]=>
    int(1)
  }
  [17]=>
  string(1) ")"
  [18]=>
  array(3) {
    [0]=>
    int(373)
    [1]=>
    string(1) " "
    [2]=>
    int(1)
  }
  [19]=>
  string(1) "{"
  [20]=>
  array(3) {
    [0]=>
    int(373)
    [1]=>
    string(1) "
"
    [2]=>
    int(1)
  }
  [21]=>
  array(3) {
    [0]=>
    int(318)
    [1]=>
    string(4) "echo"
    [2]=>
    int(2)
  }
  [22]=>
  array(3) {
    [0]=>
    int(373)
    [1]=>
    string(1) " "
    [2]=>
    int(2)
  }
  [23]=>
  array(3) {
    [0]=>
    int(311)
    [1]=>
    string(6) "$count"
    [2]=>
    int(2)
  }
  [24]=>
  string(1) ";"
  [25]=>
  array(3) {
    [0]=>
    int(373)
    [1]=>
    string(1) "
"
    [2]=>
    int(2)
  }
  [26]=>
  string(1) "}"
  [27]=>
  array(3) {
    [0]=>
    int(373)
    [1]=>
    string(1) "
"
    [2]=>
    int(3)
  }
  [28]=>
  array(3) {
    [0]=>
    int(324)
    [1]=>
    string(7) "foreach"
    [2]=>
    int(4)
  }
  [29]=>
  string(1) "("
  [30]=>
  array(3) {
    [0]=>
    int(311)
    [1]=>
    string(7) "$values"
    [2]=>
    int(4)
  }
  [31]=>
  array(3) {
    [0]=>
    int(373)
    [1]=>
    string(1) " "
    [2]=>
    int(4)
  }
  [32]=>
  array(3) {
    [0]=>
    int(328)
    [1]=>
    string(2) "as"
    [2]=>
    int(4)
  }
  [33]=>
  array(3) {
    [0]=>
    int(373)
    [1]=>
    string(1) " "
    [2]=>
    int(4)
  }
  [34]=>
  array(3) {
    [0]=>
    int(311)
    [1]=>
    string(6) "$index"
    [2]=>
    int(4)
  }
  [35]=>
  string(1) ")"
  [36]=>
  array(3) {
    [0]=>
    int(373)
    [1]=>
    string(1) " "
    [2]=>
    int(4)
  }
  [37]=>
  string(1) "{"
  [38]=>
  array(3) {
    [0]=>
    int(373)
    [1]=>
    string(1) "
"
    [2]=>
    int(4)
  }
  [39]=>
  array(3) {
    [0]=>
    int(334)
    [1]=>
    string(8) "continue"
    [2]=>
    int(5)
  }
  [40]=>
  string(1) ";"
  [41]=>
  array(3) {
    [0]=>
    int(373)
    [1]=>
    string(1) "
"
    [2]=>
    int(5)
  }
  [42]=>
  string(1) "}"
  [43]=>
  array(3) {
    [0]=>
    int(373)
    [1]=>
    string(1) " "
    [2]=>
    int(6)
  }
  [44]=>
  array(3) {
    [0]=>
    int(372)
    [1]=>
    string(2) "?>"
    [2]=>
    int(6)
  }
}
-- with switch...case tokens --
array(23) {
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
    int(329)
    [1]=>
    string(6) "switch"
    [2]=>
    int(1)
  }
  [2]=>
  string(1) "("
  [3]=>
  array(3) {
    [0]=>
    int(311)
    [1]=>
    string(4) "$var"
    [2]=>
    int(1)
  }
  [4]=>
  string(1) ")"
  [5]=>
  array(3) {
    [0]=>
    int(373)
    [1]=>
    string(1) "
"
    [2]=>
    int(1)
  }
  [6]=>
  array(3) {
    [0]=>
    int(331)
    [1]=>
    string(4) "case"
    [2]=>
    int(2)
  }
  [7]=>
  array(3) {
    [0]=>
    int(373)
    [1]=>
    string(1) " "
    [2]=>
    int(2)
  }
  [8]=>
  array(3) {
    [0]=>
    int(307)
    [1]=>
    string(1) "1"
    [2]=>
    int(2)
  }
  [9]=>
  string(1) ":"
  [10]=>
  array(3) {
    [0]=>
    int(373)
    [1]=>
    string(1) " "
    [2]=>
    int(2)
  }
  [11]=>
  array(3) {
    [0]=>
    int(333)
    [1]=>
    string(5) "break"
    [2]=>
    int(2)
  }
  [12]=>
  string(1) ";"
  [13]=>
  array(3) {
    [0]=>
    int(373)
    [1]=>
    string(1) "
"
    [2]=>
    int(2)
  }
  [14]=>
  array(3) {
    [0]=>
    int(332)
    [1]=>
    string(7) "default"
    [2]=>
    int(3)
  }
  [15]=>
  string(1) ":"
  [16]=>
  array(3) {
    [0]=>
    int(373)
    [1]=>
    string(1) " "
    [2]=>
    int(3)
  }
  [17]=>
  array(3) {
    [0]=>
    int(318)
    [1]=>
    string(4) "echo"
    [2]=>
    int(3)
  }
  [18]=>
  array(3) {
    [0]=>
    int(373)
    [1]=>
    string(1) " "
    [2]=>
    int(3)
  }
  [19]=>
  array(3) {
    [0]=>
    int(317)
    [1]=>
    string(9) ""default""
    [2]=>
    int(3)
  }
  [20]=>
  string(1) ";"
  [21]=>
  array(3) {
    [0]=>
    int(373)
    [1]=>
    string(1) " "
    [2]=>
    int(3)
  }
  [22]=>
  array(3) {
    [0]=>
    int(372)
    [1]=>
    string(2) "?>"
    [2]=>
    int(3)
  }
}
Done
