$(window).on("load", function () {
  if (!debenchPanelMain) {
    $("debench .debench-main-panel").toggle();
    $("debench .debench-main-panel-minimal").toggle();
    if (debenchPanelLast != "") {
      $("debench ul.debench-main-block > li." + debenchPanelLast).toggle();
    }
  }

  $("debench ul li[data-target]").on("click", function () {
    $("debench ul li[data-target]").removeClass('active');
    $(this).addClass('active');

    var panel = $(this).data("target");
    if (panel == "debench-panel-toggle") {
      $("debench .debench-main-panel").toggle();
      $("debench .debench-main-panel-minimal").toggle();

      if (debenchPanelLast != "") {
        $("debench ul.debench-main-block > li." + debenchPanelLast).toggle();
      }
    } else {
      $("debench ul.debench-main-block > li:not(:last-child)").hide();
      if (panel == debenchPanelLast) {
        $("." + panel).hide();
        $(this).removeClass('active');
        panel = "";
      } else {
        $("." + panel).show();
        $(this).addClass('active');
      }
      debenchPanelLast = panel;
    }
  });
});
