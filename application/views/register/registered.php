<div id="apicontent">
    <h1>Successfully registered <?=$username?></h1>
    <?php if (!empty($successMessage)): ?>
        <?= $successMessage ?>
    <?php endif; ?>
    <p>Please connect to: <?=$host?></p>
    <p>Port: <?=$port?></p>
    <p>or click <a href="mumble://<?=str_replace(".", "%2E", rawurlencode($username)) . ':'
        . $password . '@' . $host . ':' . $port . '/?version=1.2.2'; ?>">here</a> once you have Mumble installed</p>
    <p>Once connected go to Server->Connect, hit Add New..., give it a label (e.g. Comms) then click OK to save the connection as a favourite</p>
    <p>Your corp ticker will be prepended to your username automatically within 5 minutes and will take effect next time you login</p>
</div>
<?= "Time: " . $this->benchmark->elapsed_time(); ?>
<?= "\tMemory: " . $this->benchmark->memory_usage(); ?>
