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
    console.log(input.files);
    // if (input.files && input.files[0]) {
    //     var reader = new FileReader();
    //
    //     reader.onload = function (e) {
    //         $('#blah').attr('src', e.target.result);
    //     }
    //
    //     reader.readAsDataURL(input.files[0]);
    // }
}

$("#photos").change(function(){
    readURL(this);
});