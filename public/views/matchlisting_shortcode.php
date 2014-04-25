<table class="table match-live-reload" id="tblmatches">
    <thead>
        <tr>
            <th>Match</th>
            <th>Player 1</th>
            <th>Player 2</th>
            <th>Duration</th>
            <!--<th>Last update</th>-->
        </tr>
    </thead>
<tbody data-bind="template: { name: 'matchTemplate', foreach: matches }">
    
</tbody>
</table>
</div>

<script type="text/html" id="matchTemplate">
<tr>
    <td><span data-bind="text: title"></span></td>
    <td><span class="spoiler" data-bind="text: player1, css:{ matchwinner: player1() === winner()}"></span></td>
    <td><span class="spoiler" data-bind="text: player2, css:{ matchwinner: player2() === winner()}"></span></td>
    <td><a data-bind="attr: {href: paslink}" target="_blank"><span data-bind="text:pasduration"></span></a></td>
    <!--<td data-bind="text: last_update"></td>-->
</tr>
</script>

<script type="text/javascript">
jQuery(document).ready(function ($) {
    //console.log("execute matchlisting");
    eematchlisting.wptourneyid(<?php echo $vars ?>);
});
</script>