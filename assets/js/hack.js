function render(data) {
    console.log("rendering");
    console.log(data);
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
            render(records);
        });
    });
})();
