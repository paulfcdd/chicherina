'use strict';

// function topkek() {
//     if (input.files && input.files[0]) {
//         var reader = new FileReader();
//
//         reader.onload = function (e) {
//             $('#blah').attr('src', e.target.result);
//         }
//
//         reader.readAsDataURL(input.files[0]);
//     }
//
//     var files = $("#photos")[0].files;
//     for (var i = 0; i < files.length; i++)
//         console.log(files[i]);
// }

function readURL(input) {
    var files = input.files;

    if (files && files[0]) {
        var reader = new FileReader();
        var newHTML = [];

        $.each( files, function(  key, value ) {
            var size = (value.size / (1024*1024)).toFixed(2);
            // reader.onload = function (e) {
            //     $('.photo').attr('src', e.target.result);
            // };
            newHTML.push("" +
                "<tr>" +
                "<td>"+value.name+"</td>" +
                "<td>"+size+" (МБ)</td>" +
                "</tr>");

            $(".preview tbody").html(newHTML);
        });

        reader.onload = function (e) {
            $('#blah').attr('src', e.target.result);
        }
        //
        // reader.readAsDataURL(input.files[0]);
    }
}

$("#photos").change(function(){
    readURL(this);
});