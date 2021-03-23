//после загрузки DOM
$(function () {
    var form1 = new ProcessForm(
    '.ajax-form',  
    {
      selector: '.ajax-form'
    });
    
    form1.init();
    
});