
$(window).on('load', function () {
    if (is_loan == 1) {
        alert('هذا العقد تمت تسويته');
    }
})
$(document).on('click', '#save', function () {
    let monthly_installment = $('#monthly_installment').val();
    let new_installment_date = $('#new_installment_date').val();
    let first_installment_date = $('#first_installment_date').val();
    let contract_id = $('#contract_id').val();
    $.post('add-new-loan', { monthly_installment: monthly_installment, new_installment_date: new_installment_date, first_installment_date: first_installment_date, contract_id: contract_id }, function (msg) {
        $('.loan-alert').css("display", "block");
        $('.loan-alert').text(msg);
    })
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
        $('#exampleModalLabel12').text(info['name']);
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