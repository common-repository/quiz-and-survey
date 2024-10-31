/**
 * Backend admin management for Quiz-and-Survey plugin.
 */

var QASAdmin = {};

QASAdmin.submitNewQuiz = function(e) {
    if(jQuery(document.body).hasClass("processing")){
        return;
    }

    var form_data = new FormData();
    form_data.append("_ajax_nonce", qas_admin_ajax_obj.nonce);
    form_data.append("action", jQuery("#qas-action").val());
    form_data.append("quiz_type", jQuery("#qas-quiz-type").val());
    form_data.append("quiz_file", jQuery("#qas-quiz-file").prop("files")[0]);

    form_data.append("qas_id", jQuery("#qas-id").val());
    form_data.append("quiz_title", jQuery("#qas-quiz-title").val());

    var pass_score_text = jQuery("#qas-quiz-pass-score");
    if(pass_score_text.length) {
        form_data.append("quiz_pass_score", pass_score_text.val());
    }
    var anyone_can_respond_checkbox = jQuery("#qas-quiz-anyone-can-respond");
    if(anyone_can_respond_checkbox.length) {
        form_data.append("quiz_anyone_can_respond", anyone_can_respond_checkbox.prop("checked") ? 1 : 0);
    }

    jQuery(document.body).addClass("processing");
    try {
        jQuery.ajax({
            type: "POST",
            url: qas_admin_ajax_obj.ajax_url,
            cache: false,
            contentType: false,
            processData: false,
            data: form_data,
            success: function(res) {
                  jQuery(document.body).removeClass("processing");

                  if(res.code == 0) {
                      location.href = res.data;
                  } else {
                      QASAdmin.showMsg(res.code, res.msg);
                  }
              }
        });
    }
    catch(e) {
        alert(e);
    }
}

QASAdmin.deleteQuiz = function(e) {
    var result = confirm(qas_admin_ajax_obj.confirm_delete_quiz);
    if (result) {
        var data_id = jQuery(e).attr("data-id");
        var quiz_type = jQuery(e).attr("data-type")
        var data = {
                _ajax_nonce:     qas_admin_ajax_obj.nonce,
                action:          "qas_delete_quiz",
                qas_quiz_id:     data_id,
                quiz_type:       quiz_type
        };
        jQuery(document.body).addClass("processing");
        jQuery.post(qas_admin_ajax_obj.ajax_url,
                data,
                function (res) {
                    jQuery(document.body).removeClass("processing");

                    if(res.code == 0) {
                        jQuery("#qas-" + quiz_type + "-" + data_id).remove();
                    } else {
                        QASAdmin.showMsg(res.code, res.msg);
                    }
                }
        );
    }
}

QASAdmin.deleteQuizResult = function(e) {
    var result = confirm(qas_admin_ajax_obj.confirm_delete);
    if (result) {
        var quiz_result_id = jQuery(e).attr("data-id");
        var data = {
                _ajax_nonce:     qas_admin_ajax_obj.nonce,
                action:          "qas_delete_quiz_result",
                quiz_result_id:  quiz_result_id
        };
        jQuery(document.body).addClass("processing");
        jQuery.post(qas_admin_ajax_obj.ajax_url,
                data,
                function (res) {
                    jQuery(document.body).removeClass("processing");

                    if(res.code == 0) {
                        jQuery("#qas-quiz-result-" + quiz_result_id).remove();
                    } else {
                        QASAdmin.showMsg(res.code, res.msg);
                    }
                }
        );
    }
}

QASAdmin.submitSettingsDataDelete = function(e) {
    var data_delete = jQuery("#qas-settings-delete-data").prop("checked");
    if(data_delete) {
        data_delete = "yes";
        var result = confirm(qas_admin_ajax_obj.confirm_delete_when_uninstall);
        if(!result) {
            return;
        }
    } else {
        data_delete = "no";
    }

    var data = {
            _ajax_nonce:     qas_admin_ajax_obj.nonce,
            action:          "qas_submit_settings_data_delete",
            data_delete:     data_delete
    };

    jQuery(document.body).addClass("processing");
    jQuery.post(qas_admin_ajax_obj.ajax_url,
            data,
            function (res) {
                jQuery(document.body).removeClass("processing");

                QASAdmin.showMsg(res.code, res.msg);
            }
    );
}

QASAdmin.showMsg = function(code, msg) {
    if(jQuery("#qas-message").length == 0) {
        jQuery("#qas-message-container").append('<div id="qas-message" class="alert alert-dismissible"></div>');
    }
    jQuery("#qas-message").removeClass("alert-success alert-danger");
    if(code == 0) {
        jQuery("#qas-message").addClass("alert-success")
            .html('<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' + msg)
            .slideDown("slow");
    } else {
        jQuery("#qas-message").addClass("alert-danger")
            .html('<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' + msg)
            .slideDown("slow");
    }
}

QASAdmin.init = function() {
    // validate

    jQuery("#qas-settings-data-delete-form").validate({submitHandler: QASAdmin.submitSettingsDataDelete});
    jQuery("#qas-import-quiz-form").validate({submitHandler: QASAdmin.submitNewQuiz});
    jQuery("#qas-edit-quiz-form").validate({submitHandler: QASAdmin.submitNewQuiz});

    // datatable

    jQuery("#qas-quizzes-table").dataTable({});
    jQuery("#qas-surveys-table").dataTable({});
    jQuery("#qas-quiz-result-table").dataTable({});

}

jQuery(document).ready(QASAdmin.init);