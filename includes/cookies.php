<?php

$cookie_lifetime = time() + (86400 * 30); // 30 days

// Generate initial cookie value for cash
if(!isset($_COOKIE["cash"])) {
  setcookie("cash", 100000, $cookie_lifetime , "/");
  $_COOKIE["cash"] = 100000;
} 

// Generate initial cookie value for portfolio
if(!isset($_COOKIE["portfolio"])) {
  setcookie("portfolio", "", $cookie_lifetime, "/");
  $_COOKIE["portfolio"] = "";
}
