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
		$.getJSON("//indianalearns.info/api/v1/schools?limit=99999",function(data){
			for(var i = 0; i < data.length; i++){
				var d = data[i];
				var loc = new google.maps.LatLng(d.lat,d.lon);
				var mark = new google.maps.Marker({
					map: map,
					position: loc,
					title: d.name
				});
				mark['infowindow'] = new google.maps.InfoWindow({
					content : "<h1>" + d.name + "</h1>"+
					"<ul><li><strong>Principal: </strong>" + d.principal_name + "</li>"+
					"<li><strong>School Type: </strong>"+d.category + "</li></ul>"
				});
				google.maps.event.addListener(mark, 'click', function() {
					this['infowindow'].open(map,this);
				});
			}
		});
	}
	function projectGISToLatLon(url, callback) {
		// take an ArcGIS url and project its geometry into lat/lon
		$.getJSON(url, function(data) {
			var spatialRefID = data.spatialReference.wkid;
			// 4326 is a constant, refers to GCS_WGS_1984, allows us to convert points defined in relation to spatialRefID into points relative to gmaps lat/lon
			var outputSpatialRefID = 4326; 
			
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
