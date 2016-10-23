'use strict';

function addAdmin(form) {
    var login = $(form).find("input[id=login]").val();
    var email = $(form).find("input[id=email]").val();
    var password = $(form).find("input[id=password]").val();
    var role = $(form).find("input[id=role]").val();
    var path = $(form).find("input[id=path]").val();

    $.ajax({
        url: path,
        method: 'post',
        data: {
            login: login,
            email: email,
            password: password,
            role: role
        },
        success: function (data) {
            $(".info-message").toggleClass('alert-'+data.type);
            $(".info-message").text(data.message);
            $(".info-message").show();
        }
    });

}