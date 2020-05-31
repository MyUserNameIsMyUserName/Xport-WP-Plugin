$( document ).ready(function() {
    $("#customDBcheckbox").change(function() {
        $(".customDatabaseConfigForm").toggleClass("customDatabaseAdded", this.checked)
    }).change();
});
