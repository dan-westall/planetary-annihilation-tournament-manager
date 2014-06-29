var MatchModel = function(data){
	var self = this;
	ko.mapping.fromJS(data,{},self);

	self.haswinner = ko.computed(function(){
		var cleanplayers = _.filter(players, {'winner': team + "1"});
		if(cleanplayers.length >= 1){
			return true;
		}
	});

	function playersperTeam(players, team){
		var output = "";
		var cleanplayers = _.filter(players, {'team': team + ""});
		for(var p = 0; p < cleanplayers.length; p++){
			if(p === 0){
				output = cleanplayers[p].player_name;
				if(cleanplayers[p].winner === '1'){
					output = output + "<span class=\"spoiler matchwinner\"></span>";
				}
			}
			else{
				output = output + ", " + cleanplayers[p].player_name;
					if(cleanplayers[p].winner === '1'){
						output = output + "<span class=\"spoiler matchwinner\"></span>";
					}
			}
		}
		return output;
	}

	self.niceplayeroutput = ko.computed(function (){
		var cleanplayers = _.sortBy(ko.toJS(self.players()),'team');
		var teams = _.uniq(_.map(cleanplayers,'team'),true);
		var output = "";
		for(var t = 0; t < teams.length; t++){
			if(t === 0){
				output = playersperTeam(cleanplayers,t);
			}
			else {
				output = output + " vs. " + playersperTeam(cleanplayers,t);
			}
		}
		return output;
	},this);

	self.paslink = ko.computed(function(){
		return "http://pastats.com/chart?gameId=" + self.pa_stats_match_id();
	});

	var now = new Date().getTime();
	self.now = ko.observable(now).extend({ throttle: 900 });

	self.updateNow = function(){
		var now = new Date().getTime();
		self.now(now);
	};

	setInterval(self.updateNow, 1000);

  self.pasduration = ko.computed(function(){
      //return "10:01";
      if(self.paslink() !== "http://pastats.com/chart?gameId="){
          if(self.haswinner() !== undefined){
              var time = (self.pa_stats_stop() - self.pa_stats_start()) / 1000;
              var minutes = Math.floor(time / 60);
              var seconds = time - minutes * 60;
              seconds = seconds.toString().substring(0,2);
//console.log(seconds.substring(1,2));
              if(seconds.substring(1,2) === '.'){
                  seconds = '0' + seconds.substring(0,1);
              }
              return minutes + ":" + seconds;
          }
          else
          {
              if(self.haswinner() === undefined){
                  var time = (parseInt(self.now()) - self.pa_stats_start()) / 1000;
                  var minutes = Math.floor(time / 60);
                  var seconds = time - minutes * 60;
                  seconds = seconds.toString().substring(0,2);
//console.log(seconds.substring(1,1));
                  if(seconds.substring(1,2) === '.'){
                      seconds = '0' + seconds.substring(0,1);
                  }
                  return "+" + minutes + ":" + seconds;
              }
          }
      }

  });

	self.showtwitch = ko.computed(function(){
		if(self.twitch() !== "#"){
			if(self.haswinner() === undefined){
				return true;
			}
			else
			{
				return false;
			}
		}
		else{
			return false;
		}
	});

	self.showvideo = ko.computed(function(){
		if(self.videos().length > 0){
			return true;
		}
		else
		{
			return false;
		}
	});

	//console.log(self.showvideo());

};

var MatchListing = function() {
	var self = this;
	self.spoiler = ko.observable(false);
	self.matches = ko.observableArray([]);
	self.wptourneyid = ko.observable();

	self.restendpoint = ko.computed(function(){
		return "/api/tournament/" + self.wptourneyid() + "/matches";
	});

	self.wptourneyid.subscribe(function(newValue){
		self.Start();
	});

	self.Start = function(){
		self.matches([]);
		//CORRECT URL !
		$.getJSON(self.restendpoint(),function(data){
			//ko.mapping.fromJS(data.data,mapping,self.matches);

    		if(data !== undefined){
//    			console.log(data.data);
				for(var i = 0; i < data.length; i++){
					self.matches.push(new MatchModel(data[i]));
					self.SortMatches();
				}
			}

		});
	};

	self.UpdateMatch = function(match){
//		console.log("updating match " + match);
		$.getJSON("/api/tournament-match/" + match,function(data){
//			console.log(data);
			if(data.data.length > 0){
				var updatedmatch = new MatchModel(data[0]);
				var oldMatch = ko.utils.arrayFirst(self.matches(), function(item) {
				    return item.challonge_match_id() == updatedmatch.challonge_match_id();
				});
				if(oldMatch === null){
					self.matches.push(updatedmatch);
					self.SortMatches();
				}
				else{
					self.matches.replace(oldMatch, updatedmatch);
				}


			}
		});
	};

	self.SortMatches = function(){
  		self.matches.sort(
		function(left, right) {
			//return right.title() < left.title() ? 1 : -1
			return left.match_round() === right.match_round()
			? right.title().toLowerCase() > left.title().toLowerCase() ? -1 : 1
			: right.match_round() < left.match_round() ? 1 : -1
		});
	};

	/*
	var socket = io.connect(':5000');

    socket.on('updatedMatch', function (data) {
      self.UpdateMatch(data);
	  //socket.emit('my other event', { my: 'data' });
	});
	socket.on('error', function() {
    //here i change options
    	socket = io.connect(':5000', {
  			'force new connection': true
		});
	});
	*/

	self.AutoReload = function(){
		setInterval(self.Start,60000);
	};


};

var eematchlisting = new MatchListing();
ko.applyBindings(eematchlisting);
