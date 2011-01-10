<div id="apicontent">
    <h1>Mumble Registration</h1>
    <p>1. <a href="http://mumble.sourceforge.net" target="_blank">Download Mumble</a></p>
    <p>2. <a href="http://www.eveonline.com/api/" target="_blank">Get your limited API Key</a></p>
    <br />
    <?= validation_errors(); ?>
    <?= form_open('register'); ?>
    <p>User ID:</p>
    <?= form_input(array(
        'name' => 'userid', 
        'id' => 'userid', 
        'class' => 'userinput',
        'value' => set_value('userid'))); ?>
    <p>Limited API:</p>
    <?= form_input(array(
        'name' => 'apikey', 
        'id' => 'apikey', 
        'class' => 'userinput', 
        'value' => set_value('apikey'))); ?>