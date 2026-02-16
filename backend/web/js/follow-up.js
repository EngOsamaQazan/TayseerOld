
$(window).on('load', function () {
    // Removed settlement alert — no longer needed
})
$(document).on('click', '#save', function () {
    // Validate dates before saving
    if (typeof StlForm !== 'undefined' && !StlForm.validateNewDate()) {
        $('.loan-alert').css("display", "block")
            .removeClass('alert-success').addClass('alert-danger')
            .text('يرجى تصحيح تاريخ القسط الجديد قبل الحفظ');
        return;
    }
    var data = {
        contract_id:            $('#contract_id').val(),
        monthly_installment:    $('#monthly_installment').val(),
        first_installment_date: $('#first_installment_date').val(),
        new_installment_date:   $('#new_installment_date').val(),
        settlement_type:        $('#stl_settlement_type').val() || 'monthly',
        total_debt:             $('#stl_total_debt').val(),
        first_payment:          $('#stl_first_payment').val(),
        installments_count:     $('#stl_installments_count').val(),
        remaining_debt:         $('#stl_remaining_debt').val(),
        notes:                  $('#stl_notes').val()
    };
    $.post(typeof OCP_URLS !== 'undefined' ? OCP_URLS.addNewLoan : 'add-new-loan', data, function (msg) {
        $('.loan-alert').css("display", "block").text(msg);
        if (msg.indexOf('بنجاح') > -1) {
            $('.loan-alert').removeClass('alert-danger').addClass('alert-success');
            setTimeout(function(){ location.reload(); }, 1200);
        } else {
            $('.loan-alert').removeClass('alert-success').addClass('alert-danger');
        }
    });
})
$(document).on('click', '#closeModel', function () {
    location.reload(true);
})
/////
$(document).on('change', '.cant_contact', function () {
    let id = $('.cant_contact').attr('contract_id');
    let val1 = $('.cant_contact').val();
    alert(val1);
});
/////
$(document).on('click', '.custmer-popup', function () {
    let customerId = $(this).attr('customer-id');
    var a = document.getElementById('cus-link');
    a.setAttribute("href", "../../customers/customers/update?id=" + customerId);
    $.post(customer_info_url, { customerId: customerId }, function (msg) {
        let info = JSON.parse(msg);
        $('.cu-name').val(info['name']);
        $('#customerInfoTitle').text(info['name']);
        $('.cu-id-number').val(info['id_number']);
        $('.cu-birth-date').val(info['birth_date']);
        $('.cu-job-number').val(info['job_number']);
        $('.cu-email').val(info['email']);
        $('.cu-account-number').val(info['account_number']);
        $('.cu-bank-branch').val(info['bank_branch']);
        $('.cu-primary-phone-number').val(info['primary_phone_number']);
        $('.cu-sex').val(info['sex']);
        $('.cu-facebook-account').val(info['facebook_account']);
        $('.cu-hear-about-us').val(info['hear_about_us']);
        $('.cu-status').val(info['status']);
        $('.cu-city').val(info['city']);
        $('.cu-bank-name').val(info['bank_name']);
        $('.cu-job-title').val(info['job_title']);
        $('.cu-notes').val(info['notes']);
        if (info['social_security_number'] != undefined) {
            $('.cu-social-security-number').val(info['social_security_number']);


            if (info['is_social_security'] = '0') {
                $('.cu-is-social-security').val('لا');
            } else {
                $('.cu-is-social-security').val('نعم');
            }

            if (info['do_have_any_property'] = '0') {
                $('.cu-do-have-any-property').val('لا');
            } else {
                $('.cu-do-have-any-property').val('نعم');
            }
        }
    })
});
/////
function copyText(element) {
    var range, selection, worked;
    if (document.body.createTextRange) {
        range = document.body.createTextRange();
        range.moveToElementText(element);
        range.select();
    } else if (window.getSelection) {
        selection = window.getSelection();
        range = document.createRange();
        range.selectNodeContents(element);
        selection.removeAllRanges();
        selection.addRange(range);
    }
    try {
        document.execCommand('copy');
        alert('text copied');
    } catch (err) {
        alert('unable to copy text');
    }
}
/////

$(document).on('click', '#send_sms', function () {
    let phone_number = $('#phone_number').val();
    let text = $('#sms_text').val();
    $.post(send_sms, { text: text, phone_number: phone_number }, function (data) {
        let msg = JSON.parse(data)
        if (msg.message == '') {
            alert('تم ارسال الرسالة بنجاح');
        } else
            alert(msg.message);
    })
})
///////
$(document).ready(function () {
    var textarea = $("#sms_text");
    textarea.keydown(function (event) {
        var numbOfchars = textarea.val();
        var len = numbOfchars.length;
        $("#char_count").text(len);
    });
});
//////
$(document).on('click', '.statse-change', function () {
    let id = $('.statse-change').attr('contract-id');
    let statusContent = $('.status-content').val();
    $.post(change_status_url, { id: id, statusContent: statusContent }, function (e) {
        location.reload();
    })
})
////
function setPhoneNumebr(number) {
    $("#phone_number").val(number);
}