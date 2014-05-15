var PlayerModel = function(data){
	var self = this;
	ko.mapping.fromJS(data,{},self);
}

var PlayerListing = function() {
	var self = this;
	self.players = ko.observableArray([]);
	self.wptourneyid = ko.observable();

	self.restendpoint = ko.computed(function(){
		return "/api/tournament/" + self.wptourneyid() + "/players";
	});

	self.wptourneyid.subscribe(function(newValue){
		self.Start();
	});

	self.Start = function(){
		self.players([]);
		//CORRECT URL !
		$.getJSON(self.restendpoint(),function(data){
			//ko.mapping.fromJS(data.data,mapping,self.matches);
    		
    		if(data !== undefined){
//    			console.log(data.data);
				for(var i = 0; i < data.length; i++){
					self.players.push(new PlayerModel(data[i]));
					//self.SortMatches();
				}
			}
			
		});
	};


	self.SortPlayers = function(){
  		self.players.sort(
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
	
	self.AutoReload = function(){
		setInterval(self.Start,60000);
	};


};

var eeplayerlisting = new PlayerListing();
ko.applyBindings(eeplayerlisting);

