<?php
/*
 * Abstract class to hold default values for message status type
 * Used in set_message function
 */
abstract class AlertType {
    const Success = "success";
    const Info = "info";
    const Warning = "warning";
    const Danger = "danger";
}


/*
 * Function to set messege for user to display
 * @param string $alert_type
 * @param $text
 */
function set_message($alert_type = AlertType::Success, $text){
  echo "<script>
          $(document).ready(function(){
              DisplayAlert('".$alert_type."', '".$text."');
          });
        </script>";
}