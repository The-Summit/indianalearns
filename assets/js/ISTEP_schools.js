var map;
(function(){
	$("#map-canvas").height($(window).height()-$(".navbar").outerHeight());
	var mapOptions = {
		center: new google.maps.LatLng(-34.397, 150.644),
		zoom: 8,
 		mapTypeControl: false
	};
	map = new google.maps.Map(document.getElementById('map-canvas'),mapOptions);
	getUserLocation();
	loadSchools();
	
	function getUserLocation (){
		if (navigator.geolocation) {
 			navigator.geolocation.getCurrentPosition(function(loc){
				var lat = loc.coords.latitude;
				var lng = loc.coords.longitude;
				gloc = new google.maps.LatLng(lat, lng);
				map.setCenter(gloc);
			},function(){
				showDefaultMap();	
			}
			);
		} else {
			showDefaultMap();	
		}
	}
	function showDefaultMap(){
		gloc = new google.maps.LatLng("39.768403", "-86.158068");
		map.setCenter(gloc);	
	}
	function loadSchools(){
		$.getJSON("//indianalearns.info/api/v1/schools/",function(data){	
			var count = 1;
			$.each(data,function(index,d){
				count = count + 1;
				if(count>5){return false;}
				var add = encodeURIComponent(d.address);
				var zip = encodeURIComponent(d.zip);
				$.getJSON("https://maps.googleapis.com/maps/api/geocode/json?components=street_address:" + add +"|postal_code:"+zip,function(data){
					var lat = data.results[0].geometry.location.lat;
					var lng = data.results[0].geometry.location.lng;
					var pos = new google.maps.LatLng(lat,lng);

					var mark = new google.maps.Marker({
						postion: pos,
						map: map,
						animation: google.maps.Animation.DROP
					});
				});
			});
		});
	}
}());
