(function(){
	$(".modal .spinner").css("marginTop",($(window).height()-$(".navbar").outerHeight())/2);
	loading();
	$("#map-canvas").height($(window).height()-$(".navbar").outerHeight());
	var mapOptions = {
		center: new google.maps.LatLng(-34.397, 150.644),
 		zoom: 8,
 		mapTypeControl: false
	};
	map = new google.maps.Map(document.getElementById('map-canvas'),mapOptions);
	markers = [];
	schools = [];
	setInitialLocation();
	$.getJSON("//indianalearns.info/api/v1/schools?limit=999999",function(data){
		schools = data;
		setControlEvents();
	});	
	function setControlEvents(){
		var control = $(".map-controls");
		control.on("change","select,input",function(){
			updateMap();			
		}).on("click",".input-group-btn-vertical",function(){
			updateMap();
		}).find("[name='grade']").trigger("change")
		control.on("click",".minimize",function(e){
			var v = $(e.target).closest(".map-control");
			v.find(".minimizable").slideToggle();
			v.find(".glyphicon").toggleClass("glyphicon-plus").toggleClass("glyphicon-minus")
		});
	}
	function updateMap(){
		loading();
		clearMarkers();
		var year = $("[name=year] :selected").val(),
			grade = $("[name=grade] :selected").val(),
			score = Number($("[name=score]").val().replace("%",""))*.01,
			subject	= $("[name=subject] :selected").val();
			$.getJSON("//indianalearns.info/api/v1/reports/school_public_istep?group=" + grade + "&year=" + year + "&limit=999999",function(data){
				var params = {
					"grade"	: grade,
				 "score"	: score,
				 "subject"	: subject
				}
				mapSchoolArray(schools,data,params);
				notLoading();
			});
	}
	function buildSchoolInfoWindow(school,test){
		var testResults = test ? "<li><strong>Grade " + test.group + " English / Language Arts Passing: </strong>"+Number(test.ela_percent_pass*100).toPrecision(4)+ "%</li>" +
			"<li><strong>Grade " + test.group + " Mathematics Passing: </strong>"+Number(test.math_percent_pass*100).toPrecision(4) + "%</li>" +
			"<li><strong>Grade " + test.group + " Combined Passing: </strong>"+Number(test.pass_both_percent*100).toPrecision(4)+ "%</li>" : "";
		return new google.maps.InfoWindow({
			content : "<h1>" + school.name + "</h1>"+
			"<ul><li><strong>Principal: </strong>" + school.principal_name + "</li>"+
			"<li><strong>School Type: </strong>"+school.category + "</li>" + 
			"<li><strong>Grades: </strong>"+school.grade_span + "</li></ul>" + testResults
		});	
	}
	function schoolHasGrade(school,grade){
		if(school.grade_span){
			var grades = school.grade_span.split("-");
			for(var i = 0; i < grades.length; i++){
				grades[i] = grades[i]=="KG" ? 0 : grades[i]; //map letter grades to numeric values
				grades[i] = grades[i]=="PK" ? -1 : grades[i];
				grades[i] = grades[i]=="SP" || grades[i]=="ED" ? -2 : grades[i];
				grades[i] = parseInt(grades[i]);
			}
			if(grade>=grades[0]&&grade<=grades[1]){
				return true;
			}else{
				return false;	
			}
		}else{
			return false;
		}
	}
	function clearMarkers(){
		for(var i = 0; i< markers.length; i++){
			markers[i].setMap(null);
		}
		markers = [];
	}
	function loading(){
		$("#loading-overlay").fadeIn();
	}
	function notLoading(){
		$("#loading-overlay").fadeOut();	
	}
	function setInitialLocation(){
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
	function mapSchoolArray(schools,tests,params){
		for(var i = 0; i < schools.length; i++){ //iterate over every school
			var school = schools[i];
			
			if(schoolHasGrade(school,params.grade)){ // if the school has the grade level we're interested
				var schoolTest, icon;
				for(var count=0; count<tests.length; count++){ // iterate through the tests
					var test = tests[count];
					if(test["school_id"] == school.id){ // found the correct report item
						schoolTest = test;
						break;
					}
				}
				if(schoolTest){
					if(schoolTest[params.subject] > params.score){
						icon = "http://maps.google.com/mapfiles/marker_green.png";
					}else if(schoolTest[params.subject] < params.score){
						icon = "http://maps.google.com/mapfiles/marker.png";
					}else{
						icon = "http://maps.google.com/mapfiles/marker_grey.png";
					}
				}else{
					icon = "http://maps.google.com/mapfiles/marker_grey.png";
				}
				addSchoolToMap(school,icon,test);
			};
		}		
	}

	function addSchoolToMap(school,icon,test){
		var loc = new google.maps.LatLng(school.lat,school.lon);
		var mark = new google.maps.Marker({
			map: map,
			position: loc,
			title: school.name,
			icon: icon
		});
		mark['infowindow'] = buildSchoolInfoWindow(school,test);
		google.maps.event.addListener(mark, 'click', function() {
			this['infowindow'].open(map,this);
		});
		markers.push(mark);
	}
}());
(function ($) {
	$('.spinner .btn:first-of-type').on('click', function() {
		$('.spinner input').val( parseInt($('.spinner input').val().replace("%",""), 10) + 5 +"%");
	});
	$('.spinner .btn:last-of-type').on('click', function() {
		$('.spinner input').val( parseInt($('.spinner input').val().replace("%",""), 10) - 5 +"%");
	});
})(jQuery);