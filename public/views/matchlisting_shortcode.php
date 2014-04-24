<table class="table" id="tblmatches">
    <thead>
        <tr>
            <th>Match</th>
            <th>Player 1</th>
            <th>Player 2</th>
            <th>Duration</th>
            <th>Last update</th>
        </tr>
    </thead>
<tbody data-bind="template: { name:  function() { return selectedView(); }, foreach: matches }">
    
</tbody>
</table>
</div>

<script type="text/html" id="matchTemplate">
<tr>
    <td data-bind="text: title"></td>
    <td data-bind="text: player1"></td>
    <td data-bind="text: player2"></td>
    <td><a data-bind="attr: {href: paslink}" target="_blank"><span data-bind="text:pasduration"></span></a></td>
    <td data-bind="text: last_update"></td>
</tr>
</script>

<script type="text/html" id="matchTemplateSpoiler">
<tr>
    <td data-bind="text: title"></td>
    <td>Turn off Spoiler</td>
    <td>Turn off Spoiler</td>
    <td><a data-bind="attr: {href: paslink}" target="_blank"><span data-bind="text:pasduration"></span></a></td>
    <td data-bind="text: last_update"></td>
</tr>
</script>
<script type="text/javascript">
jQuery(document).ready(function ($) {
    //console.log("execute matchlisting");
    eematchlisting.wptourneyid(<?php echo $vars ?>);
});
</script>