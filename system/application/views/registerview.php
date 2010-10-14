<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <?= link_tag('css/apipagecssr2.css', 'stylesheet', 'text/css') ?>

        <title>Mumble Registration</title>
    </head>
    <body>
        <div id="apicontent">
            <h1>Mumble Registration</h1>
            <p>1. <a href="http://mumble.sourceforge.net" target="_blank">Download Mumble</a></p>
            <p>2. <a href="http://www.eveonline.com/api/" target="_blank">Get your limited API Key</a></p>
            <?= form_open('register/add'); ?>
            <p>User ID:</p>
            <input type = "text" id="useridinput" name="userid" value="<?= set_value('userid'); ?>">
            <p>Limited API:</p>
            <input type = "text" id="apiinput" name="apikey" value="<?= set_value('apikey'); ?>">
            <?= form_dropdown('username', $uname_array, $selected_user, $uname_attribs); ?>
            <div class='buttons'>
                <button type='submit' class='positive' name='save' value='Submit'>
                    <img src='images/apply2.png' alt=''/>
                    Submit
                </button>
            </div>
            <?= form_close(); ?>
        </div>
    </body>
</html>
