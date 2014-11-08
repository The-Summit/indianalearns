(function(){
    // corporation graduation rates, pulled from indianalearns api
    var graduation_rates_url = '/api/v1/reports/corporation/graduation_rates?group=total';
    var student_teacher_ratio_url = '/api/v1/reports/corporation/student_teacher_ratios';

    $.getJSON(student_teacher_ratio_url, function(student_teacher_ratios) {
        console.log("student_teacher_ratios:");
        console.log(student_teacher_ratios);
    });

    $.getJSON(graduation_rates_url, function(graduation_rates) {
        console.log("graduation_rates:");
        console.log(graduation_rates);
    });

})();
