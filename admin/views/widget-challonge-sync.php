<div class="challonge-sync-container">

    <input id="challonge-sync-nonce" type="hidden" value="<?php echo esc_attr(wp_create_nonce('challonge-sync')); ?>">

    This will delete all challonge players and re-add tournament players to the challonge system.<br /><br />

    <button class="button">Re-sync</button> <span class="spinner"></span>

    <div id="challonge-sync-response"></div>

</div>