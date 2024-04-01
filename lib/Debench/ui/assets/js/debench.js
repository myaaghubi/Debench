$(window).on('load', function(){
  if (debenchPanelMain) {
    $('debench .main-panel').toggle();
    $('debench .main-panel-minimal').toggle();
    if (debenchPanelLast!='')
      $('debench ul.debench-pannels > li.'+debenchPanelLast).toggle();
  }

  $("debench ul li[data-target]").on('click', function(){
    var panel = $(this).data('target');
    if (panel=='panel-toggle') {
      $('debench .main-panel').toggle();
      $('debench .main-panel-minimal').toggle();

      if (debenchPanelLast!='')
        $('debench ul.debench-pannels > li.'+debenchPanelLast).toggle();
    } else {
      $('debench ul.debench-pannels > li:not(:last-child)').hide();
      if (panel==debenchPanelLast) {
        $('.'+panel).hide();
        panel = "";
      } else {
        $('.'+panel).show();
      }
      debenchPanelLast = panel;
    }
  });
});
