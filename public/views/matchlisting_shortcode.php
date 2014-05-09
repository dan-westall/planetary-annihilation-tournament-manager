<table class="table match-live-reload" id="tblmatches">
    <thead>
        <tr>
            <th>Match</th>
            <th>Player 1</th>
            <th>Player 2</th>
            <th>Duration</th>
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
    <td><span data-bind="text: title"></span></td>
    <td><span class="spoiler" data-bind="text: player1, css:{ matchwinner: player1() === winner()}"></span></td>
    <td><span class="spoiler" data-bind="text: player2, css:{ matchwinner: player2() === winner()}"></span></td>
    <td><a data-bind="attr: {href: paslink}" target="_blank"><span data-bind="text:pasduration"></span></a></td>
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