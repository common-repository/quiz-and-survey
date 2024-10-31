/**
 * Frontend quiz submitting for Quiz-and-Survey plugin.
 */

var QASQuiz={};

QASQuiz.current_question = 0;
QASQuiz.question_count = 0;
QASQuiz.mode = "show";

QASQuiz.isAnswered = function() {
    var answered = false;
    let question_elem = jQuery("#qas-question-" + QASQuiz.current_question);
    let question_type = question_elem.find("#qas-question-type-" + QASQuiz.current_question).val();
    if(question_type == "FB") {
        let val = question_elem.find("#qas-answer-" + QASQuiz.current_question).val();
        if(val.trim()) {
            answered = true;
        }
    } else {
        question_elem.find("input.option").each(function(i) {
            if(this.checked) {
                answered = true; // Note you need to change outside variable "answered"
            }
        });
    }

    return answered;
}

QASQuiz.isRequired = function() {
    if(jQuery("#qas-question-" + QASQuiz.current_question).attr("class") == "required") {
        return true;
    }

    return false;
}

QASQuiz.checkAnswer = function(e) {
    if(!QASQuiz.isAnswered()) {
        alert(qas_quiz_ajax_obj.need_answer);
        return false;
    }
    return true;
}

QASQuiz.nextQuestion = function(e, dir) {
    dir = dir || "next"; // next or prev
    if(dir == "next") {
        if(!QASQuiz.checkAnswer(e)) {
            return;
        }
        if((QASQuiz.current_question + 1) >= QASQuiz.question_count) {
            return;
        }
    } else {
        if(QASQuiz.current_question <= 0) {
            return;
        }
    }

    jQuery("#qas-question-" + QASQuiz.current_question).hide();

    if(dir == "next") {
        QASQuiz.current_question++;
    }
    else {
        QASQuiz.current_question--;
    }
    jQuery("#qas-question-" + QASQuiz.current_question).show();

    // change the displayed current question number
    jQuery("#qas-current-question").html(QASQuiz.current_question + 1);

    // show/hide prev/next button
    if(QASQuiz.current_question <= 0) {
        jQuery("#qas-prev-question").hide();
    } else {
        jQuery("#qas-prev-question").show();
    }
    if((QASQuiz.current_question + 1) >= QASQuiz.question_count) {
        jQuery("#qas-next-question").hide();
        jQuery("#qas-action-submit").show();
    }
    else {
        jQuery("#qas-next-question").show();
        jQuery("#qas-action-submit").hide();
    }

    if(jQuery(document).scrollTop() > 250) {
        jQuery(document.body).animate(
            {scrollTop: jQuery("#qas-quiz").offset().top - 100},
            100
        );
    }
}

QASQuiz.submitResult = function(e) {
    if(!QASQuiz.checkAnswer(e)) {
        return;
    }

    if(jQuery(document.body).hasClass("processing")) {
        return;
    }

    var data = {
        post_id :         jQuery("#qas-post-id").val(),
        qas_quiz_id :     jQuery("#qas-quiz-id").val(),
        quiz_start_time : jQuery("#quiz-start-time").val()
    };

    if(Number(data["qas_quiz_id"]) <= 0) {
        alert(qas_quiz_ajax_obj.wrong_id);
    }

    // quiz result

    var action;
    var results = new Array();
    if( QASQuiz.type == "quiz" ) {
        action = "qas_submit_quiz_result";
        for(i = 0; i < QASQuiz.question_count; ++i) {
            var answer = new Array();
            let question_elem = jQuery("#qas-question-" + i);
            let question_type = question_elem.find("#qas-question-type-" + i).val();
            if(question_type == "FB") {
                answer[0] = question_elem.find("#qas-answer-" + i).val();
            } else {
                question_elem.find("input.option").each(function(j, e){
                    if(e.checked) {
                        answer.push(j);
                    }
                });
            }

            results[i] = (question_type == "MC") ? answer : answer[0];
        }
    } else {
        action = "qas_submit_survey_result";
        var k = 0;
        for(i = 0; i < QASQuiz.question_count; ++i) {
            let question_elem = jQuery("#qas-question-" + i);
            question_elem.find("input.option").each(function(j, e){
                if(e.checked) {
                    results[k++] = {
                        question_no:   i + 1,   // question number
                        option_no:     j + 1    // option number
                    }
                }
            });
        }
    }

    data["results"] = results;

    jQuery(document.body).addClass("processing").animate(
        {scrollTop: jQuery("#qas-quiz").offset().top - 50},
        1000
    );

    try {
        jQuery.post(qas_quiz_ajax_obj.ajax_url, {
             _ajax_nonce:     qas_quiz_ajax_obj.nonce,
             action:          action,
             data:            data
        }, QASQuiz.responseSubmitResult);
    }
    catch(e) {
        alert(e);
    }
}

QASQuiz.responseSubmitResult = function(res) {
    jQuery(document.body).removeClass("processing");
    jQuery("#qas-quiz").html(res.msg);
}

QASQuiz.current_radio_input = null;
QASQuiz.init = function() {
    jQuery("#qas-question-0").show();
    QASQuiz.question_count = jQuery(".qas-question").length;
    QASQuiz.type = jQuery("#qas-quiz-type").val();

    jQuery("#qas-prev-question").hide();
    if(QASQuiz.question_count == 1) {
        jQuery("#qas-action-submit").show();
        jQuery("#qas-next-question").hide();

    } else {
        jQuery("#qas-prev-question").click(function(e){QASQuiz.nextQuestion(e, "prev");});
        jQuery("#qas-next-question").click(QASQuiz.nextQuestion);
    }

    jQuery("#qas-respond-quiz-form").validate({submitHandler: QASQuiz.submitResult});
}

jQuery(document).ready(QASQuiz.init);