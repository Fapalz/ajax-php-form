//после загрузки DOM
$(function () {

  var forms = $('.ajax-form');
  forms.each(function( index ) {
    console.log(this);
    var FORM = new ProcessForm(this);
    FORM.init();
  });
});