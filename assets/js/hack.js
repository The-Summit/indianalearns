function render(data) {

    var mapOptions = {
        zoom: 7,
        center: new google.maps.LatLng(39.53394716494817, -86.33931812500003),
        mapTypeId: google.maps.MapTypeId.ROADMAP
    };

    var map = new google.maps.Map(document.getElementById("#map-canvas"),
                                  mapOptions);

    var layer = new google.maps.FusionTablesLayer({
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

}

(function(){
    // corporation graduation rates, pulled from indianalearns api
    var graduation_rates_url = '/api/v1/reports/corporation/graduation_rates?group=total';
    var student_teacher_ratio_url = '/api/v1/reports/corporation/student_teacher_ratios';

    var records = {};

    // get corporation graduation rates
    $.getJSON(graduation_rates_url, function(graduation_rates) {
        for(record of graduation_rates) {
            records['corporation_'+record.corp_id] = record;
        }

        // collect student/teacher ratios into the same record
        $.getJSON(student_teacher_ratio_url, function(student_teacher_ratios) {
            for(record of student_teacher_ratios) {
                //console.log(record);
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
})();
