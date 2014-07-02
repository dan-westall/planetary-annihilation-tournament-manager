<table class="table match-live-reload" id="tblmatches">
    <thead>
        <tr>
            <th>Round</th>
            <th>Match</th>
            <th>Players</th>
            <th>Duration</th>
            <th></th>
            <!--<th>Last update</th>-->
            <!--<th>Now</th>-->
        </tr>
    </thead>
<tbody data-bind="template: { name: 'matchTemplate', foreach: matches }">

</tbody>
</table>
<div class="text-toggle" data-function="spoiler">Show Spoilers: <a href="javascript:void(0);" data-true="Yes" data-false="No">No</a></div>


<script type="text/html" id="matchTemplate">
<tr>
    <td><span data-bind="text: match_round"></span></td>
    <td><a data-bind="attr: {href: match_url}"><span data-bind="text: title"></span></a></td>
    <td><span data-bind="html: niceplayeroutput, css:{ spoiler: match_round() !== '1'}"></span></td>
    <td><span class="spoiler" data-bind="text:pasduration"></span></td>
    <td>
    <a data-bind="if:pasduration, attr: {href: paslink}" target="_blank"><?php echo '<img src="' . plugins_url( '../../public/assets/img/pastatssmall.png' , __FILE__ ) . '" border="0" title="pastats" alt="pastats">'; ?></a>
    <a data-bind="if:showtwitch,attr: {href: twitch}" target="_blank"><?php echo '<img src="' . plugins_url( '../../public/assets/img/twitchblack.png' , __FILE__ ) . '" border="0" title="twitch" alt="twitch">'; ?></a>
    <a data-bind="if:showvideo,attr: {href: match_url}"><?php echo '<img src="' . plugins_url( '../../public/assets/img/youtube.png' , __FILE__ ) . '" border="0" title="video" alt="video">'; ?></a>
    </td>
    <!--<td data-bind="text: last_update"></td>-->
    <!--<td data-bind="text: now"></td>-->
</tr>
</script>

<script type="text/javascript">
jQuery(document).ready(function ($) {
    //console.log("execute matchlisting");
    eematchlisting.wptourneyid(<?php echo $vars[0] ?>);
    <?php if($vars[1]) echo 'eematchlisting.AutoReload();'?>

});
</script>
