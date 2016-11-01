'use strict';

$(function () {
    $('#datetimepicker1').datetimepicker({
        format: 'YYYY-MM-DD',
        locale: 'ru'
    });
});


$(function () {
    $('#datetimepicker2').datetimepicker({
        format: 'YYYY-MM-DD',
        locale: 'ru'
    });
});

function addTour(form) {
    var date = $(form).find("input[id=tourDate]").val();
    var city = $(form).find("input[id=tourCity]").val();
    var place = $(form).find("input[id=tourPlace]").val();
    var path = $(form).find("input[id=path]").val();

    $.ajax({
        url: path,
        method: 'post',
        data: {
            date: date,
            city: city,
            place: place
        },
        success: function (data) {
            $(".info-message").toggleClass('alert-' + data.type);
            $(".info-message").text(data.message);
            $(".info-message").show();
            setTimeout(function () {
                location.reload, 5000
            });
        }
    });
}

function editTour(form) {
    var date = $(form).find("input[id=tourDate]").val();
    var city = $(form).find("input[id=tourCity]").val();
    var place = $(form).find("input[id=tourPlace]").val();
    var path = $(form).find("input[id=path]").val();
    var id = $(form).find("input[id=id]").val();

    $.ajax({
        url: path,
        method: 'post',
        data: {
            id: id,
            date: date,
            city: city,
            place: place
        },
        success: function (data) {
            $(".info-message").toggleClass('alert-' + data.type);
            $(".info-message").text(data.message);
            $(".info-message").show();
        }
    });
}

function addAlbum(form) {
    var name = $(form).find("input[id=albumName]").val();
    var path = $(form).find("input[id=path]").val();

    $.ajax({
        url: path,
        method: 'post',
        data: {
            name: name
        },
        success: function (data) {
            $(".info-message").toggleClass('alert-' + data.type);
            $(".info-message").text(data.message);
            $(".info-message").show();
        }
    });
}