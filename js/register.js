$(document).ready(function() {
    $('#userIdInput').keyup(function(){
        
    });
    $('#inputForm').submit(function(e) {
        e.preventDefault();
        $.post($(this).attr("action"), $(this).serialize(), function(data) {
            $('#charDiv').show('fast');
            $(data).each(function(i) {
                $('#charSel').
                append($('<option></option>').
                    attr('value',$(this)[0].charid).
                    text($(this)[0].name));
            });
        }, "json");
    });
});