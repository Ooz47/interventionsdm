<?php

if (!function_exists('kint_dump') && class_exists('\Kint\Kint')) {
  function kint_dump($var, $name = null) {
    if ($name) {
      echo "<strong>$name</strong>:\n";
    }
    \Kint\Kint::dump($var);
  }
}

// if (!function_exists('kpr') && class_exists('\Kint\Kint')) {
//   function kpr($var) {
//     \Kint\Kint::dump($var);
//   }
// }
