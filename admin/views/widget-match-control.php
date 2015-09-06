<div class="generate-match-container">

    <input id="generate-match-nonce" type="hidden" value="<?php echo esc_attr(wp_create_nonce('generate-matches')); ?>">
    Generate tournament match ups. ( Only available when tournament in Preparation stage)

    <br/><br/>
    <label for="generate-groups">Generate based on player groups</label>
    <input type="checkbox" name="generate-groups" id="generate-groups" value="true" <?php checked( true ); ?>>

    <br/><br/>
    <button class="button">Generate Matches</button>

    <br/><br/>
    <div id="generate-match-response"></div>

</div>