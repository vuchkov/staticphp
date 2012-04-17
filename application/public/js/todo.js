
$('#add_item_input').focus();




// Edit item
$('#items')
  .on('mouseenter', '.item', function(){
    var span = $(this).find('span');
    var title = span.html().replace('"', '&quot;');
    span.html('<input type="text" value="'+ title +'" />')
      .find('input:first')
        .focus();
    $(this).data('old_title', title);
  })
  .on('mouseleave', '.item', function(){
    var span = $(this).find('span');
    span.html(span.find('input:first').val().replace('&quot;', '"'));
    
    if (span.html() == $(this).data('old_title'))
    {
      return;
    }
    
    $(this).css({ 'opacity': 0.5 });

    $.post(BASE_URI + 'home/_json/save', {'id' : $(this).data('id'), 'title' : span.html()}, function(data){
      if (data.title)
      {
        span.html(data.title);
      }
    }, 'json')
    .complete(function(){
      $(this).css({ 'opacity': 1 });
    });

  });



// Set complete
$('#items')
  .on('click', '.checkbox', function(){
    var item = $(this).parents('.item:first');
    item.trigger('mouseleave');
    item.css({ 'opacity': 0.5 });

    $.post(BASE_URI + 'home/_json/done', {'id' : item.data('id')}, function(data){
      item.fadeOut(200, function(){
        $('#add_item').after(item);
  
        item.css({ 'opacity': 1 })
          .addClass('done')
          .find('input:first')
            .remove();
        item.fadeIn(100);
      });
    }, 'json');
  });



// Add item
$('#add_item_input').on('keydown', function(e){
  if (e.keyCode == 13)
  {
    $('#add_item_submit').trigger('click');
  }
});

$('#add_item_submit').on('click', function(){

  if ($.trim($('#add_item_input').val()) == '')
  {
    $('#add_item_input').focus();
    return;
  }

  $('#add_item').css({ 'opacity': 0.5 });

  $.post(BASE_URI + 'home/_json/add', {'title' : $('#add_item_input').val()}, function(data){
    if (data && data.title)
    {
      $('#add_item').before('<div class="item" data-id="'+ data.id +'"><input type="checkbox" class="checkbox" /> <span>'+ data.title +'</span></div>');
      $('#add_item_input').val('');
    }
  }, 'json')
  .complete(function(){
    $('#add_item').css({ 'opacity': 1 });
  });

});
