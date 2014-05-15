<table class="table match-live-reload" id="tblplayers">
    <thead>
        <tr>
            <!--<th>PA Stats</th>-->
            <th>Name</th>
            <th>Win/Losses</th>
        </tr>
    </thead>
<tbody data-bind="template: { name: 'playerTemplate', foreach: players }">
    
</tbody>
</table>
<div class="text-toggle" data-function="spoiler">Show Spoilers: <a href="javascript:void(0);" data-true="Yes" data-false="No">No</a></div>


<script type="text/html" id="playerTemplate">
<tr>
    <!--<td><span data-bind="text: pa_stats_player_id"></span></td>-->
    <td><span data-bind="text: name"></span></td>
    <td></td>
</tr>
</script>

<script type="text/javascript">
jQuery(document).ready(function ($) {
    //console.log("execute matchlisting");
    eeplayerlisting.wptourneyid(<?php echo $vars[0] ?>);
    <?php if($vars[1]) echo 'eeplayerlisting.AutoReload();'?>
    
});
</script>