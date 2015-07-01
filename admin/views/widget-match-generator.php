<div class="generate-match-container">

    <input id="generate-match-nonce" type="hidden" value="<?php echo esc_attr(wp_create_nonce('generate-matches')); ?>">
    Generate tournament match ups. ( Only available when tournament in Preparation stage)

    <br/><br/>
    <button class="button">Generate Matches</button>

    <div id="generate-match-response"></div>

</div>

<?php

foreach(WPTM_Tournament_Formats::schedule_format(['test1', 'test2', 'test3', 'test4', 'test5', 'test6', 'test7', 'test8']) AS $round => $games){
    echo "Round: ".($round+1)."<BR>";
    foreach($games AS $play){
        echo $play["Home"]." - ".$play["Away"]."<BR>";
    }
    echo "<BR>";
}

?>