<?php
/*
 * Abstract class to hold default values for user action
 */
abstract class SharesAction {
    const Buy = "buy";
    const Sell = "sell";
}

/*
 * Function to hadnle sell/buy user actions
 * @param $values - postback values
 * @param string $action
 */
function update_portfolio_event_handler($values, $action) {
  $portfolio = load_portfolio_array();
  $existing_record = portfolio_record_lookup($portfolio, $values["lookupSymbol"]);
  
  switch ($action){
    case "buy":
      if(isset($existing_record)) {
        // Update existing record in portfolio
        $existing_record["quantity"] = $existing_record["quantity"] + $values["quantity"];
        $existing_record["price_paid"] = $values["lookupAsk"];
        save_portfolio($portfolio, $existing_record, $values["lookupSymbol"]);
      } else {
        // Set new record to portfolio
        $portfolio_record = array('symbol' => $values["lookupSymbol"],
          'name' => $values["lookupName"],
          'quantity' => $values["quantity"],
          'price_paid' => $values["lookupAsk"]);
        save_portfolio($portfolio, $portfolio_record);
      }
	  set_message(AlertType::Success, "You successfuly bought " . $values["quantity"] . " " . $values["lookupName"] ." shares.");
      break;
    case "sell":
      // Update existing record in portfolio
      $existing_record["quantity"] = $existing_record["quantity"] - $values["quantity"];
      save_portfolio($portfolio, $existing_record, $values["lookupSymbol"]);
	  set_message(AlertType::Success, "You successfuly sold " . $values["quantity"] . " " . $values["lookupName"] ." shares.");
      break;
  }
}

/*
 * Function to save new or existing record to portfolio
 * @param $portfolio
 * @param $record
 * @param $symbol
 */
function save_portfolio ($portfolio, $record, $symbol = NULL){
  if ($symbol != NULL) {
    $portfolio = remove_portfolio_record_by_symbol($symbol);
  }
  
  // Check if all shares where sold
  // If yes, we dont need to save record with 0 shares in posession
  if($record["quantity"] != 0) {
    // Prepend record to beginning of $portfolio array
    array_unshift($portfolio, $record);
  }
  
  // Save portfolio as json string back to cookie
  $json_portfolio = json_encode($portfolio);
  setcookie("portfolio", $json_portfolio, time() + (86400 * 30), "/");
  $_COOKIE["portfolio"] = $json_portfolio;
}

/*
 * Search record by symbol and temporary remove it from portfolio array
 * @param string $symbol
 * @return array $portfolio
 */
function remove_portfolio_record_by_symbol($symbol){
  $portfolio = load_portfolio_array();
  foreach ($portfolio as  $key => $old_record){
    if($old_record["symbol"] == $symbol) { 
      unset($portfolio[$key]);
      break;
    }
  }
  return $portfolio;
}

/*
 * Function to load portfolio data from cookie and return array of values
 * @return $result
 */
function load_portfolio_array () {
  $portfolio = json_decode(stripslashes($_COOKIE["portfolio"]));
  $result = array();
  foreach ($portfolio as $object) {
      $result[] = (array) $object;
  }
  return $result;
}

/*
 * Function to find record in current user portfolio
 * @param array $portfolio
 * @param string $symbol
 * @returm array $result or NULL if no record found
 */
function portfolio_record_lookup ($portfolio, $symbol){
  $result = null;
  foreach ($portfolio as $record) {
    if($record["symbol"] == $symbol) { 
      $result = $record;
      break;
    }
  }
  return $result;
}

/*
 * Function to find current amount of shares for specified symbol
 * @param string symbol
 * @return int $shares_amount_available
 */
function get_shares_amount ($symbol) {
  $portfolio = load_portfolio_array();
  $shares_amount_available = 0;
  foreach ($portfolio as $record) {
    if ($record["symbol"] == $symbol) {
      $shares_amount_available = $record["quantity"];
    }
  }
  return $shares_amount_available;
}