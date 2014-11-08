var map;
var geocoder;
var districts_layer;

var timeout = 0;
function add_district_circle(record) {

    if(record.lat != null) {
        // console.log("already have point for: "+record.name);
        // console.log("  "+record.lat+', ' + record.lon);
        point = new google.maps.LatLng(record.lat, record.lon);

        var circleOptions = {
            strokeColor: '#FF0000',
            strokeOpacity: 0.8,
            strokeWeight: 2,
            fillColor: '#FF0000',
            fillOpacity: 0.35,

            map: map,
            center: point,
            radius: 16093
        };

        var circle = new google.maps.Circle(circleOptions);
        var marker = new google.maps.Marker({
            position: point,
            map: map,
            title: record.name
        });
    }

}

function render(data) {

    var mapOptions = {
        zoom: 7,
        center: new google.maps.LatLng(39.53394716494817, -86.33931812500003),
        mapTypeId: google.maps.MapTypeId.ROADMAP
    };

    map = new google.maps.Map(document.getElementById("#map-canvas"),
                              mapOptions);

    geocoder = new google.maps.Geocoder();

    districts_layer = new google.maps.FusionTablesLayer({
        map: map,
        heatmap: { enabled: false },
        query: {
            select: "col12",
            from: "1zf_oe_OyHT_DMY9zD8wtV3joNMV0S9IC9ABsU-Q",
            where: ""
        },
        options: {
            styleId: 2,
            templateId: 2
        }
    });

    for(i in data) {
        add_district_circle(data[i]);
    }

}

(function(){
    // corporation graduation rates, pulled from indianalearns api
    var corporations_url = '/api/v1/corporations?limit=9999';
    var graduation_rates_url = '/api/v1/reports/corporation/graduation_rates?group=total&limit=9999';
    var student_teacher_ratio_url = '/api/v1/reports/corporation/student_teacher_ratios';

    var records = {};

    $.getJSON(corporations_url, function(corporations) {
        for(record of corporations) {
            records['corporation_'+record.id] = record;
        }

        // get corporation graduation rates
        $.getJSON(graduation_rates_url, function(graduation_rates) {
            for(record of graduation_rates) {
                if(records['corporation_'+record.corp_id] !== undefined) {
                    records['corporation_'+record.corp_id].graduates = record.graduates;
                    records['corporation_'+record.corp_id].grad_rate = record.grad_rate;
                }
            }

            // collect student/teacher ratios into the same record
            $.getJSON(student_teacher_ratio_url, function(student_teacher_ratios) {
                for(record of student_teacher_ratios) {
                    if(records['corporation_'+record.corp_id] !== undefined) {
                        records['corporation_'+record.corp_id].students = record.students;
                        records['corporation_'+record.corp_id].teachers = record.teachers;
                        records['corporation_'+record.corp_id].schools = record.schools;
                        records['corporation_'+record.corp_id].ratio = record.ratio;
                    }
                }

                // all data loaded
                var data = [];
                for( r in records ) {
                    data.push(records[r]);
                }
                render(data);
            });
        });
    });
})();
