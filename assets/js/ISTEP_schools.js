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
			var count = 0;
			$.each(data,function(index,d){
				count = count + 1;
				if(count>10){return false;}

				projectGISToLatLon(d.gis_url, function(pos) {
					var mark = new google.maps.Marker({
						map: map,
						position: pos,
						title: d.name,
						animation: google.maps.Animation.DROP
					});
				});
				// var add = encodeURIComponent(d.address);
				// var zip = encodeURIComponent(d.zip);
				// $.getJSON("https://maps.googleapis.com/maps/api/geocode/json?components=street_address:" + add +"|postal_code:"+zip,function(data){
				// 	var lat = data.results[0].geometry.location.lat;
				// 	var lng = data.results[0].geometry.location.lng;
				// 	var pos = new google.maps.LatLng(lat,lng);

				// 	var mark = new google.maps.Marker({
				// 		postion: pos,
				// 		map: map,
				// 		animation: google.maps.Animation.DROP
				// 	});
				// });
			});
		});
	}
	function projectGISToLatLon(url, callback) {
		// take an ArcGIS url and project its geometry into lat/lon
		$.getJSON(url, function(data) {
			var spatialRefID = data.spatialReference.wkid;
			var outputSpatialRefID = 4326; // constant, refers to lat/lon coordinate system
			
			var geometries = {
				geometryType: data.geometryType,
				geometries: [data.features[0].geometry]
			};
			
			var projectQueryString = "project" 
				    + "?inSR="+spatialRefID
				    + "&outSR="+outputSpatialRefID
				    + "&geometries="+encodeURIComponent(JSON.stringify(geometries))
				    + "&f=json";
			
			var serviceURL = "http://maps.indiana.edu/arcgis/rest/services/Utilities/Geometry/GeometryServer/";
			serviceURL += projectQueryString;
			
			$.getJSON(serviceURL, function(result) {
				if(callback && typeof callback === "function") {
					callback(new google.maps.LatLng(result.geometries[0].y,
					                                result.geometries[0].x));
				}
			});
		});
	}
}());
