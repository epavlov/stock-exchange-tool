<!DOCTYPE html>
<html>
    <head>
        <title>Stock Exchange Tool</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width">
        <script src="https://code.jquery.com/jquery-2.1.4.min.js"></script>
        <!--Data Tables-->
        <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/s/bs/dt-1.10.10/datatables.min.css"/>
        <script type="text/javascript" src="https://cdn.datatables.net/s/bs/dt-1.10.10/datatables.min.js"></script>
        <!-- Latest compiled and minified CSS -->
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css">
        <!-- Optional theme -->
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap-theme.min.css">
        <!-- Latest compiled and minified JavaScript -->
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js"></script>
        <script type="text/javascript" src="js/main.js"></script>
        <link rel="stylesheet" type="text/css" href="css/main.css"/>
    </head>
	
<?php
include 'includes/cookies.php';
include 'includes/message.php';
include 'includes/portfolio_functions.php';

/*
 * Function to find stock information by specified symbol
 * @param string $key
 * @return array of values
 */
function lookup_symbol($key){
  $url = "http://careers-data.benzinga.com/rest/richquote?symbols=" . $key;
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 4);
  $json = curl_exec($ch);
  if(!$json) {
      set_message(AlertType::Danger, curl_error($ch));
  }
  curl_close($ch);
  
  $search_result = json_decode($json);
  $data = $search_result->$key;
  $data = get_object_vars($data);
  
  // Check if error returned and display error message
  if(isset($data["error"])){
    $data = get_object_vars($data["error"]);
    set_message(AlertType::Danger, $data["message"]);
  } else {
    return array ($data["name"], $data["symbol"], $data["bidPrice"], $data["askPrice"]);
  }
}

// Symbol search event handler
if(isset($_POST['search'])) {
  list ($lookupName, $lookupSymbol, $lookupBid, $lookupAsk) = lookup_symbol($_POST['symbol_search']);
}

// View stock btn event handler
if(isset($_POST['view_stock'])) {
  list ($lookupName, $lookupSymbol, $lookupBid, $lookupAsk) = lookup_symbol($_POST['view_stock']);
}

// Buy postback event handler
if(isset($_POST['buy'])) {
  $cash = $_POST["quantity"] * $_POST["lookupAsk"];
  
  // Check if user has enought cash to buy shares
  if($_COOKIE['cash'] > $cash) {
    // Update cookie json value for portfolio
    update_portfolio_event_handler($_POST, SharesAction::Buy);
    
    // Update cash value
    setcookie("cash", $_COOKIE['cash'] - $cash, time() + (86400 * 30), "/");
    $_COOKIE['cash'] = $_COOKIE['cash'] - $cash;
  } else {
   set_message(AlertType::Warning, "Not enought cash to buy " . $_POST["quantity"] . " shares.");
  }
}

// Sell postback event handler
if(isset($_POST['sell'])) {
  $cash = $_POST["quantity"] * $_POST["lookupBid"];
 
  // Check if user has enought shares to sell
  $shares_amount_available = get_shares_amount($_POST["lookupSymbol"]);
  if($_POST["quantity"] <= $shares_amount_available && $shares_amount_available != 0){
    // Update cookie json value for portfolio
    update_portfolio_event_handler($_POST, SharesAction::Sell);
  
    // Update cash value
    setcookie("cash", $_COOKIE['cash'] + $cash, time() + (86400 * 30), "/");
    $_COOKIE['cash'] = $_COOKIE['cash'] + $cash;
  } else if($shares_amount_available === 0) {
    set_message(AlertType::Warning, "You have 0 shares of " . $_POST["lookupName"] . ". You cant sell them.");
  } else {
    set_message(AlertType::Warning, "Not enought shares to sell. You can sell " . $shares_amount_available . " or less amount of shares.");
  }
}

/*
 * Function to generate portfolio output
 * @return string $output
 */
function generate_portfolio_html() {
  $output = "";
  $portfolio_arr = load_portfolio_array();
  foreach ($portfolio_arr as $record) {
    $output .= "<tr>
              <th scope='row'>" . $record["name"] . " (" . $record["symbol"] . ")</th>
              <td>" . $record["quantity"] . "</td>
              <td>" . $record["price_paid"] . "</td>
              <td>
				<button type='submit' name='view_stock' class='btn btn-info' value='" . $record["symbol"] . "'>View Stock</button>
			  </td>
          </tr>";
  }
  return $output;
}
?>
    <body>
        <nav class="navbar navbar-default">
          <div class="container-fluid">
            <!-- Brand and toggle get grouped for better mobile display -->
            <div class="navbar-header">
              <a class="navbar-brand" href="#"><span class="glyphicon glyphicon-briefcase" aria-hidden="true"></span> Stock Exchange Tool
              </a>
            </div>
            <!-- Collect the nav links, forms, and other content for toggling -->
            <div class="collapse navbar-collapse pull-right" id="bs-example-navbar-collapse-1">
              <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="navbar-form navbar-left" role="search" method="post">
                <div class="input-group">
                  <input type="text" class="form-control" name="symbol_search" required placeholder="Enter Symbol">
                  <span class="input-group-btn">
                    <button type="submit" name="search" class="btn btn-primary">Lookup</button>
                  </span>
                </div>
              </form>
            </div><!-- /.navbar-collapse -->
          </div><!-- /.container-fluid -->
        </nav>
        <div class="row">
            <div style="padding: 20px;">
                
            <div class="alert alert-dismisable hide" id="global_alert" role="alert">
              <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            
            <div class="col-xs-6 col-md-4">
                <div class="panel panel-default">
                    <div class="panel-heading">Lookup result</div>
                    <div class="panel-body">
                        <h3><?php if(isset($lookupName)) { echo $lookupName . " (". $lookupSymbol .")"; } ?></h3>
                        <table class="table table-default">
                            <thead>
                                <tr>
                                  <th>Bid<sup data-toggle="tooltip" data-placement="top" title="bid price is the price at which shares can currently be sold">?</sup></th>
                                    <th>Ask<sup data-toggle="tooltip" data-placement="top" title="ask price is the price at which shares can currently be bought">?</sup></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <?php if(isset($lookupBid)) { echo $lookupBid; } else { echo "—"; } ?>
                                    </td>
                                    <td>
                                        <?php if(isset($lookupAsk)) { echo $lookupAsk; } else { echo "—"; } ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" class="form-inline <?php if(!isset($lookupName)) { echo "hide"; } ?>">
                            <div class="form-group">
                                <input type="number" name="quantity" required="required" class="form-control" placeholder="Quantity">
                                <input type="hidden" name="lookupName" value="<?php echo $lookupName; ?>" />
                                <input type="hidden" name="lookupSymbol" value="<?php echo $lookupSymbol; ?>" />
                                <input type="hidden" name="lookupBid" value="<?php echo $lookupBid; ?>" />
                                <input type="hidden" name="lookupAsk" value="<?php echo $lookupAsk; ?>" />
                            </div>
                            <button type="submit" class="btn btn-success" name="buy">Buy</button>
                            <button type="submit" class="btn btn-warning" name="sell">Sell</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-xs-12 col-sm-6 col-md-8">
                <div class="panel panel-default">
                    <div class="panel-heading">Current Portfolio<span class="label label-primary pull-right" style="font-size: 14px;">Cash: $<?php echo number_format($_COOKIE["cash"], 2, ".", ","); ?></span></div>
                    <div class="panel-body">
						<form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                        <table class="table table-striped" id="portfolio_table">
                            <thead>
                                <tr>
                                    <th>Company</th>
                                    <th>Quantity</th>
                                    <th>Price Paid</th>
                                    <th>&nbsp;</th>
                                </tr>
                            </thead>
                            <tbody>
                              <?php echo generate_portfolio_html(); ?>
                            </tbody>
                        </table>
						</form>
                    </div>
                </div>
            </div>
            </div>
        </div>
    </body>
</html>
