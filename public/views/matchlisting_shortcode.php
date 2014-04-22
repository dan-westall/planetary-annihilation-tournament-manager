<form class="navbar-form navbar-left">
<div class="panel panel-warning">
  <div class="panel-heading">Testing</div>
  <div class="panel-body">
    <div class="input-group">
    <span class="input-group-addon">Spoilers ? </span>
    <input type="text" class="form-control" data-bind="value: spoiler"/>
    </div>  
</div>
</div>
</form>
<div class="panel panel-info">
  <div class="panel-heading">Open Matches</div>
  <div class="panel-body">
  </div>

<table class="table table-striped table-hover" id="tblmatches">
    <thead>
        <tr>
            <th>Match</th>
            <th>Player 1</th>
            <th>Player 2</th>
            <th>Duration</th>
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
</tr>
</script>

<script type="text/html" id="matchTemplateSpoiler">
<tr>
    <td data-bind="text: title"></td>
    <td>Turn off Spoiler</td>
    <td>Turn off Spoiler</td>
    <td><a data-bind="attr: {href: paslink}" target="_blank"><span data-bind="text:pasduration"></span></a></td>
</tr>
</script>
<script type="text/javascript">
jQuery(document).ready(function ($) {
    //console.log("execute ladder");
    eematchlisting.wptourneyid(<?php echo $vars ?>);
});
</script>