(function(){
	var names = bloodhound();
	var type = typeahead();
	var ctx = $("#chart").get()[0].getContext("2d");
	var chart = false;
	type.on("typeahead:selected",function(event,datum){
		$.getJSON("/api/v1/reports/school/iread?school_id=" + datum.id,function(data){
			if(data.length!==2){
				error();
			}else{
				sortByYear(data);
				var label = makeLabel(data);
				if(chart){
					chart.addData([data[0].iread_pass_perc*100,data[1].iread_pass_perc*100],label);
				}else{
					chart = new Chart(ctx).Bar({
						labels: [label],
						datasets:[{
							fillColor: "rgba(59,109,140,1)",
							data: [data[0].iread_pass_perc*100]
						},{
							fillColor: "rgba(153,65,61,1)",
							data: [data[1].iread_pass_perc*100]
						}
					]
					},{
						responsive: true
					});
					chart.update();	
				}
			}
		});
	});
	function sortByYear(data){
		data.sort(function(a,b){
			if(a.year > b.year){
				return 1;	
			}
			if(a.year < b.year){
				return -1;	
			}
			return 0;
		});	
	}
	function error(){
		$(".twitter-typeahead").parent().after($('<div class="fade in alert alert-danger alert-dismissible" style="position:relative;top:20px;clear:both"><button type="button" class="close" data-dismiss="alert">&times;</button><strong>Sorry!</strong><p>No records were found for this school.</div>'));
		window.setTimeout(function(){$(".alert").alert("close"); }, 4000);	
	}
	function makeLabel(data){
		return data[0].school_name + " - " + data[0].year + " / " + data[1].year;
	}
	
	function loading(){
		$("#loading-overlay").fadeIn();
	}
	function notLoading(){
		$("#loading-overlay").fadeOut();	
	}
	function bloodhound(){
		var names = new Bloodhound({
			datumTokenizer: Bloodhound.tokenizers.obj.whitespace('name'),
 			queryTokenizer: Bloodhound.tokenizers.whitespace,
			prefetch : {
				url : "http://indianalearns.info/api/v1/schools?limit=9999"
			}
		});
		names.initialize();
		return names;
	}
	function typeahead(){
		return $(".typeahead").typeahead({
			minLength: 3,
			highlight: true,
			hint: false,
		},{
			source: names.ttAdapter(),
			displayKey: 'name',
			templates: {
				 suggestion: Handlebars.compile('<p data-id="{{id}}"><strong>{{name}}</strong> - {{city}}</p>')
			}
		});
	}
}());