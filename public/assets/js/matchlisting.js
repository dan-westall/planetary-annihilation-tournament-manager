var MatchModel = function(data){
	var self = this;
	ko.mapping.fromJS(data,{},self);
	//console.log(self.players()[0]);
	console.log(self.players());
    self.player1 = ko.computed(function(){
    	if(self.players().length > 0){
    		return self.players()[0].player_name();		
    	}
    });
    self.player2 = ko.computed(function(){
    	if(self.players().length > 0){
      		return self.players()[1].player_name();
  		}
    });
	self.paslink = ko.computed(function(){
		return "http://pastats.com/chart?gameId=" + self.pa_stats_match_id();
	});

	self.pasduration = ko.computed(function(){
  		return "10:01";
	});

};

var MatchListing = function() {
	var self = this;
	self.spoiler = ko.observable(true);
	self.matches = ko.observableArray([]);
	self.wptourneyid = ko.observable();
	
	self.selectedView = ko.computed(function(){
      if(self.spoiler() === true){
        return "matchTemplateSpoiler";
      }
      else{
        return "matchTemplate";
      }
	});

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
    		//console.log(data.data);
			for(var i = 0; i < data.data.length; i++)
			{
				self.matches.push(new MatchModel(data.data[i]));
			}
		});
	};
			
	var socket = io.connect('/challongelinker/');
      socket.on('updatedMatch', function (data) {
      console.log(data);
      self.Start();
	  //socket.emit('my other event', { my: 'data' });
	});


};

var eematchlisting = new MatchListing();
ko.applyBindings(eematchlisting);
