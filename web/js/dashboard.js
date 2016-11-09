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


function addContact(form) {
	var position = $(form).find("input[id=position]").val();
	var firstname = $(form).find("input[id=firstname]").val();
	var lastname = $(form).find("input[id=lastname]").val();
	var phone = $(form).find("input[id=phone]").val();
	var email = $(form).find("input[id=email]").val();
	var path = $(form).find("input[id=path]").val();

	$.ajax({
		method: 'post',
		url: path,
		data: {
			position: position,
			firstname: firstname,
			lastname: lastname,
			phone: phone,
			email: email
		}, 
		success: function (data) {
			console.log(data);
			localStorage.setItem('status', data.message);
			window.location.reload();
		}
	});
}

console.log(localStorage.getItem('status'));

function deleteRider(id, path) {

	$.ajax({
		method: 'post',
		url: path,
		data: {
			id: id
		},
		success: function (data) {
			$(".info-message").toggleClass('alert-' + data.type);
			$(".info-message").text(data.message);
			$(".info-message").show();
		}
	});
}

function deleteAlbum(id, path) {
	$.ajax({
		method: 'post',
		url: path,
		data: {
			id: id
		},
		success: function (data) {
			$(".info-message").toggleClass('alert-' + data.type);
			$(".info-message").text(data.message);
			$(".info-message").show();
		}
	});
}

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