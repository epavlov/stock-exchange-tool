 /*
 * Funtion to display html alert
 */
function DisplayAlert(alert_type, text){
    $("#global_alert").removeClass("hide").append(text).addClass("show alert-" + alert_type);
}

$(document).ready(function(){
    
    // Initiate portfolio table
    $('#portfolio_table').DataTable();
    
    $(function () {
        $('[data-toggle="tooltip"]').tooltip()
    })
});