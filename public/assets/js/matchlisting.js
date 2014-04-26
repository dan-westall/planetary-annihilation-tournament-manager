var MatchModel = function(data){
	var self = this;
	ko.mapping.fromJS(data,{},self);
	//console.log(self.players()[0]);
//	console.log(self);
    self.player1 = ko.computed(function(){
    	if(self.players().length > 0){
    		return self.players()[0].player_name();		
    	}
    });
    self.player2 = ko.computed(function(){
    	if(self.players().length > 1){
      		return self.players()[1].player_name();
  		}
    });

    self.winner = ko.computed(function(){
		if(self.players().length > 0){
			if(self.players()[0].winner() == 1){
				return self.players()[0].player_name();
			}
			else{
		    	if(self.players().length > 1){
		    		if(self.players()[1].winner() == 1){
		      			return self.players()[1].player_name();
		      		}
		  		}			
			}
		}
    });

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
            if(self.winner() !== undefined){
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
                if(self.winner() === undefined){
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

};

var MatchListing = function() {
	var self = this;
	self.spoiler = ko.observable(false);
	self.matches = ko.observableArray([]);
	self.wptourneyid = ko.observable();

	self.restendpoint = ko.computed(function(){
		return "/api/tournament-matches/" + self.wptourneyid();
	});

	self.wptourneyid.subscribe(function(newValue){
		self.Start();
	});

	self.Start = function(){
		self.matches([]);
		//CORRECT URL !
		$.getJSON(self.restendpoint(),function(data){
			//ko.mapping.fromJS(data.data,mapping,self.matches);
    		
    		if(data.data !== undefined){
//    			console.log(data.data);
				for(var i = 0; i < data.data.length; i++){
					self.matches.push(new MatchModel(data.data[i]));
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
				var updatedmatch = new MatchModel(data.data[0]);
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
			return right.title() < left.title() ? 1 : -1
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
	setInterval(self.Start,60000);


};

var eematchlisting = new MatchListing();
ko.applyBindings(eematchlisting);

